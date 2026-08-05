---
title: Mengelola Jenis Pinjaman
slug: admin-koperasi-loan-types
summary: Cara membuat dan memperbarui jenis pinjaman via API resource.
category: Admin Koperasi · Pinjaman
module: loans
roles:
  - admin_koperasi
permissions:
  - manage_cooperative_loan_types
permission_mode: all
route_names:
  - cooperative.loan-types.index
  - cooperative.loan-types.store
  - cooperative.loan-types.update
  - cooperative.loan-types.destroy
risk_level: medium
screenshot_entries:
  - admin-koperasi-loan-types-desktop
related_articles:
  - admin-koperasi-operational-dashboard
last_reviewed_commit: 20c86960
status: published
sort_order: 20
---

# Mengelola Jenis Pinjaman

Permission yang dibutuhkan: `manage_cooperative_loan_types`.

1. Buka **Pinjaman → Jenis Pinjaman** →
   `route('cooperative.loan-types.index')`.
2. Klik **Buat**; submit form yang men-`POST` ke
   `route('cooperative.loan-types.store')`. Tidak ada route
   `cooperative.loan-types.create` terpisah — UI memuat form
   lewat Inertia dan submit langsung ke `store`.
3. Validasi mengikuti
   `App\Http\Requests\Cooperative\StoreLoanTypeRequest`:
   - `code` (string, unik per cooperative)
   - `name`
   - `description`
   - `interest_rate`
   - `admin_fee`
   - `late_fee_per_day`
   - `min_amount`, `max_amount`
   - `min_term_months`, `max_term_months`
   - `is_active`
4. Perubahan melalui `route('cooperative.loan-types.update')`
   dengan `PUT`; `LoanTypeController` memanggil
   `authorizeLoanManagement()` (inline) untuk memastikan
   `can('manage_cooperative_loan_types')` bernilai benar.

## Penghapusan

`route('cooperative.loan-types.destroy')` melakukan soft delete.
Jenis yang sudah pernah dipakai oleh aplikasi tidak dapat
dihapus — controller akan menolak.
