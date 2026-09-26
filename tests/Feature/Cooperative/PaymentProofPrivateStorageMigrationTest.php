<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofPrivateStorageMigrationTest extends TestCase
{
    use RefreshDatabase;

    private string $isolatedStorageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filesystems.payment_proof_disk', 'local');
        $this->useIsolatedStorage();
    }

    protected function tearDown(): void
    {
        if (isset($this->isolatedStorageRoot)) {
            File::deleteDirectory($this->isolatedStorageRoot);
        }

        parent::tearDown();
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

    public function test_dry_run_preserves_database_and_filesystem_fingerprints(): void
    {
        $publicOnly = 'cooperative/payment-proofs/admin/dry-public.png';
        $privateOnly = 'cooperative/payment-proofs/admin/dry-private.png';
        $matching = 'cooperative/payment-proofs/admin/dry-matching.png';

        Storage::disk('public')->put($publicOnly, 'public-only');
        Storage::disk('local')->put($privateOnly, 'private-only');
        Storage::disk('public')->put($matching, 'same-content');
        Storage::disk('local')->put($matching, 'same-content');
        $this->paymentWithProof($publicOnly);
        $this->paymentWithProof($privateOnly);
        $this->paymentWithProof($matching);

        $before = $this->fingerprint();

        $this->artisan('payments:migrate-proofs-to-private')
            ->assertSuccessful();

        $this->assertSame($before, $this->fingerprint());
    }

    public function test_malformed_and_out_of_scope_paths_are_rejected_without_mutation(): void
    {
        foreach ([
            'branding/logo.png',
            '../payment-proof.png',
            'cooperative/payment-proofs/../secret.png',
            'cooperative/payment-proofs/admin/./proof.png',
            'cooperative/payment-proofs/',
            'cooperative/payment-proofs/admin//proof.png',
            'cooperative\\payment-proofs\\admin\\proof.png',
            'cooperative/payment-proofs/admin/CON.txt',
        ] as $path) {
            $this->paymentWithProof($path);
        }

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_public_delete_is_reported_and_source_is_preserved(): void
    {
        $path = 'cooperative/payment-proofs/admin/delete-failure.png';
        Storage::disk('public')->put($path, 'preserve-on-delete-failure');
        $payment = $this->paymentWithProof($path);

        $publicDisk = \Mockery::mock(Storage::disk('public'))->makePartial();
        $publicDisk->shouldReceive('delete')->once()->with($path)->andReturnFalse();
        Storage::set('public', $publicDisk);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        $this->assertSame('preserve-on-delete-failure', Storage::disk('public')->get($path));
        $this->assertSame('preserve-on-delete-failure', Storage::disk('local')->get($path));
        $this->assertSame($path, $payment->fresh()->proof_path);
    }

    public function test_failed_private_write_is_reported_and_public_source_is_preserved(): void
    {
        $path = 'cooperative/payment-proofs/admin/write-failure.png';
        Storage::disk('public')->put($path, 'preserve-on-write-failure');
        $payment = $this->paymentWithProof($path);

        $privateDisk = \Mockery::mock(Storage::disk('local'))->makePartial();
        $privateDisk->shouldReceive('writeStream')->once()->withArgs(
            static fn (string $target, mixed $stream): bool => $target === $path && is_resource($stream)
        )->andReturnFalse();
        Storage::set('local', $privateDisk);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        $this->assertSame('preserve-on-write-failure', Storage::disk('public')->get($path));
        $this->assertFalse(Storage::disk('local')->exists($path));
        $this->assertSame($path, $payment->fresh()->proof_path);
    }

    public function test_source_read_failure_is_reported_without_removing_public_source(): void
    {
        $path = 'cooperative/payment-proofs/admin/read-failure.png';
        Storage::disk('public')->put($path, 'preserve-on-read-failure');
        $this->paymentWithProof($path);

        $publicDisk = \Mockery::mock(Storage::disk('public'))->makePartial();
        $publicDisk->shouldReceive('readStream')->once()->with($path)->andReturnFalse();
        Storage::set('public', $publicDisk);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_private_hash_read_failure_is_reported_and_public_source_is_preserved(): void
    {
        $path = 'cooperative/payment-proofs/admin/private-hash-failure.png';
        Storage::disk('public')->put($path, 'preserve-on-private-hash-failure');
        $this->paymentWithProof($path);

        $privateAdapter = Storage::disk('local');
        $privateDisk = \Mockery::mock($privateAdapter)->makePartial();
        $privateDisk->shouldReceive('readStream')->once()->with($path)->andReturnFalse();
        Storage::set('local', $privateDisk);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        $this->assertSame('preserve-on-private-hash-failure', Storage::disk('public')->get($path));
        $this->assertTrue($privateAdapter->exists($path));
        $this->assertSame('preserve-on-private-hash-failure', $privateAdapter->get($path));
    }

    public function test_storage_exists_failure_is_reported_without_removing_public_source(): void
    {
        $path = 'cooperative/payment-proofs/admin/exists-failure.png';
        Storage::disk('public')->put($path, 'preserve-on-exists-failure');
        $this->paymentWithProof($path);

        $privateAdapter = Storage::disk('local');
        $privateDisk = \Mockery::mock($privateAdapter)->makePartial();
        $privateDisk->shouldReceive('exists')->once()->with($path)->andThrow(new \RuntimeException('Injected private exists failure.'));
        Storage::set('local', $privateDisk);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        $this->assertSame('preserve-on-exists-failure', Storage::disk('public')->get($path));
        $this->assertFalse($privateAdapter->exists($path));
    }

    public function test_public_exists_failure_is_reported_without_removing_source(): void
    {
        $path = 'cooperative/payment-proofs/admin/public-exists-failure.png';
        Storage::disk('public')->put($path, 'preserve-on-public-exists-failure');
        $this->paymentWithProof($path);

        $publicAdapter = Storage::disk('public');
        $publicDisk = \Mockery::mock($publicAdapter)->makePartial();
        $publicDisk->shouldReceive('exists')->once()->with($path)->andThrow(new \RuntimeException('Injected public exists failure.'));
        Storage::set('public', $publicDisk);

        $this->artisan('payments:migrate-proofs-to-private', ['--execute' => true])
            ->assertExitCode(1);

        $this->assertSame('preserve-on-public-exists-failure', $publicAdapter->get($path));
        $this->assertFalse(Storage::disk('local')->exists($path));
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

    private function useIsolatedStorage(): void
    {
        $this->isolatedStorageRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kojaya-rc04-pay006-'.bin2hex(random_bytes(8));

        Storage::set('local', Storage::build([
            'driver' => 'local',
            'root' => $this->isolatedStorageRoot.DIRECTORY_SEPARATOR.'private',
        ]));
        Storage::set('public', Storage::build([
            'driver' => 'local',
            'root' => $this->isolatedStorageRoot.DIRECTORY_SEPARATOR.'public',
        ]));
    }

    /** @return array{payments: array<int, array{id: int, proof_path: string|null}>, public: array<string, string>, private: array<string, string>} */
    private function fingerprint(): array
    {
        $files = static function (string $disk): array {
            $fingerprint = [];

            foreach (Storage::disk($disk)->allFiles('cooperative/payment-proofs') as $path) {
                $fingerprint[$path] = hash('sha256', Storage::disk($disk)->get($path));
            }

            ksort($fingerprint);

            return $fingerprint;
        };

        return [
            'payments' => CooperativePayment::query()->orderBy('id')->get(['id', 'proof_path'])->toArray(),
            'public' => $files('public'),
            'private' => $files('local'),
        ];
    }
}
