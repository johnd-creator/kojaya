---
title: Mengajukan dan Melacak Pinjaman
slug: anggota-loan-flow
summary: Cara mengajukan pinjaman baru dan membaca status aplikasi.
category: Anggota · Pinjaman
module: loans
roles:
  - anggota
permissions: []
permission_mode: all
route_names:
  - member.loans
  - member.loans.store
  - member.loans.installments.payment-intent
  - member.loans.payment-intents.status
risk_level: medium
screenshot_entries:
  - anggota-loan-flow-desktop
  - anggota-loan-flow-mobile
related_articles:
  - anggota-portal-overview
last_reviewed_commit: 20c86960
status: published
sort_order: 30
---

# Mengajukan dan Melacak Pinjaman

1. Buka **Pinjaman** (`route('member.loans')`).
2. Pilih tab **Simulasi & Ajukan** pada
   `resources/js/pages/Kojayaku/Loans.vue`.
3. Isi jenis pinjaman, nominal, tenor, tanggal jatuh tempo
   pertama, tujuan, dan catatan. Validasi dilakukan oleh
   `App\Http\Requests\StoreMemberLoanApplicationRequest`.
4. Klik **Kirim Pengajuan Pinjaman**. Sistem melakukan
   `POST` ke `route('member.loans.store')`
   (`MemberPortalController@applyLoan`) yang memanggil
   `App\Services\Cooperative\LoanService::apply()` dengan
   `cooperative_member_id` dari user yang sedang login.

## Status aplikasi

Enum `App\Enums\LoanStatus` menentukan status yang mungkin:

| Status | Konteks |
| --- | --- |
| `APPLIED` | Aplikasi baru, menunggu tinjauan Manajer. |
| `MANAGER_APPROVED` | Sudah disetujui Manajer, menunggu keputusan Pengurus. |
| `APPROVED` | Disetujui final, menunggu pencairan. |
| `REJECTED` | Ditolak pada salah satu tahap. |
| `ACTIVE` | Sudah dicairkan, angsuran berjalan. |
| `PAID_OFF` | Lunas. |
| `DEFAULTED` | Angsuran macet. |
| `WRITTEN_OFF` | Dihapusbukukan. |

Frontend `Kojayaku/Loans.vue` memfilter daftar berdasarkan kolom
`status` (string dari enum). Untuk melihat perkembangan, kembali ke
tab **Daftar Pinjaman & Riwayat** dan pilih aplikasi.

## Pembayaran angsuran

Untuk membayar angsuran yang jatuh tempo:

1. Buka detail angsuran dari daftar pinjaman.
2. Sistem membuat payment intent melalui
   `route('member.loans.installments.payment-intent')`
   (`MemberPortalController@createLoanPaymentIntent`).
3. Pantau status melalui
   `route('member.loans.payment-intents.status')`
   (`MemberPortalController@loanPaymentIntentStatus`).
4. `App\Services\Integrations\LoanPaymentIntentService` memastikan
   hanya ada satu payment intent aktif per angsuran.

## Pembatalan aplikasi

Saat ini aplikasi pinjaman **tidak dapat dibatalkan oleh anggota**
melalui antarmuka. Jika ingin menarik aplikasi, hubungi Admin
Koperasi melalui kanal yang tersedia.
