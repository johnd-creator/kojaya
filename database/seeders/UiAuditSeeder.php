<?php

namespace Database\Seeders;

use App\Enums\LoanStatus;
use App\Enums\MemberStoreAccountStatus;
use App\Enums\MemberStoreDelegateStatus;
use App\Enums\MemberStoreFundingMethod;
use App\Enums\MemberStoreFundingStatus;
use App\Enums\MemberStoreLedgerEffect;
use App\Enums\MemberStoreLedgerEntryType;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreFundingRequest;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PointTransaction;
use App\Models\PosCategory;
use App\Models\PosInventoryLocation;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\PosStockCount;
use App\Models\PosStockCountItem;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UiAuditSeeder extends Seeder
{
    private const PASSWORD = 'UiAudit!2026';

    private const FIXED_DATE = '2026-01-15';

    public function run(): void
    {
        if (! in_array((string) config('app.env'), ['testing', 'playwright'], true)) {
            throw new \LogicException('UiAuditSeeder is only available in testing or playwright environments.');
        }

        $this->call(RolePermissionSeeder::class);
        $organizations = $this->seedOrganizations();
        $users = $this->seedUsers($organizations['pusat']);
        $members = $this->seedMembers($organizations, $users);
        $this->seedStoreCredit($organizations, $users, $members);
        $this->seedPos($organizations['pusat']);
        $this->seedAdditionalFixtures($organizations, $users, $members);
        $this->seedCanonicalDuesInvoices($members);
    }

    /** @return array<string, Organization> */
    private function seedOrganizations(): array
    {
        $pusat = $this->organization('AUD-PUSAT', 'Koperasi Pusat', 'L0', 'HEAD_OFFICE');
        $suralaya = $this->organization('AUD-SURALAYA', 'Unit Suralaya', 'L1', 'BRANCH', $pusat);
        $jakarta = $this->organization('AUD-JAKARTA', 'Unit Jakarta', 'L1', 'BRANCH', $pusat);

        return compact('pusat', 'suralaya', 'jakarta');
    }

    private function organization(string $code, string $name, string $level, string $type, ?Organization $parent = null): Organization
    {
        return Organization::query()->updateOrCreate(
            ['code' => $code],
            [
                'id' => Organization::query()->where('code', $code)->value('id') ?? (string) Str::uuid(),
                'parent_id' => $parent?->id,
                'name' => $name,
                'level' => $level,
                'type' => $type,
                'address' => 'Alamat audit UI '.$name,
                'phone' => '0215550100',
                'email' => strtolower($code).'@kojaya.test',
                'is_active' => true,
            ],
        );
    }

    /** @return array<string, User> */
    private function seedUsers(Organization $organization): array
    {
        $definitions = [
            'system-admin' => ['ui.system@kojaya.test', 'UI System Admin', 'System Admin'],
            'pengurus' => ['ui.pengurus@kojaya.test', 'UI Pengurus Koperasi', 'Pengurus Koperasi'],
            'manajer' => ['ui.manajer@kojaya.test', 'UI Manajer Koperasi', 'Manajer Koperasi'],
            'admin' => ['ui.admin@kojaya.test', 'UI Admin Koperasi', 'Admin Koperasi'],
            'kasir' => ['ui.kasir@kojaya.test', 'UI Kasir Koperasi', 'Kasir Koperasi'],
            'anggota' => ['ui.anggota@kojaya.test', 'UI Anggota', 'Anggota'],
        ];
        $users = [];

        foreach ($definitions as $key => [$email, $name, $role]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => self::PASSWORD, 'organization_id' => $organization->id],
            );
            $user->forceFill(['email_verified_at' => self::FIXED_DATE.' 09:00:00'])->save();
            $user->syncRoles([$role]);
            $users[$key] = $user;
        }

        return $users;
    }

    /** @param array<string, Organization> $organizations @param array<string, User> $users @return array<string, CooperativeMember> */
    private function seedMembers(array $organizations, array $users): array
    {
        $definitions = [
            'positive' => ['AUD-001', 'UI Audit Positif', $organizations['pusat']],
            'zero' => ['AUD-002', 'UI Audit Saldo Nol', $organizations['pusat']],
            'negative' => ['AUD-003', 'UI Audit Saldo Negatif', $organizations['pusat']],
            'suspended' => ['AUD-004', 'UI Audit Suspended', $organizations['pusat']],
            'empty-ledger' => ['AUD-005', 'UI Audit Empty Ledger', $organizations['pusat']],
            'long-name' => ['AUD-006', 'UI Audit Anggota dengan Nama Sangat Panjang untuk Pengujian Responsif dan Wrapping', $organizations['suralaya']],
            'no-account' => ['AUD-007', 'UI Audit Belum Memiliki Akun', $organizations['jakarta']],
            'other-org' => ['AUD-008', 'UI Audit Organisasi Lain', $organizations['jakarta']],
            'pending-review' => ['AUD-009', 'UI Audit Calon Anggota Pending', $organizations['pusat']],
            'revision' => ['AUD-010', 'UI Audit Anggota Perlu Revisi', $organizations['pusat']],
        ];
        $members = [];

        foreach ($definitions as $key => [$memberNo, $name, $organization]) {
            $status = match ($key) {
                'pending-review', 'revision' => CooperativeMember::VALIDATION_PENDING,
                default => CooperativeMember::VALIDATION_ACTIVE,
            };
            $validationStatus = match ($key) {
                'pending-review' => CooperativeMember::VALIDATION_PENDING_REVIEW,
                'revision' => CooperativeMember::VALIDATION_REVISION,
                default => CooperativeMember::VALIDATION_ACTIVE,
            };
            $member = CooperativeMember::withTrashed()->updateOrCreate(
                ['member_no' => $memberNo],
                [
                    'organization_id' => $organization->id,
                    'user_id' => $key === 'positive' ? $users['anggota']->id : null,
                    'no_anggota' => $memberNo,
                    'name' => $name,
                    'email' => strtolower($memberNo).'@kojaya.test',
                    'phone' => '081200000'.substr($memberNo, -1),
                    'address' => 'Alamat anggota audit UI',
                    'joined_at' => self::FIXED_DATE,
                    'tanggal_aktif' => self::FIXED_DATE,
                    'status' => $status,
                    'validation_status' => $validationStatus,
                    'validated_at' => $validationStatus === CooperativeMember::VALIDATION_ACTIVE ? self::FIXED_DATE.' 09:00:00' : null,
                    'validated_by' => $validationStatus === CooperativeMember::VALIDATION_ACTIVE ? $users['pengurus']->id : null,
                    'profile_completed_at' => self::FIXED_DATE.' 09:00:00',
                    'onboarding_submitted_at' => self::FIXED_DATE.' 09:00:00',
                    'credit_limit' => 500000,
                    'outstanding_balance' => 0,
                ],
            );
            $member->restore();
            $members[$key] = $member;
        }

        return $members;
    }

    /** @param array<string, Organization> $organizations @param array<string, User> $users @param array<string, CooperativeMember> $members */
    private function seedStoreCredit(array $organizations, array $users, array $members): void
    {
        $accounts = [
            'positive' => $this->account($members['positive'], 150000, 500000, MemberStoreAccountStatus::Active),
            'zero' => $this->account($members['zero'], 0, 250000, MemberStoreAccountStatus::Active),
            'negative' => $this->account($members['negative'], -75000, 100000, MemberStoreAccountStatus::Active),
            'suspended' => $this->account($members['suspended'], 20000, 100000, MemberStoreAccountStatus::Suspended),
            'empty-ledger' => $this->account($members['empty-ledger'], 0, 100000, MemberStoreAccountStatus::Active),
            'long-name' => $this->account($members['long-name'], 95000, 120000, MemberStoreAccountStatus::Active),
            'other-org' => $this->account($members['other-org'], 30000, 200000, MemberStoreAccountStatus::Active),
        ];

        $this->ledger($accounts['positive'], 150000, 0, 150000, MemberStoreLedgerEffect::Credit, 'Saldo awal positif', 'positive-opening', $users['pengurus']);
        $this->ledger($accounts['negative'], 75000, 0, -75000, MemberStoreLedgerEffect::Debit, 'Utang pembelian dengan catatan panjang untuk audit keterbacaan dan detail ledger', 'negative-opening', $users['pengurus']);
        $this->ledger($accounts['long-name'], 95000, 0, 95000, MemberStoreLedgerEffect::Credit, 'Catatan transaksi yang cukup panjang untuk memastikan teks tidak merusak layout tabel dan detail akun.', 'long-opening', $users['pengurus']);
        $this->ledger($accounts['suspended'], 20000, 0, 20000, MemberStoreLedgerEffect::Credit, null, 'suspended-opening', $users['pengurus']);

        MemberStoreDelegate::query()->updateOrCreate(
            ['organization_id' => $organizations['pusat']->id, 'code' => 'AUD-DELEGATE-01'],
            ['account_id' => $accounts['positive']->id, 'user_id' => $users['anggota']->id, 'display_name' => 'Delegate Audit UI', 'per_transaction_limit' => 100000, 'daily_limit' => 300000, 'valid_from' => self::FIXED_DATE, 'expires_at' => '2026-12-31', 'status' => MemberStoreDelegateStatus::Active->value, 'created_by' => $users['pengurus']->id],
        );

        MemberStoreFundingRequest::query()->updateOrCreate(
            ['idempotency_key' => 'ui-audit-transfer-pending'],
            ['account_id' => $accounts['positive']->id, 'organization_id' => $organizations['pusat']->id, 'method' => MemberStoreFundingMethod::Transfer->value, 'amount' => 50000, 'status' => MemberStoreFundingStatus::Pending->value, 'bank_reference' => 'UI-AUDIT-BANK-001', 'submitted_by' => $users['anggota']->id],
        );
    }

    private function account(CooperativeMember $member, int $balance, int $creditLimit, MemberStoreAccountStatus $status): MemberStoreAccount
    {
        return MemberStoreAccount::query()->updateOrCreate(
            ['organization_id' => $member->organization_id, 'cooperative_member_id' => $member->id],
            ['balance' => $balance, 'credit_limit' => $creditLimit, 'status' => $status->value, 'opened_at' => self::FIXED_DATE.' 09:00:00', 'suspended_at' => $status === MemberStoreAccountStatus::Suspended ? self::FIXED_DATE.' 10:00:00' : null],
        );
    }

    private function ledger(MemberStoreAccount $account, int $amount, int $before, int $after, MemberStoreLedgerEffect $effect, ?string $reason, string $key, User $actor): void
    {
        MemberStoreLedgerEntry::query()->firstOrCreate(
            ['account_id' => $account->id, 'idempotency_key' => $key],
            ['organization_id' => $account->organization_id, 'entry_type' => $effect === MemberStoreLedgerEffect::Credit ? MemberStoreLedgerEntryType::OpeningBalance->value : MemberStoreLedgerEntryType::AdjustmentDebit->value, 'amount' => $amount, 'effect' => $effect->value, 'balance_before' => $before, 'balance_after' => $after, 'actor_user_id' => $actor->id, 'reason' => $reason, 'metadata' => ['source' => 'ui-audit-seeder'], 'occurred_at' => self::FIXED_DATE.' 09:15:00'],
        );
    }

    private function seedPos(Organization $organization): void
    {
        $category = PosCategory::query()->updateOrCreate(['slug' => 'ui-audit-grocery'], ['name' => 'Audit Grocery', 'is_active' => true]);
        $products = [
            ['UI-AUD-001', 'Beras Audit 5kg', 78000, 24, 4, null],
            ['UI-AUD-002', 'Produk Tanpa Gambar', 12000, 2, 5, null],
            ['UI-AUD-003', 'Produk Dengan Gambar Lokal', 25000, 8, 3, 'ui-audit-product.png'],
            ['UI-AUD-004', 'Produk dengan Nama Sangat Panjang untuk Uji Kartu POS', 35000, 1, 5, null],
        ];

        foreach ($products as [$sku, $name, $salePrice, $stock, $minimumStock, $imagePath]) {
            PosProduct::query()->updateOrCreate(
                ['sku' => $sku],
                ['organization_id' => $organization->id, 'pos_category_id' => $category->id, 'barcode' => null, 'name' => $name, 'image_path' => $imagePath, 'brand' => 'Kojaya Audit', 'variant' => null, 'unit' => 'pcs', 'rack_location' => 'A1', 'cost_price' => max($salePrice - 5000, 0), 'sale_price' => $salePrice, 'stock' => $stock, 'minimum_stock' => $minimumStock, 'is_active' => true, 'is_discontinued' => false],
            );
        }

        $source = public_path('images/logo.png');
        if (is_file($source)) {
            Storage::disk('public')->put('ui-audit-product.png', file_get_contents($source));
        }
    }

    /** @param array<string, Organization> $organizations @param array<string, User> $users @param array<string, CooperativeMember> $members */
    private function seedAdditionalFixtures(array $organizations, array $users, array $members): void
    {
        $loanType = LoanType::query()->updateOrCreate(
            ['code' => 'AUD-LOAN-TYPE'],
            ['name' => 'Pinjaman Audit UI', 'description' => 'Fixture pinjaman deterministik untuk audit visual.', 'interest_rate' => 1.5, 'admin_fee' => 25000, 'late_fee_per_day' => 2500, 'min_amount' => 500000, 'max_amount' => 25000000, 'min_term_months' => 3, 'max_term_months' => 24, 'is_active' => true],
        );

        Loan::query()->updateOrCreate(
            ['reference_no' => 'UI-AUDIT-LOAN-001'],
            ['cooperative_member_id' => $members['positive']->id, 'organization_id' => $organizations['pusat']->id, 'loan_type_id' => $loanType->id, 'user_id' => $users['anggota']->id, 'principal_amount' => 3000000, 'interest_rate' => 1.5, 'admin_fee' => 25000, 'late_fee_per_day' => 2500, 'term_months' => 6, 'installment_amount' => 537500, 'total_interest_amount' => 270000, 'total_amount' => 3295000, 'outstanding_amount' => 3295000, 'applied_at' => self::FIXED_DATE, 'first_due_date' => '2026-02-15', 'status' => LoanStatus::Applied, 'purpose' => 'Fixture pinjaman untuk audit visual.', 'notes' => 'Data deterministik.'],
        );

        $product = PosProduct::query()->where('sku', 'UI-AUD-001')->firstOrFail();
        $transaction = PosTransaction::query()->updateOrCreate(
            ['transaction_no' => 'UI-AUDIT-POS-001'],
            ['organization_id' => $organizations['pusat']->id, 'cooperative_member_id' => $members['positive']->id, 'cashier_id' => $users['kasir']->id, 'subtotal' => 78000, 'discount_amount' => 0, 'total_amount' => 78000, 'gross_profit' => 10000, 'cash_received' => 100000, 'cash_change' => 22000, 'status' => 'COMPLETED', 'sold_at' => self::FIXED_DATE.' 10:00:00'],
        );
        PosTransactionItem::query()->updateOrCreate(
            ['pos_transaction_id' => $transaction->id, 'pos_product_id' => $product->id],
            ['quantity' => 1, 'unit_price' => 78000, 'cost_price' => 68000, 'unit_profit' => 10000, 'line_total' => 78000, 'line_profit' => 10000],
        );
        PosPayment::query()->updateOrCreate(
            ['pos_transaction_id' => $transaction->id, 'payment_method' => 'CASH'],
            ['amount' => 78000, 'reference_no' => 'UI-AUDIT-CASH-001'],
        );

        $location = PosInventoryLocation::query()->updateOrCreate(
            ['code' => 'AUD-MAIN'],
            ['name' => 'Gudang Audit UI', 'location_type' => 'STORE', 'address' => 'Unit audit', 'is_active' => true, 'is_default' => true],
        );
        $count = PosStockCount::query()->updateOrCreate(
            ['count_no' => 'UI-AUDIT-COUNT-001'],
            ['pos_inventory_location_id' => $location->id, 'requested_by' => $users['admin']->id, 'counted_at' => self::FIXED_DATE, 'notes' => 'Stock opname fixture deterministik.', 'status' => PosStockCount::STATUS_DRAFT],
        );
        PosStockCountItem::query()->updateOrCreate(
            ['pos_stock_count_id' => $count->id, 'pos_product_id' => $product->id],
            ['system_qty' => 24, 'counted_qty' => 23, 'difference' => -1, 'notes' => 'Selisih fixture audit.'],
        );

        $reward = Reward::query()->updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000000021'],
            ['organization_id' => $organizations['pusat']->id, 'name' => 'Voucher Audit UI', 'category' => 'DISKON', 'description' => 'Reward deterministik untuk audit visual.', 'points_required' => 100, 'stock' => 10, 'valid_until' => '2026-12-31', 'is_active' => true],
        );
        $pointTransaction = PointTransaction::query()->updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000000022'],
            ['cooperative_member_id' => $members['positive']->id, 'transaction_type' => 'EARNED', 'points' => 250, 'balance_before' => 0, 'balance_after' => 250, 'reference_number' => 'UI-AUDIT-POINT-001', 'description' => 'Poin fixture audit.', 'posted_at' => self::FIXED_DATE, 'expires_at' => '2026-12-31'],
        );
        RewardRedemption::query()->updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000000023'],
            ['reward_id' => $reward->id, 'cooperative_member_id' => $members['positive']->id, 'point_transaction_id' => $pointTransaction->id, 'quantity' => 1, 'points_used' => 100, 'delivery_address' => 'Alamat audit UI', 'status' => 'PENDING', 'redeemed_at' => self::FIXED_DATE.' 11:00:00'],
        );

        $wajib = CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'WAJIB'],
            ['name' => 'Simpanan Wajib', 'category' => 'WAJIB', 'default_amount' => 100000, 'frequency' => 'MONTHLY', 'is_active' => true],
        );
        $pokok = CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'POKOK'],
            ['name' => 'Simpanan Pokok', 'category' => 'POKOK', 'default_amount' => 200000, 'frequency' => 'ONCE', 'is_active' => true],
        );
        CooperativeDuesInvoice::query()->updateOrCreate(
            ['cooperative_member_id' => $members['positive']->id, 'cooperative_contribution_type_id' => $wajib->id, 'period' => '2026-01'],
            ['amount' => 100000, 'paid_amount' => 0, 'due_date' => '2026-01-10', 'status' => 'UNPAID'],
        );
        CooperativeDuesInvoice::query()->updateOrCreate(
            ['cooperative_member_id' => $members['zero']->id, 'cooperative_contribution_type_id' => $wajib->id, 'period' => '2026-01'],
            ['amount' => 100000, 'paid_amount' => 25000, 'due_date' => '2026-01-10', 'status' => 'PARTIAL'],
        );
        CooperativePayment::query()->updateOrCreate(
            ['cooperative_member_id' => $members['positive']->id, 'reference_no' => 'UI-AUDIT-PAYMENT-001'],
            ['cooperative_contribution_type_id' => $pokok->id, 'amount' => 200000, 'payment_method' => 'TRANSFER', 'paid_at' => self::FIXED_DATE, 'status' => 'PENDING', 'notes' => 'Bukti pembayaran audit UI menunggu verifikasi.'],
        );
        CooperativePayment::query()->updateOrCreate(
            ['cooperative_member_id' => $members['zero']->id, 'reference_no' => 'UI-AUDIT-PAYMENT-002'],
            ['cooperative_contribution_type_id' => $wajib->id, 'amount' => 100000, 'payment_method' => 'QRIS', 'paid_at' => self::FIXED_DATE, 'status' => 'PENDING', 'notes' => 'Pembayaran QRIS audit UI menunggu verifikasi.'],
        );
    }

    /**
     * Seed canonical dues invoices so that every admin/operator/member screen reads
     * a deterministic data set without relying on lazy GET-side generation.
     *
     * @param  array<string, CooperativeMember>  $members
     */
    private function seedCanonicalDuesInvoices(array $members): void
    {
        $wajib = CooperativeContributionType::query()->where('code', 'WAJIB')->firstOrFail();
        $pokok = CooperativeContributionType::query()->where('code', 'POKOK')->firstOrFail();
        $period = '2026-01';
        $periodDate = \Carbon\CarbonImmutable::parse(self::FIXED_DATE)->startOfMonth();
        $dueDate = $periodDate->day(10)->toDateString();

        $activeMembers = CooperativeMember::query()
            ->active()
            ->orderBy('id')
            ->get();

        foreach ($activeMembers as $member) {
            CooperativeDuesInvoice::query()->firstOrCreate(
                [
                    'cooperative_member_id' => $member->id,
                    'cooperative_contribution_type_id' => $pokok->id,
                    'period' => $period,
                ],
                [
                    'amount' => $pokok->default_amount,
                    'paid_amount' => 0,
                    'due_date' => $dueDate,
                    'status' => 'UNPAID',
                ],
            );

            CooperativeDuesInvoice::query()->firstOrCreate(
                [
                    'cooperative_member_id' => $member->id,
                    'cooperative_contribution_type_id' => $wajib->id,
                    'period' => $period,
                ],
                [
                    'amount' => $wajib->default_amount,
                    'paid_amount' => 0,
                    'due_date' => $dueDate,
                    'status' => 'UNPAID',
                ],
            );
        }

        // Apply deterministic status overrides so the screens show varied statuses.
        CooperativeDuesInvoice::query()->updateOrCreate(
            ['cooperative_member_id' => $members['positive']->id, 'cooperative_contribution_type_id' => $wajib->id, 'period' => $period],
            ['amount' => 100000, 'paid_amount' => 0, 'due_date' => '2026-01-10', 'status' => 'UNPAID'],
        );
        CooperativeDuesInvoice::query()->updateOrCreate(
            ['cooperative_member_id' => $members['zero']->id, 'cooperative_contribution_type_id' => $wajib->id, 'period' => $period],
            ['amount' => 100000, 'paid_amount' => 25000, 'due_date' => '2026-01-10', 'status' => 'PARTIAL'],
        );
    }
}
