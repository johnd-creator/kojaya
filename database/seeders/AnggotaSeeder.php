<?php

namespace Database\Seeders;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativePaymentService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AnggotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Guarded strictly for local development and test environments.
     */
    public function run(): void
    {
        if (! in_array((string) config('app.env'), ['local', 'testing', 'playwright'], true)) {
            throw new \LogicException('AnggotaSeeder is only available in local, testing, or playwright environments.');
        }

        $organization = Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Koperasi Jaya Bersama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'is_active' => true,
            ]
        );
        $pokok = CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'POKOK'],
            ['name' => 'Simpanan Pokok', 'category' => 'POKOK', 'default_amount' => 200000, 'frequency' => 'ONCE', 'is_active' => true],
        );
        $wajib = CooperativeContributionType::query()->updateOrCreate(
            ['code' => 'WAJIB'],
            ['name' => 'Simpanan Wajib', 'category' => 'WAJIB', 'default_amount' => 100000, 'frequency' => 'MONTHLY', 'is_active' => true],
        );
        $pengurus = User::query()->where('email', 'admin@erp.com')->first();
        $adminKop = User::query()->where('email', 'admin.kop@koperasijayabersama.id')->first();

        foreach ($this->rows() as $row) {
            $namaAnggota = $row['nama_anggota'];
            $noRekening = strtoupper((string) ($row['no_rekening'] ?? ''));

            $member = CooperativeMember::withTrashed()->updateOrCreate(
                ['no_anggota' => $row['no_anggota']],
                [
                    ...$row,
                    'organization_id' => $organization->id,
                    'member_no' => $row['no_anggota'],
                    'name' => rtrim(rtrim($namaAnggota, '*')),
                    'phone' => $row['no_telp'],
                    'joined_at' => $row['tanggal_aktif'],
                    'jenis_anggota' => str_ends_with($namaAnggota, '*') ? 'ALB' : $row['jenis_anggota'],
                    'status' => 'ACTIVE',
                    'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
                    'validated_at' => now(),
                    'validated_by' => $pengurus?->id,
                    'validation_notes' => 'Demo anggota sudah disetujui pengurus.',
                    'admin_validated_at' => now(),
                    'admin_validated_by' => $adminKop?->id,
                    'admin_validation_notes' => 'Demo anggota sudah diverifikasi admin koperasi.',
                    'profile_completed_at' => Carbon::parse($row['tanggal_aktif'])->endOfDay(),
                    'onboarding_submitted_at' => Carbon::parse($row['tanggal_aktif'])->endOfDay(),
                    'no_rekening' => $row['autodebet'] === 'MANUAL' || $noRekening === 'MANUAL' ? null : $row['no_rekening'],
                ]
            );
            $member->restore();

            $memberNumber = (int) substr((string) $row['no_anggota'], -3);
            $this->seedInvoicePayment($member, $pokok, Carbon::parse($row['tanggal_aktif'])->format('Y-m'), (float) $pokok->default_amount, (float) $pokok->default_amount, 'ANGGOTA-POKOK-'.$row['no_anggota'], $pengurus);
            $this->seedMonthlyMandatoryDues($member, $wajib, $memberNumber, $pengurus);
        }
    }

    /**
     * Namespaced demo member identities to prevent collision with production members.
     *
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            ['no_anggota' => 'DEMO-ANG-001', 'tanggal_aktif' => '2025-01-01', 'nama_anggota' => 'Ahmad Hidayat', 'status' => 'AKTIF', 'npwp' => '12.345.678.9-012.000', 'no_telp' => '081234560001', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'L', 'kategori' => 'IP', 'autodebet' => 'BNI', 'no_rekening' => '880100001'],
            ['no_anggota' => 'DEMO-ANG-002', 'tanggal_aktif' => '2025-02-01', 'nama_anggota' => 'Siti Aminah', 'status' => 'AKTIF', 'npwp' => null, 'no_telp' => '081234560002', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'P', 'kategori' => 'IP', 'autodebet' => 'BRI', 'no_rekening' => '330200002'],
            ['no_anggota' => 'DEMO-ANG-003', 'tanggal_aktif' => '2025-03-01', 'nama_anggota' => 'Budi Santoso*', 'status' => 'AKTIF', 'npwp' => '22.333.444.5-666.000', 'no_telp' => '081234560003', 'jenis_anggota' => 'ALB', 'jenis_kelamin' => 'L', 'kategori' => 'CDB', 'autodebet' => 'MANUAL', 'no_rekening' => null],
            ['no_anggota' => 'DEMO-ANG-004', 'tanggal_aktif' => '2025-04-01', 'nama_anggota' => 'Dewi Lestari', 'status' => 'AKTIF', 'npwp' => null, 'no_telp' => '081234560004', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'P', 'kategori' => 'KOP', 'autodebet' => 'MANUAL', 'no_rekening' => 'MANUAL'],
            ['no_anggota' => 'DEMO-ANG-005', 'tanggal_aktif' => '2025-05-01', 'nama_anggota' => 'Rudi Hartono', 'status' => 'AKTIF', 'npwp' => '33.444.555.6-777.000', 'no_telp' => null, 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'L', 'kategori' => 'IP', 'autodebet' => 'BNI', 'no_rekening' => '880100005'],
            ['no_anggota' => 'DEMO-ANG-006', 'tanggal_aktif' => '2025-06-01', 'nama_anggota' => 'Maya Putri*', 'status' => 'AKTIF', 'npwp' => null, 'no_telp' => '081234560006', 'jenis_anggota' => 'ALB', 'jenis_kelamin' => 'P', 'kategori' => 'CDB', 'autodebet' => 'BRI', 'no_rekening' => '330200006'],
            ['no_anggota' => 'DEMO-ANG-007', 'tanggal_aktif' => '2025-07-01', 'nama_anggota' => 'Agus Salim', 'status' => 'AKTIF', 'npwp' => '44.555.666.7-888.000', 'no_telp' => '081234560007', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'L', 'kategori' => 'KOP', 'autodebet' => 'MANUAL', 'no_rekening' => null],
            ['no_anggota' => 'DEMO-ANG-008', 'tanggal_aktif' => '2025-08-01', 'nama_anggota' => 'Nina Kartika', 'status' => 'AKTIF', 'npwp' => null, 'no_telp' => '081234560008', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'P', 'kategori' => 'IP', 'autodebet' => 'BNI', 'no_rekening' => '880100008'],
            ['no_anggota' => 'DEMO-ANG-009', 'tanggal_aktif' => '2025-09-01', 'nama_anggota' => 'Fajar Nugroho', 'status' => 'AKTIF', 'npwp' => '55.666.777.8-999.000', 'no_telp' => '081234560009', 'jenis_anggota' => 'AB', 'jenis_kelamin' => 'L', 'kategori' => 'CDB', 'autodebet' => 'BRI', 'no_rekening' => '330200009'],
            ['no_anggota' => 'DEMO-ANG-010', 'tanggal_aktif' => '2025-10-01', 'nama_anggota' => 'Ratna Sari*', 'status' => 'AKTIF', 'npwp' => null, 'no_telp' => '081234560010', 'jenis_anggota' => 'ALB', 'jenis_kelamin' => 'P', 'kategori' => 'KOP', 'autodebet' => 'MANUAL', 'no_rekening' => null],
        ];
    }

    private function seedMonthlyMandatoryDues(
        CooperativeMember $member,
        CooperativeContributionType $wajib,
        int $memberNumber,
        ?User $pengurus,
    ): void {
        $start = Carbon::parse($member->tanggal_aktif ?: $member->joined_at ?: now())->startOfMonth();
        $end = Carbon::now()->startOfMonth();

        for ($periodDate = $start->copy(); $periodDate->lte($end); $periodDate->addMonth()) {
            $period = $periodDate->format('Y-m');
            $paidAmount = $this->demoMandatoryPaidAmount(
                $memberNumber,
                $periodDate,
                $end,
                (float) $wajib->default_amount,
            );

            $this->seedInvoicePayment(
                $member,
                $wajib,
                $period,
                (float) $wajib->default_amount,
                $paidAmount,
                'ANGGOTA-WAJIB-'.$member->no_anggota.'-'.$period,
                $pengurus,
            );
        }
    }

    private function demoMandatoryPaidAmount(
        int $memberNumber,
        CarbonInterface $periodDate,
        CarbonInterface $currentPeriod,
        float $amount,
    ): float {
        if ($periodDate->equalTo($currentPeriod) && $memberNumber % 4 === 0) {
            return 0.0;
        }

        if ($periodDate->equalTo($currentPeriod->copy()->subMonth()) && $memberNumber % 5 === 0) {
            return $amount / 2;
        }

        return $amount;
    }

    private function seedInvoicePayment(
        CooperativeMember $member,
        CooperativeContributionType $type,
        string $period,
        float $amount,
        float $paidAmount,
        string $reference,
        ?User $pengurus,
    ): void {
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

        if ($paidAmount <= 0) {
            return;
        }

        $payment = CooperativePayment::query()->firstOrCreate(
            [
                'cooperative_member_id' => $member->id,
                'cooperative_dues_invoice_id' => $invoice->id,
                'reference_no' => 'DEMO-'.$reference,
            ],
            [
                'amount' => $paidAmount,
                'payment_method' => 'CASH',
                'paid_at' => Carbon::parse($period.'-05')->toDateString(),
                'status' => 'APPROVED',
                'approved_at' => Carbon::parse($period.'-05 10:00:00'),
                'approved_by' => $pengurus?->id,
            ],
        );

        if ($payment->wasRecentlyCreated || $payment->ledgerEntries()->doesntExist()) {
            app(CooperativePaymentService::class)->approve($payment, $pengurus);
        }
    }
}
