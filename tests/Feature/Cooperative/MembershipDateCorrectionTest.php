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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipDateCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Organization $otherOrganization;

    protected User $admin;

    protected User $otherAdmin;

    protected User $regularMember;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Koperasi Unit Satu',
        ]);

        $this->otherOrganization = Organization::factory()->create([
            'code' => 'KOP-002',
            'name' => 'Koperasi Unit Dua',
        ]);

        $this->admin = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Admin Koperasi Unit',
            'email' => 'admin.kop@kojaya.test',
        ]);
        $this->admin->assignRole('Admin Koperasi');
        $this->admin->givePermissionTo([
            'view_cooperative_member',
            'manage_cooperative_member',
        ]);

        $this->otherAdmin = User::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Admin Unit Lain',
            'email' => 'admin.other@kojaya.test',
        ]);
        $this->otherAdmin->assignRole('Admin Koperasi');
        $this->otherAdmin->givePermissionTo([
            'view_cooperative_member',
            'manage_cooperative_member',
        ]);

        $this->regularMember = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Anggota Biasa',
            'email' => 'anggota@kojaya.test',
        ]);
        $this->regularMember->assignRole('Anggota');
    }

    public function test_authorized_operator_can_correct_membership_dates_with_reason(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => '001',
            'nama_anggota' => 'Budi Santoso',
            'name' => 'Budi Santoso',
            'joined_at' => '2024-01-15',
            'tanggal_aktif' => '2024-02-01',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Budi Santoso',
                'name' => 'Budi Santoso',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
                'joined_at' => '2023-11-01',
                'tanggal_aktif' => '2023-11-15',
                'correction_reason' => 'Koreksi tanggal penetapan SK keanggotaan',
            ]);

        $response->assertRedirect(route('cooperative.members.index'));
        $response->assertSessionHas('success');

        $member->refresh();
        $this->assertSame('2023-11-01', $member->joined_at?->toDateString());
        $this->assertSame('2023-11-15', $member->tanggal_aktif?->toDateString());

        // Assert audit log exists
        $audit = AuditLog::query()
            ->where('action', 'member.membership_dates.corrected')
            ->where('module', 'cooperative.member')
            ->where('subject_id', $member->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($this->admin->id, $audit->user_id);
        $this->assertSame('Koreksi tanggal penetapan SK keanggotaan', $audit->reason);
        $this->assertSame('2024-01-15', $audit->old_values['joined_at']);
        $this->assertSame('2024-02-01', $audit->old_values['tanggal_aktif']);
        $this->assertSame('2023-11-01', $audit->new_values['joined_at']);
        $this->assertSame('2023-11-15', $audit->new_values['tanggal_aktif']);
    }

    public function test_updating_member_without_date_changes_does_not_require_reason_and_does_not_log_date_audit(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => '002',
            'nama_anggota' => 'Siti Nurhaliza',
            'name' => 'Siti Nurhaliza',
            'joined_at' => '2024-01-10',
            'tanggal_aktif' => '2024-01-10',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'P',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Siti Nurhaliza Updated',
                'name' => 'Siti Nurhaliza Updated',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'P',
                'kategori' => 'IP',
                'autodebet' => 'MANUAL',
                'joined_at' => '2024-01-10',
                'tanggal_aktif' => '2024-01-10',
            ]);

        $response->assertRedirect(route('cooperative.members.index'));
        $this->assertSame('Siti Nurhaliza Updated', $member->refresh()->name);

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'member.membership_dates.corrected',
            'subject_id' => $member->id,
        ]);
    }

    public function test_date_changes_without_reason_or_short_reason_are_rejected(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'joined_at' => '2024-01-10',
            'tanggal_aktif' => '2024-01-10',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ]);

        // 1. Missing reason
        $response1 = $this->actingAs($this->admin)
            ->from(route('cooperative.members.edit', $member))
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => $member->name,
                'name' => $member->name,
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
                'joined_at' => '2023-01-10',
                'tanggal_aktif' => '2023-01-10',
            ]);

        $response1->assertRedirect(route('cooperative.members.edit', $member));
        $response1->assertSessionHasErrors(['correction_reason']);

        // 2. Short reason (< 5 characters)
        $response2 = $this->actingAs($this->admin)
            ->from(route('cooperative.members.edit', $member))
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => $member->name,
                'name' => $member->name,
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
                'joined_at' => '2023-01-10',
                'tanggal_aktif' => '2023-01-10',
                'correction_reason' => 'Fix',
            ]);

        $response2->assertRedirect(route('cooperative.members.edit', $member));
        $response2->assertSessionHasErrors(['correction_reason']);
    }

    public function test_future_dates_are_rejected(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'joined_at' => '2024-01-10',
            'tanggal_aktif' => '2024-01-10',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ]);

        $tomorrow = now()->addDay()->toDateString();

        // 1. Future joined_at
        $response1 = $this->actingAs($this->admin)
            ->from(route('cooperative.members.edit', $member))
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => $member->name,
                'name' => $member->name,
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
                'joined_at' => $tomorrow,
                'tanggal_aktif' => $tomorrow,
                'correction_reason' => 'Perubahan tanggal ke masa depan',
            ]);

        $response1->assertRedirect(route('cooperative.members.edit', $member));
        $response1->assertSessionHasErrors(['joined_at', 'tanggal_aktif']);
    }

    public function test_tanggal_aktif_earlier_than_joined_at_is_rejected(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'joined_at' => '2024-01-10',
            'tanggal_aktif' => '2024-01-10',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('cooperative.members.edit', $member))
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => $member->name,
                'name' => $member->name,
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
                'joined_at' => '2024-05-01',
                'tanggal_aktif' => '2024-01-01',
                'correction_reason' => 'Perubahan tanggal aktif sebelum gabung',
            ]);

        $response->assertRedirect(route('cooperative.members.edit', $member));
        $response->assertSessionHasErrors(['tanggal_aktif']);
    }

    public function test_unauthorized_user_and_foreign_tenant_are_forbidden(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'joined_at' => '2024-01-10',
            'tanggal_aktif' => '2024-01-10',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ]);

        // Regular member without manage_cooperative_member permission
        $this->actingAs($this->regularMember)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Unauthorized Edit',
                'name' => 'Unauthorized Edit',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
            ])
            ->assertForbidden();

        // Admin from another organization
        $this->actingAs($this->otherAdmin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Foreign Tenant Edit',
                'name' => 'Foreign Tenant Edit',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
            ])
            ->assertForbidden();
    }

    public function test_financial_safety_invariant_all_financial_records_and_balances_remain_untouched(): void
    {
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => '009',
            'nama_anggota' => 'Member Finansial',
            'name' => 'Member Finansial',
            'joined_at' => '2024-01-01',
            'tanggal_aktif' => '2024-01-01',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ]);

        $type = CooperativeContributionType::query()->firstOrCreate(
            ['code' => 'WAJIB'],
            [
                'name' => 'Simpanan Wajib',
                'category' => 'WAJIB',
                'default_amount' => 50000.0,
                'frequency' => 'MONTHLY',
                'is_active' => true,
            ]
        );

        // 1. Create financial records
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'invoice_number' => 'INV-2026-001',
            'period' => '2026-03',
            'amount' => 50000,
            'amount_paid' => 50000,
            'status' => 'PAID',
            'due_date' => '2026-04-10',
            'paid_at' => '2026-04-05 10:00:00',
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'reference_no' => 'PAY-2026-001',
            'payment_method' => 'MANUAL_TRANSFER',
            'amount' => 50000,
            'status' => 'COMPLETED',
            'paid_at' => '2026-04-05',
        ]);

        $ledgerEntry = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SAVING_DEPOSIT',
            'credit' => 50000,
            'debit' => 0,
            'period' => '2026-03',
            'description' => 'Simpanan Wajib 2026-03',
            'posted_at' => '2026-04-05 10:00:00',
        ]);

        $receipt = CooperativeReceipt::query()->create([
            'receipt_no' => 'RCP-2026-001',
            'cooperative_member_id' => $member->id,
            'cooperative_payment_id' => $payment->id,
            'pdf_path' => 'cooperative/receipts/test.pdf',
            'issued_at' => '2026-04-05 10:00:00',
        ]);

        // Capture initial snapshot
        $invoicesCountBefore = CooperativeDuesInvoice::count();
        $paymentsCountBefore = CooperativePayment::count();
        $ledgerCountBefore = CooperativeLedgerEntry::count();
        $receiptsCountBefore = CooperativeReceipt::count();

        $invoiceDataBefore = $invoice->fresh()->toArray();
        $paymentDataBefore = $payment->fresh()->toArray();
        $ledgerDataBefore = $ledgerEntry->fresh()->toArray();
        $receiptDataBefore = $receipt->fresh()->toArray();

        // 2. Perform membership date correction
        $response = $this->actingAs($this->admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Member Finansial',
                'name' => 'Member Finansial',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
                'joined_at' => '2023-06-01',
                'tanggal_aktif' => '2023-06-15',
                'correction_reason' => 'Perbaikan data historis tanggal bergabung anggota',
            ]);

        $response->assertRedirect(route('cooperative.members.index'));
        $response->assertSessionHas('success');

        // 3. Assert Financial Invariant: completely unchanged
        $this->assertSame($invoicesCountBefore, CooperativeDuesInvoice::count());
        $this->assertSame($paymentsCountBefore, CooperativePayment::count());
        $this->assertSame($ledgerCountBefore, CooperativeLedgerEntry::count());
        $this->assertSame($receiptsCountBefore, CooperativeReceipt::count());

        $this->assertEquals($invoiceDataBefore, $invoice->fresh()->toArray());
        $this->assertEquals($paymentDataBefore, $payment->fresh()->toArray());
        $this->assertEquals($ledgerDataBefore, $ledgerEntry->fresh()->toArray());
        $this->assertEquals($receiptDataBefore, $receipt->fresh()->toArray());

        // 4. Assert Member Status remains ACTIVE
        $this->assertSame('ACTIVE', $member->fresh()->status);
        $this->assertSame('2023-06-01', $member->fresh()->joined_at?->toDateString());
        $this->assertSame('2023-06-15', $member->fresh()->tanggal_aktif?->toDateString());
    }
}
