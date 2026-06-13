<?php

namespace Database\Seeders;

use App\Enums\CooperativeShuPeriodStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuPeriod;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\User;
use App\Services\Cooperative\AnnualShuDistributionService;
use App\Services\Cooperative\CooperativePaymentService;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CooperativeSeeder extends Seeder
{
    public function run(): void
    {
        $headOffice = Organization::query()->updateOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => Organization::query()->where('code', 'KOP-001')->value('id') ?? (string) Str::uuid(),
                'name' => 'Koperasi Jaya Bersama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'parent_id' => null,
                'address' => 'Jalan Jaya Bersama No. 1, Jakarta',
                'phone' => '021-12345678',
                'email' => 'info@koperasijayabersama.id',
                'is_active' => true,
                'latitude' => '-6.200000',
                'longitude' => '106.816666',
                'radius' => 200,
            ],
        );

        Organization::query()->updateOrCreate(
            ['code' => 'KBU-001'],
            [
                'id' => Organization::query()->where('code', 'KBU-001')->value('id') ?? (string) Str::uuid(),
                'parent_id' => $headOffice->id,
                'name' => 'PT Koperasi Berkah Usaha',
                'level' => 'L1',
                'type' => 'BRANCH',
                'address' => 'Jl. Berkah Usaha No. 8, Jakarta',
                'phone' => '021-111111',
                'email' => 'operasional@koperasiberkahusaha.id',
                'is_active' => true,
                'latitude' => '-6.175392',
                'longitude' => '106.827153',
                'radius' => 150,
            ],
        );

        $pokok = CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'POKOK'],
            ['name' => 'Simpanan Pokok', 'category' => 'POKOK', 'default_amount' => 200000, 'frequency' => 'ONCE', 'is_active' => true],
        );
        $wajib = CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'WAJIB'],
            ['name' => 'Simpanan Wajib', 'category' => 'WAJIB', 'default_amount' => 100000, 'frequency' => 'MONTHLY', 'is_active' => true],
        );
        $sukarela = CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'SUKARELA'],
            ['name' => 'Simpanan Sukarela', 'category' => 'SUKARELA', 'default_amount' => 0, 'frequency' => 'ADHOC', 'is_active' => true],
        );
        CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'KHUSUS'],
            ['name' => 'Simpanan Khusus', 'category' => 'KHUSUS', 'default_amount' => 0, 'frequency' => 'ADHOC', 'is_active' => true],
        );

        $pengurus = User::query()->where('email', 'admin@erp.com')->first();
        $adminKop = User::query()->updateOrCreate(
            ['email' => 'admin.kop@koperasijayabersama.id'],
            [
                'name' => 'Admin Koperasi',
                'password' => 'password',
                'organization_id' => $headOffice->id,
            ],
        );
        $adminKop->forceFill(['email_verified_at' => now()])->save();
        $adminKop->syncRoles(['Admin Koperasi']);

        $kasir = User::query()->updateOrCreate(
            ['email' => 'kasir@koperasijayabersama.id'],
            [
                'name' => 'Kasir POS Koperasi',
                'password' => 'password',
                'organization_id' => $headOffice->id,
            ],
        );
        $kasir->forceFill(['email_verified_at' => now()])->save();
        $kasir->syncRoles(['Kasir Koperasi']);

        $members = $this->seedMembers($headOffice, $pokok, $wajib, $sukarela, $pengurus);
        $products = $this->seedPosInventory();
        $this->seedPosTransactions($members, $products, $kasir);

        if (! CooperativeShuPeriod::query()->where('year', 2025)->whereIn('status', [CooperativeShuPeriodStatus::Closed->value, CooperativeShuPeriodStatus::ClosedRevised->value])->exists()) {
            app(AnnualShuDistributionService::class)->close(
                2025,
                12500000,
                null,
                $pengurus,
            );
        }

        $this->command?->info('Seeded demo koperasi: 2 organisasi, anggota, iuran, POS, poin POS, dan SHU tahunan.');
    }

    /**
     * @return array<int, CooperativeMember>
     */
    private function seedMembers(
        Organization $headOffice,
        CooperativeContributionType $pokok,
        CooperativeContributionType $wajib,
        CooperativeContributionType $sukarela,
        ?User $pengurus,
    ): array {
        $names = [
            ['Andi Prasetyo', '2022-01-15', 12],
            ['Siti Rahmawati', '2022-04-10', 12],
            ['Budi Santoso', '2023-02-20', 11],
            ['Maya Lestari', '2023-08-05', 10],
            ['Dian Purnama', '2024-01-12', 9],
            ['Rizky Maulana', '2024-06-18', 8],
            ['Nina Kartika', '2025-01-09', 7],
            ['Fahmi Hidayat', '2025-07-22', 5],
            ['Laras Wulandari', '2026-01-11', 4],
            ['Teguh Saputra', '2026-03-04', 2],
        ];

        $members = [];

        foreach ($names as $index => [$name, $joinedAt, $paidMonths]) {
            $number = $index + 1;
            $member = CooperativeMember::query()->updateOrCreate(
                ['member_no' => 'KOP-'.Carbon::parse($joinedAt)->format('Y').'-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT)],
                [
                    'organization_id' => $headOffice->id,
                    'name' => $name,
                    'email' => 'anggota'.$number.'@koperasijayabersama.id',
                    'phone' => '08123456'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                    'identity_number' => '317400000000'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                    'address' => 'Alamat anggota demo '.$number,
                    'tanggal_aktif' => $joinedAt,
                    'joined_at' => $joinedAt,
                    'status' => 'ACTIVE',
                    'credit_limit' => 500000,
                    'credit_term_days' => 30,
                ],
            );

            $this->seedInvoicePayment($member, $pokok, Carbon::parse($joinedAt)->format('Y-m'), (float) $pokok->default_amount, true, 'POKOK-'.$number, $pengurus);

            foreach (range(1, 12) as $month) {
                $period = '2025-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT);
                $this->seedInvoicePayment($member, $wajib, $period, (float) $wajib->default_amount, $month <= $paidMonths, 'WAJIB-'.$number.'-'.$period, $pengurus);
            }

            foreach (range(1, 5) as $month) {
                $period = '2026-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT);
                $this->seedInvoicePayment($member, $wajib, $period, (float) $wajib->default_amount, $month <= min($paidMonths, 5), 'WAJIB-'.$number.'-'.$period, $pengurus);
            }

            if ($number <= 4) {
                $this->seedInvoicePayment($member, $sukarela, '2026-05', 25000 * $number, true, 'SUKARELA-'.$number, $pengurus);
            }

            $members[] = $member;
        }

        return $members;
    }

    private function seedInvoicePayment(
        CooperativeMember $member,
        CooperativeContributionType $type,
        string $period,
        float $amount,
        bool $paid,
        string $reference,
        ?User $pengurus,
    ): void {
        $memberStartPeriod = Carbon::parse($member->tanggal_aktif ?: $member->joined_at ?: now())->startOfMonth();
        if (Carbon::parse($period.'-01')->startOfMonth()->lt($memberStartPeriod)) {
            return;
        }

        $invoice = CooperativeDuesInvoice::query()->updateOrCreate(
            [
                'cooperative_member_id' => $member->id,
                'cooperative_contribution_type_id' => $type->id,
                'period' => $period,
            ],
            [
                'amount' => $amount,
                'paid_amount' => 0,
                'due_date' => Carbon::parse($period.'-10')->toDateString(),
                'status' => 'UNPAID',
            ],
        );

        if (! $paid) {
            return;
        }

        $payment = CooperativePayment::query()->firstOrCreate(
            [
                'cooperative_member_id' => $member->id,
                'cooperative_dues_invoice_id' => $invoice->id,
                'reference_no' => 'DEMO-'.$reference,
            ],
            [
                'amount' => $amount,
                'payment_method' => 'CASH',
                'paid_at' => Carbon::parse($period.'-05')->toDateString(),
                'status' => 'APPROVED',
                'approved_at' => Carbon::parse($period.'-05 10:00:00'),
                'approved_by' => $pengurus?->id,
            ],
        );

        app(CooperativePaymentService::class)->approve($payment, $pengurus);
    }

    /**
     * @return array<string, PosProduct>
     */
    private function seedPosInventory(): array
    {
        $categories = [
            'sembako' => PosCategory::query()->updateOrCreate(['slug' => 'sembako'], ['name' => 'Sembako', 'is_active' => true]),
            'minuman' => PosCategory::query()->updateOrCreate(['slug' => 'minuman'], ['name' => 'Minuman', 'is_active' => true]),
            'atk' => PosCategory::query()->updateOrCreate(['slug' => 'atk'], ['name' => 'ATK & Kebutuhan Kantor', 'is_active' => true]),
        ];

        $products = [
            ['category' => 'sembako', 'sku' => 'POS-RICE-5KG', 'barcode' => '8997001000011', 'name' => 'Beras Premium 5kg', 'cost_price' => 68000, 'sale_price' => 79000, 'stock' => 80, 'minimum_stock' => 15],
            ['category' => 'sembako', 'sku' => 'POS-OIL-2L', 'barcode' => '8997001000028', 'name' => 'Minyak Goreng 2L', 'cost_price' => 31000, 'sale_price' => 38000, 'stock' => 100, 'minimum_stock' => 20],
            ['category' => 'sembako', 'sku' => 'POS-SUGAR-1KG', 'barcode' => '8997001000035', 'name' => 'Gula Pasir 1kg', 'cost_price' => 14500, 'sale_price' => 17500, 'stock' => 90, 'minimum_stock' => 20],
            ['category' => 'minuman', 'sku' => 'POS-WATER-600', 'barcode' => '8997001000042', 'name' => 'Air Mineral 600ml', 'cost_price' => 2200, 'sale_price' => 3500, 'stock' => 180, 'minimum_stock' => 40],
            ['category' => 'minuman', 'sku' => 'POS-TEA-350', 'barcode' => '8997001000059', 'name' => 'Teh Botol 350ml', 'cost_price' => 4200, 'sale_price' => 6500, 'stock' => 120, 'minimum_stock' => 30],
            ['category' => 'atk', 'sku' => 'POS-PEN-BLUE', 'barcode' => '8997001000066', 'name' => 'Pulpen Biru', 'cost_price' => 1800, 'sale_price' => 3500, 'stock' => 75, 'minimum_stock' => 20],
        ];

        $seeded = [];

        foreach ($products as $product) {
            $seeded[$product['sku']] = PosProduct::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'pos_category_id' => $categories[$product['category']]->id,
                    'barcode' => $product['barcode'],
                    'name' => $product['name'],
                    'cost_price' => $product['cost_price'],
                    'sale_price' => $product['sale_price'],
                    'stock' => $product['stock'],
                    'minimum_stock' => $product['minimum_stock'],
                    'is_active' => true,
                ],
            );
        }

        return $seeded;
    }

    /**
     * @param  array<int, CooperativeMember>  $members
     * @param  array<string, PosProduct>  $products
     */
    private function seedPosTransactions(array $members, array $products, User $kasir): void
    {
        $transactions = [
            ['date' => '2025-02-12 09:15:00', 'member' => 0, 'payment' => 'CASH', 'ref' => 'DEMO-POS-2025-001', 'items' => [['POS-RICE-5KG', 2], ['POS-OIL-2L', 2]]],
            ['date' => '2025-03-18 13:20:00', 'member' => 1, 'payment' => 'TRANSFER', 'ref' => 'DEMO-POS-2025-002', 'items' => [['POS-SUGAR-1KG', 4], ['POS-WATER-600', 12]]],
            ['date' => '2025-05-10 17:35:00', 'member' => 2, 'payment' => 'CASH', 'ref' => 'DEMO-POS-2025-003', 'items' => [['POS-OIL-2L', 3], ['POS-TEA-350', 8]]],
            ['date' => '2025-08-22 10:05:00', 'member' => 0, 'payment' => 'MEMBER_CREDIT', 'ref' => 'DEMO-POS-2025-004', 'items' => [['POS-RICE-5KG', 1], ['POS-PEN-BLUE', 10]]],
            ['date' => '2025-11-06 15:50:00', 'member' => 4, 'payment' => 'QRIS', 'ref' => 'DEMO-POS-2025-005', 'items' => [['POS-RICE-5KG', 3], ['POS-SUGAR-1KG', 3]]],
            ['date' => '2026-01-15 11:10:00', 'member' => 5, 'payment' => 'CASH', 'ref' => 'DEMO-POS-2026-001', 'items' => [['POS-WATER-600', 24], ['POS-TEA-350', 12]]],
            ['date' => '2026-03-09 16:00:00', 'member' => 6, 'payment' => 'TRANSFER', 'ref' => 'DEMO-POS-2026-002', 'items' => [['POS-OIL-2L', 4], ['POS-SUGAR-1KG', 4]]],
            ['date' => '2026-05-01 08:45:00', 'member' => 8, 'payment' => 'CASH', 'ref' => 'DEMO-POS-2026-003', 'items' => [['POS-RICE-5KG', 2], ['POS-PEN-BLUE', 5]]],
            ['date' => '2026-05-02 14:25:00', 'member' => null, 'payment' => 'CASH', 'ref' => 'DEMO-POS-2026-004', 'items' => [['POS-WATER-600', 6]]],
        ];

        $service = app(PosTransactionService::class);

        foreach ($transactions as $transaction) {
            Carbon::setTestNow(Carbon::parse($transaction['date']));

            $service->create([
                'client_reference' => $transaction['ref'],
                'cooperative_member_id' => $transaction['member'] === null ? null : $members[$transaction['member']]->id,
                'payment_method' => $transaction['payment'],
                'items' => collect($transaction['items'])
                    ->map(fn (array $item): array => [
                        'pos_product_id' => $products[$item[0]]->id,
                        'quantity' => $item[1],
                    ])
                    ->all(),
            ], $kasir);
        }

        Carbon::setTestNow();
    }
}
