---
title: Tinjauan Aplikasi Pinjaman oleh Manajer
slug: manajer-loan-review
summary: Proses review 5C, keputusan setujui/tolak, dan jalur eskalasi.
category: Manajer Koperasi · Pinjaman
module: loans
roles:
  - manajer_koperasi
permissions:
  - review_cooperative_loan
permission_mode: all
route_names:
  - cooperative.loans.index
  - cooperative.loans.show
  - cooperative.loans.review
  - cooperative.loans.approve
  - cooperative.loans.reject
  - cooperative.ledger.index
risk_level: medium
screenshot_entries:
  - manajer-loan-review-desktop
related_articles:
  - manajer-financial-monitoring
  - pengurus-loan-approval
last_reviewed_commit: 20c86960
status: published
sort_order: 10
---

# Tinjauan Aplikasi Pinjaman oleh Manajer

Manajer Koperasi adalah peran tertinggi kedua di koperasi dengan
permission `review_cooperative_loan` (lihat
`app/Enums/PermissionEnum.php` + `RolePermissionSeeder`). Tinjauan
dipicu oleh `POST` ke `route('cooperative.loans.review')`.

## Alur review

1. Masuk ke **Pinjaman** → `route('cooperative.loans.index')`
   dengan filter `status=APPLIED`.
2. Tinjau **5C**: Character, Capacity, Capital, Collateral,
   Condition. Data tersedia di `route('cooperative.loans.show')`.
3. Pilih keputusan lewat endpoint:
   - **Teruskan ke Pengurus** →
     `route('cooperative.loans.review')`. Backend
     `LoanController@review` memindahkan status ke
     `MANAGER_APPROVED`.
   - **Tolak** → `route('cooperative.loans.reject')`; status
     `REJECTED` final.
4. Pastikan ledger simpanan terkini sebelum menyetujui; gunakan
   `route('cooperative.ledger.index')` sebagai referensi.

> **Catatan:** aplikasi yang sudah `MANAGER_APPROVED` menunggu
> keputusan Pengurus. Manajer tidak memiliki `approve_cooperative_loan`
> kecuali role-nya ditambahi secara eksplisit.
