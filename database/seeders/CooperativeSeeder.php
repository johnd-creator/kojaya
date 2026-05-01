<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Services\Cooperative\CooperativePaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CooperativeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $headOffice = Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => Str::uuid(),
                'name' => 'Koperasi Utama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'parent_id' => null,
                'address' => 'Jalan Koperasi No. 1, Jakarta',
                'phone' => '021-12345678',
                'email' => 'info@koperasi.id',
                'is_active' => true,
            ]
        );

        // Create 5 Subsidiaries (Anak Koperasi)
        $subsidiaries = [
            [
                'code' => 'KOP-101',
                'name' => 'Anak Koperasi Jakarta',
                'address' => 'Jl. Jakarta No. 1',
                'phone' => '021-111111',
                'email' => 'jakarta@koperasi.id',
            ],
            [
                'code' => 'KOP-102',
                'name' => 'Anak Koperasi Bandung',
                'address' => 'Jl. Bandung No. 1',
                'phone' => '022-222222',
                'email' => 'bandung@koperasi.id',
            ],
            [
                'code' => 'KOP-103',
                'name' => 'Anak Koperasi Semarang',
                'address' => 'Jl. Semarang No. 1',
                'phone' => '024-333333',
                'email' => 'semarang@koperasi.id',
            ],
            [
                'code' => 'KOP-104',
                'name' => 'Anak Koperasi Surabaya',
                'address' => 'Jl. Surabaya No. 1',
                'phone' => '031-444444',
                'email' => 'surabaya@koperasi.id',
            ],
            [
                'code' => 'KOP-105',
                'name' => 'Anak Koperasi Medan',
                'address' => 'Jl. Medan No. 1',
                'phone' => '061-555555',
                'email' => 'medan@koperasi.id',
            ],
        ];

        foreach ($subsidiaries as $subsidiary) {
            Organization::query()->firstOrCreate(
                ['code' => $subsidiary['code']],
                [
                    'id' => Str::uuid(),
                    'name' => $subsidiary['name'],
                    'level' => 'L1',
                    'type' => 'BRANCH',
                    'parent_id' => $headOffice->id,
                    'address' => $subsidiary['address'],
                    'phone' => $subsidiary['phone'],
                    'email' => $subsidiary['email'],
                    'is_active' => true,
                ]
            );
        }

        $pokok = CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'POKOK'],
            ['name' => 'Simpanan Pokok', 'category' => 'POKOK', 'default_amount' => 100000, 'frequency' => 'ONCE', 'is_active' => true],
        );
        $wajib = CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'WAJIB'],
            ['name' => 'Simpanan Wajib', 'category' => 'WAJIB', 'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true],
        );
        CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'SUKARELA'],
            ['name' => 'Simpanan Sukarela', 'category' => 'SUKARELA', 'default_amount' => 0, 'frequency' => 'ADHOC', 'is_active' => true],
        );

        for ($i = 1; $i <= 10; $i++) {
            $member = CooperativeMember::query()->updateOrCreate(
                ['member_no' => 'KOP-2026-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)],
                [
                    'organization_id' => $headOffice->id,
                    'name' => 'Anggota Demo '.$i,
                    'email' => "anggota{$i}@koperasi.id",
                    'phone' => '08123456'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'identity_number' => '317400000000'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'address' => 'Alamat anggota demo '.$i,
                    'joined_at' => now()->startOfYear()->addDays($i)->toDateString(),
                    'status' => 'ACTIVE',
                ],
            );

            $invoice = CooperativeDuesInvoice::query()->updateOrCreate(
                [
                    'cooperative_member_id' => $member->id,
                    'cooperative_contribution_type_id' => $wajib->id,
                    'period' => now()->format('Y-m'),
                ],
                [
                    'amount' => $wajib->default_amount,
                    'paid_amount' => 0,
                    'due_date' => now()->startOfMonth()->day(10)->toDateString(),
                    'status' => 'UNPAID',
                ],
            );

            if ($i <= 5) {
                $payment = CooperativePayment::query()->firstOrCreate(
                    [
                        'cooperative_member_id' => $member->id,
                        'cooperative_dues_invoice_id' => $invoice->id,
                        'reference_no' => 'DEMO-PAY-'.$i,
                    ],
                    [
                        'amount' => $wajib->default_amount,
                        'payment_method' => 'CASH',
                        'paid_at' => now()->toDateString(),
                        'status' => 'APPROVED',
                        'approved_at' => now(),
                    ],
                );

                app(CooperativePaymentService::class)->approve($payment);
            }

            CooperativeDuesInvoice::query()->firstOrCreate(
                [
                    'cooperative_member_id' => $member->id,
                    'cooperative_contribution_type_id' => $pokok->id,
                    'period' => now()->format('Y-m'),
                ],
                [
                    'amount' => $pokok->default_amount,
                    'paid_amount' => 0,
                    'due_date' => now()->startOfMonth()->day(10)->toDateString(),
                    'status' => 'UNPAID',
                ],
            );
        }

        $category = PosCategory::query()->updateOrCreate(
            ['slug' => 'sembako'],
            ['name' => 'Sembako', 'is_active' => true],
        );

        $products = [
            ['sku' => 'POS-RICE-5KG', 'name' => 'Beras Premium 5kg', 'sale_price' => 78000, 'stock' => 25, 'minimum_stock' => 5],
            ['sku' => 'POS-OIL-2L', 'name' => 'Minyak Goreng 2L', 'sale_price' => 36000, 'stock' => 40, 'minimum_stock' => 10],
            ['sku' => 'POS-SUGAR-1KG', 'name' => 'Gula Pasir 1kg', 'sale_price' => 17000, 'stock' => 30, 'minimum_stock' => 8],
        ];

        foreach ($products as $product) {
            PosProduct::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    ...$product,
                    'pos_category_id' => $category->id,
                    'cost_price' => $product['sale_price'] * 0.85,
                    'barcode' => null,
                    'is_active' => true,
                ],
            );
        }

        $this->command->info('✅ Created 1 Head Office and 5 Subsidiaries for Koperasi.');
    }
}
