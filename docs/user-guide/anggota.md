# Panduan Pengguna — Anggota

> **Target peran:** `Anggota` (Spatie role: `Anggota`)
> **Permission minimum:** `member_portal_access` (diperkuat dengan ownership check pada relasi `cooperativeMember`).
> **Portal:** `/member` (middleware `member`).

Halaman ini adalah versi Markdown dari artikel yang sama yang disajikan
oleh pusat panduan in-app di `/documentation`. Lihat
[`Database\Seeders\DocumentationArticleSeeder`](../database/seeders/DocumentationArticleSeeder.php)
untuk sumber otoritatif artikel `target_role = anggota`.

## 1. Mengenal Portal Anggota Kojayaku

Portal anggota berada di belakang middleware `member` dan prefix
`member.*`. Semua URL dibuka setelah login anggota (lihat
`routes/web.php` baris grup `Route::prefix('member')->name('member.')`
di dalam `Route::middleware(['auth', 'verified'])`).

### Menu utama

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

### Onboarding

Setelah pertama kali login, anggota diarahkan ke
`route('member.onboarding')` untuk melengkapi data diri, lalu
memilih metode pembayaran Midtrans sebelum lanjut ke
`member.payments.intent` (lihat
`App\Http\Controllers\MemberPortalController@createPaymentIntent`).

### Pembayaran iuran

Pembayaran bulanan dipicu oleh
`php artisan cooperative:generate-monthly-dues` (lihat
`App\Console\Commands\CooperativeGenerateMonthlyDues`).
Invoice yang muncul hanya untuk bulan yang belum dibayar; anggota
memilih invoice lalu membuka `MidtransPaymentDialog.vue` atau
`PaymentProofDialog.vue` untuk transfer manual.

## 2. Alur Pembayaran Iuran via Midtrans

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

### Bukti transfer manual

Jika memilih manual, gunakan `PaymentProofDialog.vue` lalu unggah
bukti ke `route('member.payments.proof')`. Bukti masuk ke antrian
verifikasi Admin Koperasi pada
`route('cooperative.dues.index')` (lihat
`cooperative.dues.mark-paid`).

### Notifikasi

Pembayaran sukses memperbarui `notifications` dan mengirim lewat
outbox dengan idempotency key = UUID outbox (lihat
[`docs/decisions.md`](../decisions.md)).

## 3. Mengajukan dan Melacak Pinjaman

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

### Pembatalan

Anggota dapat membatalkan aplikasi selama status masih `submitted`
lewat tombol **Batalkan** di detail pinjaman
(`route('cooperative.loans.show')`).
