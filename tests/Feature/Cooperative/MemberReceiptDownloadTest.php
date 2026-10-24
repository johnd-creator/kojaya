<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\DownloadLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativeReceiptService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MemberReceiptDownloadTest extends TestCase
{
    private User $owner;

    private CooperativeMember $member;

    private CooperativeDuesInvoice $invoice;

    private CooperativePayment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        self::assertSame('sqlite', config('database.default'));
        self::assertSame(':memory:', config('database.connections.sqlite.database'));
        self::assertSame(realpath(base_path('app/Http/Controllers/Api/V1/MemberSelfServiceController.php')), realpath((new \ReflectionClass(\App\Http\Controllers\Api\V1\MemberSelfServiceController::class))->getFileName()));
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
        $org = Organization::factory()->create();
        $this->owner = User::factory()->create(['organization_id' => $org->id]);
        $this->owner->assignRole('Anggota');
        $this->member = CooperativeMember::factory()->active()->create(['user_id' => $this->owner->id, 'organization_id' => $org->id]);
        $this->invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_contribution_type_id' => CooperativeContributionType::factory()->create()->id,
            'period' => '2026-10', 'amount' => 100000, 'paid_amount' => 100000, 'due_date' => '2026-10-25', 'status' => 'PAID',
        ]);
        $this->payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $this->member->id, 'cooperative_dues_invoice_id' => $this->invoice->id,
            'user_id' => $this->owner->id, 'amount' => 100000, 'payment_method' => 'TRANSFER', 'paid_at' => '2026-10-23', 'status' => 'APPROVED',
        ]);
    }

    public function test_openapi_documents_authenticated_binary_receipt_instead_of_json_envelope(): void
    {
        $spec = app(\App\Services\OpenApi\OpenApiGenerator::class)->generate();
        $operation = $spec['paths']['/api/v1/member/payments/{payment}/receipt/file']['get'];
        self::assertSame(['application/pdf'], array_keys($operation['responses']['200']['content']));
        self::assertSame('binary', $operation['responses']['200']['content']['application/pdf']['schema']['format']);
        self::assertArrayHasKey('404', $operation['responses']);
        self::assertSame(['payment'], array_column($operation['parameters'], 'name'));
        self::assertNotSame([], $operation['security'] ?? $spec['security']);
    }

    private function endpoint(): string
    {
        return '/api/v1/member/payments/'.$this->payment->id.'/receipt/file';
    }

    private function token(User $user, array $abilities = ['member:read']): string
    {
        return $user->createToken('isolated-receipt-fixture', $abilities)->plainTextToken;
    }

    public function test_actual_bearer_without_web_session_downloads_private_pdf_and_replay_does_not_post_money(): void
    {
        $this->withToken($this->token($this->owner));
        $response = $this->getJson($this->endpoint())->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeaderMissing('Location');
        self::assertFalse(Auth::guard('web')->check());
        $bytes = $response->streamedContent();
        self::assertStringStartsWith('%PDF-', $bytes);
        self::assertStringEndsWith('%%EOF', trim($bytes));
        self::assertStringContainsString('private', $response->headers->get('Cache-Control'));
        self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $receipt = $this->payment->fresh()->receipt;
        self::assertStringContainsString($receipt->receipt_no.'.pdf', $response->headers->get('Content-Disposition'));
        Storage::disk('local')->assertExists($receipt->pdf_path);
        Storage::disk('public')->assertMissing($receipt->pdf_path);
        self::assertSame($bytes, $this->getJson($this->endpoint())->assertOk()->streamedContent());
        self::assertSame(1, CooperativeReceipt::query()->count());
        self::assertSame(0, CooperativeLedgerEntry::query()->count());
        self::assertSame('APPROVED', $this->payment->fresh()->status);
        self::assertSame('100000.00', $this->payment->fresh()->amount);
        self::assertSame('PAID', $this->invoice->fresh()->status);
        self::assertSame('100000.00', $this->invoice->fresh()->paid_amount);
        self::assertSame(2, DownloadLog::query()->where('user_id', $this->owner->id)->where('document_type', 'cooperative-receipt')->where('document_id', $receipt->id)->count());
    }

    public function test_guest_expired_and_wrong_ability_tokens_never_issue_or_download_receipts(): void
    {
        $this->getJson($this->endpoint())->assertUnauthorized();
        $this->withToken($this->owner->createToken('expired-fixture', ['member:read'], now()->subMinute())->plainTextToken)->getJson($this->endpoint())->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken($this->token($this->owner, ['profile:read']))->getJson($this->endpoint())->assertForbidden();
        self::assertSame(0, CooperativeReceipt::query()->count());
        self::assertSame([], Storage::disk('local')->allFiles());
        self::assertSame(0, DownloadLog::query()->count());
    }

    public function test_another_member_in_same_organization_cannot_download(): void
    {
        $other = User::factory()->create(['organization_id' => $this->owner->organization_id]);
        $other->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $other->id, 'organization_id' => $other->organization_id]);
        $this->withToken($this->token($other))->getJson($this->endpoint())->assertForbidden();
        self::assertSame(0, CooperativeReceipt::query()->count());
    }

    public function test_member_from_another_organization_cannot_download(): void
    {
        $org = Organization::factory()->create();
        $other = User::factory()->create(['organization_id' => $org->id]);
        $other->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $other->id, 'organization_id' => $org->id]);
        $this->withToken($this->token($other))->getJson($this->endpoint())->assertForbidden();
        self::assertSame(0, CooperativeReceipt::query()->count());
    }

    public function test_linked_member_outside_user_organization_is_rejected_by_canonical_scope(): void
    {
        $this->member->update(['organization_id' => Organization::factory()->create()->id]);
        $this->withToken($this->token($this->owner))->getJson($this->endpoint())->assertForbidden();
        self::assertSame(0, CooperativeReceipt::query()->count());
    }

    public function test_inactive_member_cannot_download_even_with_read_ability(): void
    {
        $this->member->update(['status' => 'PENDING', 'validation_status' => 'PENDING']);
        $this->withToken($this->token($this->owner))->getJson($this->endpoint())->assertForbidden();
        self::assertSame(0, CooperativeReceipt::query()->count());
    }

    public function test_pending_rejected_and_unknown_payment_statuses_never_issue_receipts(): void
    {
        $this->withToken($this->token($this->owner));
        foreach (['PENDING', 'REJECTED', 'UNKNOWN'] as $status) {
            $this->payment->update(['status' => $status]);
            $this->getJson($this->endpoint())->assertNotFound();
        }
        self::assertSame(0, CooperativeReceipt::query()->count());
        self::assertSame([], Storage::disk('local')->allFiles());
        self::assertSame(0, CooperativeLedgerEntry::query()->count());
    }

    public function test_missing_private_pdf_never_falls_back_to_public_storage(): void
    {
        $receipt = app(CooperativeReceiptService::class)->issue($this->payment, $this->owner);
        $bytes = Storage::disk('local')->get($receipt->pdf_path);
        Storage::disk('public')->put($receipt->pdf_path, $bytes);
        Storage::disk('local')->delete($receipt->pdf_path);
        $this->withToken($this->token($this->owner))->getJson($this->endpoint())->assertNotFound();
        self::assertSame(1, CooperativeReceipt::query()->count());
        self::assertSame(0, DownloadLog::query()->count());
    }

    public function test_receipt_member_mismatch_and_unsafe_private_paths_fail_closed(): void
    {
        $receipt = app(CooperativeReceiptService::class)->issue($this->payment, $this->owner);
        $receipt->update(['cooperative_member_id' => CooperativeMember::factory()->active()->create()->id]);
        $this->withToken($this->token($this->owner))->getJson($this->endpoint())->assertNotFound();
        $receipt->update(['cooperative_member_id' => $this->member->id]);
        foreach (['../../.env', 'cooperative/receipts/../other.pdf', 'other-private/report.pdf', 'cooperative/receipts/subdir/file.pdf'] as $path) {
            $receipt->update(['pdf_path' => $path]);
            $this->getJson($this->endpoint())->assertNotFound();
        }
        self::assertSame(0, DownloadLog::query()->count());
    }

    public function test_existing_signed_web_route_remains_session_guarded(): void
    {
        $receipt = app(CooperativeReceiptService::class)->issue($this->payment, $this->owner);
        $url = URL::temporarySignedRoute('download.cooperative-receipt', now()->addMinutes(10), ['receipt' => $receipt->id]);
        $this->withToken($this->token($this->owner))->getJson($url)->assertUnauthorized();
        $this->actingAs($this->owner, 'web')->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}
