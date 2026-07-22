<?php

namespace Database\Seeders;

use App\Enums\MemberStoreAccountStatus;
use App\Enums\MemberStoreDelegateStatus;
use App\Enums\MemberStoreFundingMethod;
use App\Enums\MemberStoreFundingStatus;
use App\Enums\MemberStoreLedgerEffect;
use App\Enums\MemberStoreLedgerEntryType;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreFundingRequest;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
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
        if (in_array((string) config('app.env'), ['production', 'staging'], true)) {
            throw new \LogicException('UiAuditSeeder is only available in testing or playwright environments.');
        }

        $this->call(RolePermissionSeeder::class);
        $organizations = $this->seedOrganizations();
        $users = $this->seedUsers($organizations['pusat']);
        $members = $this->seedMembers($organizations, $users);
        $this->seedStoreCredit($organizations, $users, $members);
        $this->seedPos();
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
        ];
        $members = [];

        foreach ($definitions as $key => [$memberNo, $name, $organization]) {
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
                    'status' => CooperativeMember::VALIDATION_ACTIVE,
                    'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                    'validated_at' => self::FIXED_DATE.' 09:00:00',
                    'validated_by' => $users['pengurus']->id,
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

    private function seedPos(): void
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
                ['pos_category_id' => $category->id, 'barcode' => null, 'name' => $name, 'image_path' => $imagePath, 'brand' => 'Kojaya Audit', 'variant' => null, 'unit' => 'pcs', 'rack_location' => 'A1', 'cost_price' => max($salePrice - 5000, 0), 'sale_price' => $salePrice, 'stock' => $stock, 'minimum_stock' => $minimumStock, 'is_active' => true, 'is_discontinued' => false],
            );
        }

        $source = public_path('images/logo.png');
        if (is_file($source)) {
            Storage::disk('public')->put('ui-audit-product.png', file_get_contents($source));
        }
    }
}
