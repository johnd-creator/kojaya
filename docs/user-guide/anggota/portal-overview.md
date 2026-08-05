---
title: Mengenal Portal Anggota Kojayaku
slug: anggota-portal-overview
summary: Peta singkat menu anggota, alur simpan pinjam, dan notifikasi Midtrans.
category: Anggota · Memulai
module: portal
roles:
  - anggota
permissions: []
permission_mode: all
route_names:
  - member.dashboard
  - member.profile
  - member.savings
  - member.loans
  - member.points
  - member.rewards
  - member.transactions
  - member.store-account
  - member.notifications
  - member.onboarding
risk_level: low
screenshot_entries:
  - anggota-portal-overview-desktop
related_articles:
  - anggota-payment-flow
  - anggota-loan-flow
last_reviewed_commit: 20c86960
status: published
sort_order: 10
---

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
`route('member.onboarding')` untuk melengkapi data diri. Status
onboarding dan validasi menentukan apakah anggota dapat mengakses
fitur finansial — lihat
`App\Services\Cooperative\MemberAccessService` untuk logika
`can_access_financial_features` dan `is_active`.

## Pembayaran iuran

Pembayaran bulanan dipicu oleh
`php artisan cooperative:generate-monthly-dues` (lihat
`App\Console\Commands\CooperativeGenerateMonthlyDues`). Invoice
yang muncul hanya untuk bulan yang belum dibayar; anggota memilih
invoice lalu membuka `MidtransPaymentDialog.vue` atau
`PaymentProofDialog.vue` untuk transfer manual. Lihat artikel
**Alur Pembayaran Iuran via Midtrans** untuk langkah lengkap.
