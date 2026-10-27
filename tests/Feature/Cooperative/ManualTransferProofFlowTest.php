<?php

namespace Tests\Feature\Cooperative;

use App\Models\AuditLog;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManualTransferProofFlowTest extends TestCase
{
    private User $owner;

    private CooperativeMember $member;

    private CooperativeDuesInvoice $invoice;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->seed(RolePermissionSeeder::class);
        config(['filesystems.payment_proof_disk' => 'local']);
        Storage::fake('local');
        Storage::fake('public');
        Http::preventStrayRequests();
        Notification::fake();
        $org = Organization::factory()->create();
        $this->owner = User::factory()->create(['organization_id' => $org->id]);
        $this->owner->assignRole('Anggota');
        $this->member = CooperativeMember::factory()->active()->create(['user_id' => $this->owner->id, 'organization_id' => $org->id]);
        $this->admin = User::factory()->create(['organization_id' => $org->id]);
        $this->admin->assignRole('Admin Koperasi');
        $this->invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $this->member->id,
            'cooperative_contribution_type_id' => CooperativeContributionType::factory()->create()->id,
            'period' => '2026-10', 'amount' => 100000, 'paid_amount' => 0,
            'due_date' => '2026-10-25', 'status' => 'UNPAID',
        ]);
        Sanctum::actingAs($this->owner, ['member:read', 'member:write']);
    }

    private function payload(?UploadedFile $file = null): array
    {
        return [
            'cooperative_dues_invoice_id' => $this->invoice->id,
            'amount' => '100000', 'payment_method' => 'TRANSFER', 'paid_at' => '2026-10-23',
            'reference_no' => 'SYNTHETIC-ONLY',
            'proof' => $file ?? UploadedFile::fake()->image('synthetic.png'),
        ];
    }

    public function test_upload_replay_and_private_owner_admin_retrieval_never_settle_invoice(): void
    {
        $payload = $this->payload();
        $headers = ['Idempotency-Key' => 'synthetic-transfer-01'];
        $response = $this->postJson('/api/v1/member/payments/proof', $payload, $headers)
            ->assertCreated()->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.has_proof', true)->assertJsonMissingPath('data.proof_path');
        $id = $response->json('data.id');
        $this->postJson('/api/v1/member/payments/proof', $payload, $headers)
            ->assertCreated()->assertHeader('X-Idempotency-Replayed', 'true')->assertJsonPath('data.id', $id);
        $this->postJson('/api/v1/member/payments/proof', [...$payload, 'amount' => '99999'], $headers)->assertConflict();
        $this->assertSame(1, CooperativePayment::query()->count());
        $payment = CooperativePayment::query()->findOrFail($id);
        Storage::disk('local')->assertExists($payment->proof_path);
        Storage::disk('public')->assertMissing($payment->proof_path);
        $this->assertSame('UNPAID', $this->invoice->fresh()->status);
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());
        $this->get("/api/v1/member/payments/{$id}")->assertOk()->assertJsonPath('data.has_proof', true)->assertJsonMissingPath('data.proof_path');
        $this->get("/api/v1/member/payments/{$id}/receipt")->assertNotFound();
        $download = $this->get("/api/v1/member/payments/{$id}/proof")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame(file_get_contents($payload['proof']->getPathname()), $download->streamedContent());
        $this->actingAs($this->admin)->get(route('cooperative.payments.proof', $id))->assertOk()->assertHeader('Content-Disposition', 'inline; filename=bukti-pembayaran-'.$id.'.'.pathinfo(CooperativePayment::findOrFail($id)->proof_path, PATHINFO_EXTENSION));
        $this->actingAs($this->admin)->get(route('cooperative.payments.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('payments.data.0.has_proof', true)->where('payments.data.0.can_view_proof', true)->missing('payments.data.0.proof_path'));
    }

    public function test_foreign_invoice_and_proof_are_denied_and_missing_file_is_not_found(): void
    {
        $id = $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertCreated()->json('data.id');
        $foreignOrg = Organization::factory()->create();
        $foreign = User::factory()->create(['organization_id' => $foreignOrg->id]);
        $foreign->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $foreign->id, 'organization_id' => $foreignOrg->id]);
        Sanctum::actingAs($foreign, ['member:read', 'member:write']);
        $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertNotFound();
        $this->get("/api/v1/member/payments/{$id}/proof")->assertForbidden();
        $foreign->assignRole('Admin Koperasi');
        $this->actingAs($foreign)->get(route('cooperative.payments.proof', $id))->assertForbidden();
        $unprivileged = User::factory()->create(['organization_id' => $this->admin->organization_id]);
        $this->actingAs($unprivileged)->get(route('cooperative.payments.proof', $id))->assertForbidden();
        Sanctum::actingAs($this->owner, ['member:read']);
        Storage::disk('local')->delete(CooperativePayment::query()->findOrFail($id)->proof_path);
        $this->get("/api/v1/member/payments/{$id}/proof")->assertNotFound();
    }

    public function test_valid_pdf_upload_remains_private_and_pending(): void
    {
        $bytes = file_get_contents(base_path('tests/Fixtures/manual-transfer-multipage.pdf'));
        $pdf = UploadedFile::fake()->createWithContent('synthetic.pdf', $bytes);
        $id = $this->postJson('/api/v1/member/payments/proof', $this->payload($pdf))
            ->assertCreated()->assertJsonPath('data.status', 'PENDING')->json('data.id');
        $response = $this->get("/api/v1/member/payments/{$id}/proof")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($bytes, $response->streamedContent());
        $adminResponse = $this->actingAs($this->admin)->get(route('cooperative.payments.proof', $id))->assertOk()->assertHeader('Content-Disposition', 'inline; filename=bukti-pembayaran-'.$id.'.'.pathinfo(CooperativePayment::findOrFail($id)->proof_path, PATHINFO_EXTENSION));
        $this->assertSame($bytes, $adminResponse->streamedContent());
    }

    public function test_database_failure_removes_stored_proof_and_leaves_invoice_unchanged(): void
    {
        CooperativePayment::creating(function (): void {
            throw new \RuntimeException('synthetic-write-failure');
        });
        $this->withoutExceptionHandling();
        try {
            $this->postJson('/api/v1/member/payments/proof', $this->payload());
            $this->fail('Expected synthetic write failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('synthetic-write-failure', $exception->getMessage());
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(0, CooperativePayment::query()->count());
        $this->assertSame('UNPAID', $this->invoice->fresh()->status);
    }

    public function test_storage_failure_does_not_create_a_payment(): void
    {
        $disk = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertStatus(503);
        $this->assertSame(0, CooperativePayment::query()->count());
        $this->assertSame('UNPAID', $this->invoice->fresh()->status);
    }

    public function test_validation_blocks_wrong_type_oversize_missing_proof_and_qris(): void
    {
        foreach ([
            ['proof' => UploadedFile::fake()->create('large.pdf', 4097, 'application/pdf')],
            ['proof' => UploadedFile::fake()->create('script.txt', 1, 'text/plain')],
            ['proof' => null], ['payment_method' => 'QRIS'], ['amount' => 0],
        ] as $override) {
            $this->postJson('/api/v1/member/payments/proof', [...$this->payload(), ...$override])->assertUnprocessable();
        }
        $this->assertSame(0, CooperativePayment::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_in_flight_same_key_returns_conflict_without_second_write(): void
    {
        $key = 'synthetic-transfer-inflight';
        $cacheKey = 'idempotency:'.hash('sha256', $this->owner->id.'|POST|api/v1/member/payments/proof|'.$key);
        $lock = Cache::lock($cacheKey.':lock', 120);
        $this->assertTrue($lock->get());
        try {
            $this->postJson('/api/v1/member/payments/proof', $this->payload(), ['Idempotency-Key' => $key])->assertConflict();
            $this->assertSame(0, CooperativePayment::query()->count());
        } finally {
            $lock->release();
        }
    }

    public function test_two_distinct_uploads_cannot_settle_the_same_invoice_twice(): void
    {
        $first = $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertCreated()->json('data.id');
        $second = $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertCreated()->json('data.id');
        $this->assertNotSame($first, $second);
        $this->actingAs($this->admin)->post(route('cooperative.payments.approve', $first))->assertSessionHasNoErrors();
        $this->post(route('cooperative.payments.approve', $second))->assertSessionHasErrors('payment');
        $this->assertEquals(100000, $this->invoice->fresh()->paid_amount);
        $this->assertSame('PENDING', CooperativePayment::findOrFail($second)->status);
        $this->assertEquals(100000, CooperativeLedgerEntry::query()->sum('credit'));
        $this->assertSame(1, CooperativeReceipt::query()->count());
        $this->post(route('cooperative.payments.approve', $first))->assertSessionHasNoErrors();
        $this->assertSame(1, CooperativeLedgerEntry::query()->count());
    }

    public function test_pending_cancellation_preserves_evidence_and_never_changes_financial_balances(): void
    {
        $id = $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertCreated()->json('data.id');
        $payment = CooperativePayment::findOrFail($id);
        $path = $payment->proof_path;
        $this->actingAs($this->admin)->get(route('cooperative.payments.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('payments.data.0.can_cancel', true));
        $this->post(route('cooperative.payments.cancel', $id), ['reason' => 'Pengajuan ganda, bukti sama.'])->assertSessionHasNoErrors();
        $this->assertSame('VOID', $payment->fresh()->status);
        $this->assertEquals(100000, $payment->amount);
        $this->assertSame($path, $payment->proof_path);
        Storage::disk('local')->assertExists($path);
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());
        $this->assertSame(0, CooperativeReceipt::query()->count());
        $this->assertSame('VOID', $payment->approvalLogs()->firstOrFail()->to_status);
        $this->assertSame('Pengajuan ganda, bukti sama.', $payment->approvalLogs()->firstOrFail()->note);
        $this->assertSame(1, AuditLog::query()->where('action', 'payment.pending_cancelled')->count());
        $this->post(route('cooperative.payments.approve', $id))->assertSessionHasErrors('payment');
        $this->post(route('cooperative.payments.cancel', $id), ['reason' => 'Ulang'])->assertSessionHasErrors('reason');
        $this->assertSame(1, $payment->approvalLogs()->count());
    }

    public function test_cancellation_requires_reason_and_organization_authority_and_cannot_cancel_posted_payment(): void
    {
        $id = $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertCreated()->json('data.id');
        $foreign = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
        $foreign->assignRole('Admin Koperasi');
        $this->actingAs($foreign)->post(route('cooperative.payments.cancel', $id), ['reason' => 'Forbidden'])->assertForbidden();
        $this->actingAs($this->owner)->post(route('cooperative.payments.cancel', $id), ['reason' => 'Forbidden'])->assertForbidden();
        $this->actingAs($this->admin)->post(route('cooperative.payments.cancel', $id), ['reason' => '   '])->assertSessionHasErrors('reason');
        $this->post(route('cooperative.payments.cancel', $id), ['reason' => str_repeat('x', 1001)])->assertSessionHasErrors('reason');
        $this->post(route('cooperative.payments.approve', $id))->assertSessionHasNoErrors();
        $this->post(route('cooperative.payments.cancel', $id), ['reason' => 'Too late'])->assertSessionHasErrors('reason');
        $this->assertSame('APPROVED', CooperativePayment::findOrFail($id)->status);
        $this->assertEquals(100000, $this->invoice->fresh()->paid_amount);
        $this->assertSame(1, CooperativeLedgerEntry::query()->count());
        $this->assertSame(1, CooperativeReceipt::query()->count());
    }

    public function test_cancellation_audit_failure_rolls_back_status_and_approval_history(): void
    {
        $id = $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertCreated()->json('data.id');
        $this->mock(AuditLogService::class)->shouldReceive('log')->once()->andThrow(new \RuntimeException('synthetic-audit-failure'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->admin)->post(route('cooperative.payments.cancel', $id), ['reason' => 'Duplicate']);
            $this->fail('Expected failed audit');
        } catch (\RuntimeException $exception) {
            $this->assertSame('synthetic-audit-failure', $exception->getMessage());
        }
        $payment = CooperativePayment::findOrFail($id);
        $this->assertSame('PENDING', $payment->status);
        $this->assertNull($payment->notes);
        $this->assertSame(0, $payment->approvalLogs()->count());
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);
    }

    public function test_bulk_duplicate_approval_rolls_back_without_double_credit(): void
    {
        $ids = [];
        for ($i = 0; $i < 2; $i++) {
            $ids[] = $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertCreated()->json('data.id');
        }
        $this->actingAs($this->admin)->post(route('cooperative.payments.bulk-approve'), ['ids' => $ids])->assertSessionHasErrors('payment');
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);
        $this->assertSame(2, CooperativePayment::query()->where('status', 'PENDING')->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());
        $this->assertSame(0, CooperativeReceipt::query()->count());
    }

    public function test_partial_payments_can_finish_a_month_but_cannot_exceed_its_remaining_balance(): void
    {
        $first = $this->postJson('/api/v1/member/payments/proof', [...$this->payload(), 'amount' => 25000])->assertCreated()->json('data.id');
        $second = $this->postJson('/api/v1/member/payments/proof', [...$this->payload(), 'amount' => 75000])->assertCreated()->json('data.id');
        $excess = $this->postJson('/api/v1/member/payments/proof', [...$this->payload(), 'amount' => 100000])->assertCreated()->json('data.id');
        $this->actingAs($this->admin)->post(route('cooperative.payments.approve', $first))->assertSessionHasNoErrors();
        $this->assertSame('PARTIAL', $this->invoice->fresh()->status);
        $this->post(route('cooperative.payments.approve', $excess))->assertSessionHasErrors('payment');
        $this->post(route('cooperative.payments.approve', $second))->assertSessionHasNoErrors();
        $this->assertSame('PAID', $this->invoice->fresh()->status);
        $this->assertEquals(100000, CooperativeLedgerEntry::query()->sum('credit'));
    }

    public function test_monthly_ledger_description_uses_selected_invoice_month_for_mobile_and_collect(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-12-15'));
        $this->invoice->contributionType->update(['code' => 'WAJIB', 'category' => 'WAJIB']);
        $mobile = $this->postJson('/api/v1/member/payments/proof', [...$this->payload(), 'paid_at' => '2026-12-15', 'notes' => 'Transfer Oktober'])->assertCreated()->json('data.id');
        $this->actingAs($this->admin)->post(route('cooperative.payments.approve', $mobile))->assertSessionHasNoErrors();
        foreach (['2026-11', '2026-12'] as $period) {
            $invoice = $this->invoice->replicate();
            $invoice->fill(['period' => $period, 'paid_amount' => 0, 'status' => 'UNPAID', 'due_date' => $period.'-25']);
            $invoice->save();
            $this->post(route('cooperative.dues.mark-paid'), ['invoice_ids' => [$invoice->id], 'paid_at' => '2026-12-15', 'payment_method' => 'CASH'])->assertSessionHasNoErrors();
        }
        $entries = CooperativeLedgerEntry::query()->orderBy('id')->get();
        $this->assertCount(3, $entries);
        foreach (['Oktober', 'November', 'Desember'] as $index => $month) {
            $this->assertStringStartsWith('Simpanan Wajib '.$month.' 2026', $entries[$index]->description);
            $this->assertSame('2026-'.(10 + $index), $entries[$index]->period);
        }
        $this->assertStringContainsString('Transfer Oktober', $entries[0]->description);
        $this->post(route('cooperative.dues.mark-paid'), ['invoice_ids' => [$this->invoice->id], 'paid_at' => '2026-12-15'])->assertSessionHas('error');
        $this->assertSame(3, CooperativePayment::query()->count());
        $this->assertEquals(300000, CooperativeLedgerEntry::query()->sum('credit'));
    }

    public function test_unexpected_legacy_proof_type_is_downloaded_instead_of_rendered_inline(): void
    {
        $id = $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertCreated()->json('data.id');
        Storage::disk('local')->put('cooperative/legacy.html', '<!doctype html><script>alert(1)</script>');
        CooperativePayment::findOrFail($id)->update(['proof_path' => 'cooperative/legacy.html']);
        $response = $this->actingAs($this->admin)->get(route('cooperative.payments.proof', $id))->assertOk();
        $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_pending_cancellation_cannot_change_gateway_reconciliation_or_locked_period_records(): void
    {
        $id = $this->postJson('/api/v1/member/payments/proof', $this->payload())->assertCreated()->json('data.id');
        $payment = CooperativePayment::findOrFail($id);
        $this->actingAs($this->admin);
        foreach ([['gateway_provider' => 'midtrans'], ['gateway_reference' => 'SYNTHETIC-ORDER'], ['reconciled_at' => now()], ['approved_at' => now()]] as $state) {
            $payment->forceFill($state)->save();
            $this->post(route('cooperative.payments.cancel', $id), ['reason' => 'Blocked'])->assertSessionHasErrors('reason');
            $payment->forceFill(array_fill_keys(array_keys($state), null))->save();
        }
        \App\Models\CooperativePeriodLock::query()->create(['period' => '2026-10', 'module' => 'COOPERATIVE', 'status' => 'LOCKED', 'locked_at' => now(), 'locked_by' => $this->admin->id]);
        $this->post(route('cooperative.payments.cancel', $id), ['reason' => 'Locked'])->assertSessionHasErrors('period');
        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertSame(0, $payment->approvalLogs()->count());
    }

    public function test_collect_failure_does_not_leave_an_approved_payment_without_posting(): void
    {
        $this->mock(AuditLogService::class)->shouldReceive('log')->once()->andThrow(new \RuntimeException('synthetic-collect-failure'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->admin)->post(route('cooperative.dues.mark-paid'), ['invoice_ids' => [$this->invoice->id], 'paid_at' => '2026-10-27']);
            $this->fail('Expected failed collect');
        } catch (\RuntimeException $exception) {
            $this->assertSame('synthetic-collect-failure', $exception->getMessage());
        }
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);
        $this->assertSame('UNPAID', $this->invoice->fresh()->status);
        $this->assertSame(0, CooperativePayment::query()->count());
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());
        $this->assertSame(0, CooperativeReceipt::query()->count());
    }
}
