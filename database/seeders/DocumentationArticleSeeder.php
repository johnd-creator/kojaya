<?php

namespace Database\Seeders;

use App\Models\DocumentationArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DocumentationArticleSeeder extends Seeder
{
    /**
     * Seed the four role-flavoured guides that drive the
     * `/documentation` center. Every `route('…')` reference below is
     * resolved against `routes/web.php` and `routes/settings.php` of the
     * pinned commit. Any drift is reported by
     * `php artisan docs:audit-drift` (see
     * `app/Console/Commands/VerifyDocumentationRoutesCommand.php`).
     *
     * Permission identifiers are also taken from
     * `app/Enums/PermissionEnum.php` and the role/permission mapping
     * in `database/seeders/RolePermissionSeeder.php`.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $articles = [
            // ---------- ANGGOTA ----------
            [
                'slug' => 'anggota-portal-overview',
                'title' => 'Mengenal Portal Anggota Kojayaku',
                'summary' => 'Peta singkat menu anggota, alur simpan pinjam, dan notifikasi Midtrans.',
                'category' => 'Anggota · Memulai',
                'target_role' => 'anggota',
                'required_permissions' => null,
                'sort_order' => 10,
                'body_markdown' => <<<'MD'
# Mengenal Portal Anggota Kojayaku

Portal anggota berada di belakang middleware `member` dan prefix
`member.*`. Semua URL dibuka setelah login anggota (lihat
`routes/web.php` baris grup `Route::prefix('member')->name('member.')`
di dalam `Route::middleware(['auth', 'verified'])`).

## Menu utama

- **Dashboard** → `route('member.dashboard')`
  Menampilkan ringkasan simpanan pokok, simpanan wajib, status
  keanggotaan, dan pintasan pembayaran.
- **Profil Saya** → `route('member.profile')`
  Mengelola data diri, keluarga, dan dokumen.
- **Simpanan** → `route('member.savings')`
  Riwayat simpanan pokok, wajib, dan sukarela.
- **Pinjaman** → `route('member.loans')`
  Daftar pinjaman aktif, angsuran berjalan, dan kalkulator plafon.
- **Poin & Rewards** → `route('member.points')` dan
  `route('member.rewards')`
- **Transaksi & Store Account** → `route('member.transactions')` dan
  `route('member.store-account')`
- **Notifikasi** → `route('member.notifications')`

## Onboarding

Setelah pertama kali login, anggota diarahkan ke
`route('member.onboarding')` untuk melengkapi data diri, lalu
memilih metode pembayaran Midtrans sebelum lanjut ke
`member.payments.intent` (lihat
`App\Http\Controllers\MemberPortalController@createPaymentIntent`).

## Pembayaran iuran

Pembayaran bulanan dipicu oleh
`php artisan cooperative:generate-monthly-dues` (lihat
`App\Console\Commands\CooperativeGenerateMonthlyDues`).
Invoice yang muncul hanya untuk bulan yang belum dibayar; anggota
memilih invoice lalu membuka `MidtransPaymentDialog.vue` atau
`PaymentProofDialog.vue` untuk transfer manual.
MD,
            ],
            [
                'slug' => 'anggota-payment-flow',
                'title' => 'Alur Pembayaran Iuran via Midtrans',
                'summary' => 'Cara membayar iuran bulanan, memilih invoice yang benar, dan mengunggah bukti.',
                'category' => 'Anggota · Pembayaran',
                'target_role' => 'anggota',
                'required_permissions' => null,
                'sort_order' => 20,
                'body_markdown' => <<<'MD'
# Alur Pembayaran Iuran via Midtrans

1. Buka **Simpanan** atau **Dashboard** anggota.
2. Pilih tagihan dengan status `pending`. Frontend hanya menampilkan
   invoice yang statusnya `pending` agar anggota tidak salah bayar.
3. Klik **Bayar**; sistem membuat payment intent via
   `route('member.payments.intent')`
   (`MemberPortalController@createPaymentIntent`).
4. Selesaikan pembayaran di `MidtransPaymentDialog.vue` dengan VA
   bank sandbox sesuai `MIDTRANS_VA_BANK` di `.env`.
5. Status dipantau di `route('member.payments.status')` sampai
   menjadi `paid`.

## Bukti transfer manual

Jika memilih manual, gunakan `PaymentProofDialog.vue` lalu unggah
bukti ke `route('member.payments.proof')`. Bukti masuk ke antrian
verifikasi Admin Koperasi pada
`route('cooperative.dues.index')` (lihat
`cooperative.dues.mark-paid`).

## Notifikasi

Pembayaran sukses memperbarui `notifications` dan mengirim lewat
outbox dengan idempotency key = UUID outbox (lihat
`docs/decisions.md`).
MD,
            ],
            [
                'slug' => 'anggota-loan-flow',
                'title' => 'Mengajukan dan Melacak Pinjaman',
                'summary' => 'Cara mengajukan pinjaman baru dan membaca status aplikasi.',
                'category' => 'Anggota · Pinjaman',
                'target_role' => 'anggota',
                'required_permissions' => null,
                'sort_order' => 30,
                'body_markdown' => <<<'MD'
# Mengajukan dan Melacak Pinjaman

1. Buka **Pinjaman** (`route('member.loans')`).
2. Pilih **Ajukan Pinjaman** → `route('cooperative.loans.create')`
   (route ini dibuka di bawah middleware `auth`+`can:view_cooperative_loan`).
3. Isi nominal, tenor, dan jenis pinjaman. Validasi menggunakan
   `LoanApplicationRequest`; plafon mengikuti limit simpanan.
4. Setelah submit, status aplikasi melalui:
   - `submitted` → diantrikan untuk Manajer.
   - `manager_review` → sedang ditinjau `Manajer Koperasi` lewat
     `route('cooperative.loans.review')` (perlu permission
     `review_cooperative_loan`).
   - `chairman_approval` → menunggu keputusan `Pengurus Koperasi`
     (`route('cooperative.loans.approve')`, permission
     `approve_cooperative_loan`).
   - `approved` → cair lewat `route('cooperative.loans.disburse')`,
     angsuran otomatis dibuat.
5. Lacak status real-time di **Pinjaman** dengan filter `status`.

## Pembatalan

Anggota dapat membatalkan aplikasi selama status masih `submitted`
lewat tombol **Batalkan** di detail pinjaman
(`route('cooperative.loans.show')`).
MD,
            ],

            // ---------- ADMIN KOPERASI ----------
            [
                'slug' => 'admin-koperasi-operational-dashboard',
                'title' => 'Dashboard Operasional Admin Koperasi',
                'summary' => 'Pintasan ke modul anggota, simpan pinjam, POS, dan inventori.',
                'category' => 'Admin Koperasi · Memulai',
                'target_role' => 'admin_koperasi',
                'required_permissions' => null,
                'sort_order' => 10,
                'body_markdown' => <<<'MD'
# Dashboard Operasional Admin Koperasi

Grup middleware `cooperative` (lihat `routes/web.php` baris
`Route::prefix('cooperative')->name('cooperative.')`) membatasi akses
ke semua route dengan prefix `cooperative.`. Admin Koperasi
memiliki izin `manage_cooperative_member`, `manage_cooperative_dues`,
`manage_cooperative_payment`, `manage_cooperative_loan`,
`access_cooperative_pos`, `manage_cooperative_points`, dan
`manage_cooperative_loan_types` (lihat
`app/Enums/PermissionEnum.php` + `RolePermissionSeeder`).

## Pintasan utama

- **Operator Dashboard** → `route('cooperative.operator.dashboard')`
  (entry point pengalaman harian Admin Koperasi).
- **Daftar Anggota** → `route('cooperative.members.index')`,
  `route('cooperative.members.create')`,
  `route('cooperative.members.edit')`, dan
  `route('cooperative.members.show')`.
- **Pengunduran Diri** →
  `route('cooperative.members.resignations.index')`
  (perlu permission `review_cooperative_resignation`).
- **Saldo Pembuka** → `route('cooperative.members.opening-balance.show')`
  (perlu permission `manage_cooperative_opening_balance`).
- **Iuran** → `route('cooperative.dues.index')`.
- **Pinjaman** → daftar `route('cooperative.loans.index')`, buat
  `route('cooperative.loans.create')`, detail
  `route('cooperative.loans.show')`, kalkulator
  `route('cooperative.loans.calculator')`, jenis
  `route('cooperative.loan-types.index')`.
- **POS & Inventori** → `route('cooperative.pos.index')`,
  `route('cooperative.pos-products.index')`,
  `route('cooperative.pos-categories.index')`,
  `route('cooperative.pos.inventory.receipts.index')`,
  `route('cooperative.pos.inventory.transfers.index')`, dan
  `route('cooperative.pos.inventory.counts.index')`.
MD,
            ],
            [
                'slug' => 'admin-koperasi-loan-types',
                'title' => 'Mengelola Jenis Pinjaman',
                'summary' => 'Cara membuat dan memperbarui jenis pinjaman via API resource.',
                'category' => 'Admin Koperasi · Pinjaman',
                'target_role' => 'admin_koperasi',
                'required_permissions' => ['manage_cooperative_loan_types'],
                'sort_order' => 20,
                'body_markdown' => <<<'MD'
# Mengelola Jenis Pinjaman

Permission yang dibutuhkan: `manage_cooperative_loan_types`.

1. Buka **Pinjaman → Jenis Pinjaman** →
   `route('cooperative.loan-types.index')`.
2. Klik **Buat**; submit form yang men-`POST` ke
   `route('cooperative.loan-types.store')`. Tidak ada route
   `cooperative.loan-types.create` terpisah — UI memuat form
   lewat Inertia dan submit langsung ke `store`.
3. Field minimal: nama, bunga efektif, tenor maksimum, dan
   dokumen wajib (lihat `LoanTypeRequest`).
4. Perubahan langsung berlaku untuk pinjaman baru, tidak
   memengaruhi angsuran yang sudah jalan.

## Validasi

- Nama: unik per cooperative.
- Bunga: 0 ≤ bunga ≤ 100.
- Tenor: 1 ≤ tenor ≤ 360 bulan.
- Dokumen: minimal 1 jenis.

## Verifikasi

Update melalui `route('cooperative.loan-types.update')` lalu cek
`LoanTypePolicy@update` mengembalikan `true` untuk role
`Admin Koperasi`.
MD,
            ],
            [
                'slug' => 'admin-koperasi-pos-inventory',
                'title' => 'Operasi Harian POS, Inventori, dan Setoran Kasir',
                'summary' => 'Alur shift, penjualan, retur, penyetoran kas, dan opname stok.',
                'category' => 'Admin Koperasi · Operasional',
                'target_role' => 'admin_koperasi',
                'required_permissions' => ['access_cooperative_pos', 'manage_pos_products'],
                'sort_order' => 30,
                'body_markdown' => <<<'MD'
# Operasi Harian POS, Inventori, dan Setoran Kasir

## POS

- Buka shift: `route('cooperative.pos.shifts.index')` lalu
  `route('cooperative.pos.shifts.open')`.
- Catat order: `route('cooperative.pos.transactions.store')` dan
  pantau di `route('cooperative.pos.transactions.index')`.
- Pantauan coffee orders:
  `route('cooperative.pos.coffee-orders.index')` dan ubah status
  lewat `route('cooperative.pos.coffee-orders.update-status')`.
- Void: `route('cooperative.pos.void-requests.index')` (perlu
  permission `approve_pos_void`) lalu proses via
  `route('cooperative.pos.void-requests.process')`.
- Retur: `route('cooperative.pos.returns.create')` (per
  transaksi) lalu simpan di
  `route('cooperative.pos.returns.store')`.
- Kredit/angsuran anggota:
  `route('cooperative.pos.credit.create')` dan
  `route('cooperative.pos.credit.store')`.

## Setoran kasir

Setelah shift, tutup lewat
`route('cooperative.pos.shifts.close')`. Setoran harian kemudian
direkam di `route('cooperative.pos.closings.index')` dan
ditutup via `route('cooperative.pos.closings.close')` (perlu
permission `view_pos_reports`).

## Inventori

- Stok opname: `route('cooperative.pos.inventory.counts.index')`,
  `route('cooperative.pos.inventory.counts.create')`,
  `route('cooperative.pos.inventory.counts.show')`.
- Penerimaan barang:
  `route('cooperative.pos.inventory.receipts.index')` /
  `route('cooperative.pos.inventory.receipts.create')`.
- Transfer gudang:
  `route('cooperative.pos.inventory.transfers.index')` /
  `route('cooperative.pos.inventory.transfers.create')`.

## Laporan

- Laporan POS: `route('cooperative.pos.reports.index')`.
- Laporan koperasi: `route('cooperative.reports.index')`.
- SHU: `route('cooperative.shu.index')`.
MD,
            ],

            // ---------- MANAJER KOPERASI ----------
            [
                'slug' => 'manajer-loan-review',
                'title' => 'Tinjauan Aplikasi Pinjaman oleh Manajer',
                'summary' => 'Proses review 5C, keputusan setujui/tolak, dan jalur eskalasi.',
                'category' => 'Manajer Koperasi · Pinjaman',
                'target_role' => 'manajer_koperasi',
                'required_permissions' => ['review_cooperative_loan'],
                'sort_order' => 10,
                'body_markdown' => <<<'MD'
# Tinjauan Aplikasi Pinjaman oleh Manajer

Manajer Koperasi adalah peran tertinggi kedua di koperasi dengan
permission `review_cooperative_loan` (lihat
`app/Enums/PermissionEnum.php` + `RolePermissionSeeder`). Tinjauan
dipicu oleh `POST` ke `route('cooperative.loans.review')`.

## Alur review

1. Masuk ke **Pinjaman** → `route('cooperative.loans.index')`
   dengan filter `status=manager_review`.
2. Tinjau **5C**: Character, Capacity, Capital, Collateral,
   Condition. Data tersedia di `route('cooperative.loans.show')`.
3. Pilih keputusan lewat endpoint:
   - **Setujui untuk Pengurus** →
     `route('cooperative.loans.approve')` (jika Manajer juga
     memiliki permission `approve_cooperative_loan`) atau
     `route('cooperative.loans.review')` untuk meneruskan ke
     `chairman_approval`.
   - **Minta Revisi** → `route('cooperative.loans.review')` dengan
     catatan di `loan_applications.notes`.
   - **Tolak** → `route('cooperative.loans.reject')`; status
     `rejected` final.
4. Pastikan ledger simpanan terkini sebelum menyetujui; gunakan
   `route('cooperative.ledger.index')` sebagai referensi.

## Kuorum & batas waktu

Review harus selesai ≤ 3 hari kerja sejak `submitted`. SLA
dipantau lewat `LoanApplicationReviewWindow` (lihat
`App\Models\LoanApplication`).
MD,
            ],
            [
                'slug' => 'manajer-financial-monitoring',
                'title' => 'Pemantauan Keuangan Harian',
                'summary' => 'Cara membaca ringkasan simpan pinjam, NPL, dan pencairan.',
                'category' => 'Manajer Koperasi · Keuangan',
                'target_role' => 'manajer_koperasi',
                'required_permissions' => ['view_cooperative_report'],
                'sort_order' => 20,
                'body_markdown' => <<<'MD'
# Pemantauan Keuangan Harian

## Dashboard

- Ringkasan harian:
  `route('cooperative.operator.dashboard')` (widget Manajer).
- NPL: badge di pojok kanan atas widget.
- Angsuran tertunda: taut ke
  `route('cooperative.loans.index')` dengan filter
  `status=overdue`.

## Tindakan korektif

- Angsuran macet ≥ 30 hari → tugaskan Admin Koperasi untuk
  follow-up lewat `route('cooperative.payments.index')`.
- Setoran kasir harian → rekonsiliasi di
  `route('cooperative.pos.closings.index')`; cocokan dengan
  `route('cooperative.ledger.index')`.
- Pencairan yang belum cair di
  `route('cooperative.loans.index')` (filter `status=approved`)
  → hubungi Admin untuk status transfer lewat
  `route('cooperative.loans.disburse')`.

## Eskalasi

Permasalahan hukum (sengketa, audit eksternal) → teruskan ke
Pengurus via laporan triwulan pada
`route('cooperative.reports.index')`.
MD,
            ],

            // ---------- PENGURUS KOPERASI ----------
            [
                'slug' => 'pengurus-loan-approval',
                'title' => 'Persetujuan Akhir Pinjaman oleh Pengurus',
                'summary' => 'Kewenangan approvals, quorum, dan pencatatan keputusan rapat.',
                'category' => 'Pengurus Koperasi · Tata Kelola',
                'target_role' => 'pengurus_koperasi',
                'required_permissions' => ['approve_cooperative_loan'],
                'sort_order' => 10,
                'body_markdown' => <<<'MD'
# Persetujuan Akhir Pinjaman oleh Pengurus

Pengurus Koperasi adalah peran tertinggi di koperasi
(setingkat System Admin di lingkup koperasi, lihat
`app/Enums/RoleExperience.php` + `docs/proses_bisnis/roles.md`).
Permission `approve_cooperative_loan` menjadi kunci masuk ke
endpoint `POST` `route('cooperative.loans.approve')`.

## Alur approval

1. Buka **Pinjaman** → `route('cooperative.loans.index')` dengan
   filter `status=chairman_approval`.
2. Buka detail → `route('cooperative.loans.show')` (read-only).
3. Keputusan:
   - **Setujui** → `route('cooperative.loans.approve')`; status
     `approved`; trigger pencairan via
     `route('cooperative.loans.disburse')` dan penjadwalan
     angsuran.
   - **Tunda** → kembali ke Manajer dengan catatan di
     `loan_applications.notes`.
   - **Tolak** → `route('cooperative.loans.reject')`; status
     `rejected` final.
4. Setiap keputusan wajib menyertakan referensi rapat/risalah
   di `ApprovalMinute`.

## Quorum

- Pinjaman ≤ Rp 500 juta: cukup 1 Pengurus + 1 Manajer.
- Pinjaman > Rp 500 juta: minimum 2 Pengurus + 1 Manajer.
- Pinjaman > Rp 2 miliar: wajib keputusan rapat pleno, simpan
  scan risalah di lampiran `ApprovalMinute`.

## Pencatatan

- `AuditLog` otomatis merekam setiap keputusan (lihat
  `tests/Feature/AuditLogTest.php`).
- Laporan triwulan: aggregate dari `LoanApplication` dengan
  status `approved` diekspor lewat
  `route('cooperative.reports.index')` → preset **Pengurus**.
MD,
            ],
            [
                'slug' => 'pengurus-shu-and-governance',
                'title' => 'SHU, Tata Kelola, dan Rapat Anggota',
                'summary' => 'Siklus SHU tahunan, dokumen RAT, dan audit internal.',
                'category' => 'Pengurus Koperasi · Tata Kelola',
                'target_role' => 'pengurus_koperasi',
                'required_permissions' => ['manage_cooperative_shu'],
                'sort_order' => 20,
                'body_markdown' => <<<'MD'
# SHU, Tata Kelola, dan Rapat Anggota

## SHU tahunan

- Alokasi: `route('cooperative.shu.index')` → lihat preset
  **RAT**. Perhitungan mengikuti
  `App\Services\ShuDistributionService` (lihat
  `tests/Unit/Finance/Shu*Test.php`).
- Penutupan buku: `route('cooperative.shu.close')`. Penyaluran
  ke anggota: otomatis dilakukan setelah RAT ditutup; lihat juga
  `route('cooperative.points.index')` (perlu
  `manage_cooperative_points`).
- Permintaan revisi periode:
  `route('cooperative.shu.request-revision')`.

## RAT

- Persiapan dokumen:
  `route('cooperative.reports.index')` → preset **AnnualReport**.
- Notifikasi RAT ke anggota: outbox bertipe `rat_invitation`
  (lihat `app/Services/Notifications`).
- Hasil RAT disimpan ke `RatMinute` dan diarsipkan via
  `AuditLog` agar bisa diaudit oleh pengurus berikutnya.

## Audit internal

- Audit internal: baca log di
  `route('audit-logs')` (perlu permission `view_audit_logs`).
- Pengawasan operasional: manfaatkan
  `route('exceptions.index')` untuk anomali lintas modul.
- Perubahan AD/ART: lewat modul pengaturan koperasi yang
  dilindungi permission `manage_cooperative_settings`
  (`can:manage_cooperative_settings` di
  `App\Providers\AuthServiceProvider`).
MD,
            ],
        ];

        foreach ($articles as $row) {
            DocumentationArticle::updateOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, ['published_at' => $now]),
            );
        }
    }
}
