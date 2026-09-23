<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofPrivateStorageMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filesystems.payment_proof_disk', 'local');
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_legacy_public_payment_proof_is_copied_verified_and_removed(): void
    {
        $path = 'cooperative/payment-proofs/admin/legacy-proof.png';
        $contents = 'legacy-payment-proof-content';
        Storage::disk('public')->put($path, $contents);
        $payment = $this->paymentWithProof($path);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertSuccessful();

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame($contents, Storage::disk('local')->get($path));
        $this->assertSame($path, $payment->fresh()->proof_path);
    }

    public function test_migration_is_idempotent_for_private_proofs(): void
    {
        $path = 'cooperative/payment-proofs/member/private-proof.png';
        $contents = 'private-payment-proof-content';
        Storage::disk('local')->put($path, $contents);
        $payment = $this->paymentWithProof($path);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertSuccessful();
        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertSuccessful();

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame($contents, Storage::disk('local')->get($path));
        $this->assertSame($path, $payment->fresh()->proof_path);
    }

    public function test_matching_public_duplicate_is_removed_but_mismatched_private_copy_is_preserved(): void
    {
        $matchingPath = 'cooperative/payment-proofs/admin/matching-proof.png';
        Storage::disk('local')->put($matchingPath, 'verified-copy');
        Storage::disk('public')->put($matchingPath, 'verified-copy');
        $this->paymentWithProof($matchingPath);

        $conflictPath = 'cooperative/payment-proofs/admin/conflicting-proof.png';
        Storage::disk('local')->put($conflictPath, 'valid-private-copy');
        Storage::disk('public')->put($conflictPath, 'different-public-copy');
        $this->paymentWithProof($conflictPath);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        Storage::disk('local')->assertExists($matchingPath);
        Storage::disk('public')->assertMissing($matchingPath);
        $this->assertSame('verified-copy', Storage::disk('local')->get($matchingPath));
        Storage::disk('local')->assertExists($conflictPath);
        Storage::disk('public')->assertExists($conflictPath);
        $this->assertSame('valid-private-copy', Storage::disk('local')->get($conflictPath));
    }

    public function test_missing_or_out_of_scope_proofs_fail_safely_without_creating_or_deleting_files(): void
    {
        $missingPath = 'cooperative/payment-proofs/admin/missing-proof.png';
        $outOfScopePath = 'branding/logo.png';
        Storage::disk('public')->put($outOfScopePath, 'unrelated-public-file');
        $this->paymentWithProof($missingPath);
        $this->paymentWithProof($outOfScopePath);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        Storage::disk('local')->assertMissing($missingPath);
        Storage::disk('public')->assertMissing($missingPath);
        Storage::disk('local')->assertMissing($outOfScopePath);
        Storage::disk('public')->assertExists($outOfScopePath);
        $this->assertSame('unrelated-public-file', Storage::disk('public')->get($outOfScopePath));
    }

    public function test_dry_run_reports_work_without_modifying_either_disk(): void
    {
        $path = 'cooperative/payment-proofs/admin/dry-run-proof.png';
        Storage::disk('public')->put($path, 'leave-this-source');
        $this->paymentWithProof($path);

        $this->artisan('payments:migrate-proofs-to-private')
            ->assertSuccessful();

        Storage::disk('local')->assertMissing($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_unreferenced_files_in_the_legacy_proof_namespace_are_reported_and_preserved(): void
    {
        $orphanPath = 'cooperative/payment-proofs/admin/unreferenced-proof.png';
        Storage::disk('public')->put($orphanPath, 'preserve-for-manual-review');

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        Storage::disk('public')->assertExists($orphanPath);
        Storage::disk('local')->assertMissing($orphanPath);
        $this->assertSame('preserve-for-manual-review', Storage::disk('public')->get($orphanPath));
    }

    private function paymentWithProof(string $path): CooperativePayment
    {
        $member = CooperativeMember::factory()->active()->create();

        return CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'amount' => 50000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
            'proof_path' => $path,
        ]);
    }
}
