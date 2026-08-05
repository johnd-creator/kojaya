---
title: Dashboard Operasional Admin Koperasi
slug: admin-koperasi-operational-dashboard
summary: Pintasan ke modul anggota, simpan pinjam, POS, dan inventori.
category: Admin Koperasi · Memulai
module: dashboard
roles:
  - admin_koperasi
permissions: []
permission_mode: all
route_names:
  - cooperative.operator.dashboard
  - cooperative.members.index
  - cooperative.members.create
  - cooperative.members.edit
  - cooperative.members.show
  - cooperative.members.resignations.index
  - cooperative.members.opening-balance.show
  - cooperative.dues.index
  - cooperative.loans.index
  - cooperative.loans.create
  - cooperative.loans.show
  - cooperative.loans.calculator
  - cooperative.loan-types.index
  - cooperative.pos.index
  - cooperative.pos-products.index
  - cooperative.pos-categories.index
risk_level: low
screenshot_entries:
  - admin-koperasi-operational-dashboard-desktop
related_articles:
  - admin-koperasi-loan-types
  - admin-koperasi-pos-inventory
  - admin-koperasi-payment-queue
last_reviewed_commit: 20c86960
status: published
sort_order: 10
---

# Dashboard Operasional Admin Koperasi

Grup middleware `cooperative` (lihat `routes/web.php` baris
`Route::prefix('cooperative')->name('cooperative.')`) membatasi akses
ke semua route dengan prefix `cooperative.`. Admin Koperasi memiliki
permission `manage_cooperative_member`, `manage_cooperative_dues`,
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
- **Saldo Pembuka** →
  `route('cooperative.members.opening-balance.show')`
  (perlu permission `manage_cooperative_opening_balance`).
- **Iuran** → `route('cooperative.dues.index')`.
- **Pinjaman** → daftar `route('cooperative.loans.index')`, buat
  `route('cooperative.loans.create')`, detail
  `route('cooperative.loans.show')`, kalkulator
  `route('cooperative.loans.calculator')`, jenis
  `route('cooperative.loan-types.index')`.
- **POS & Inventori** → `route('cooperative.pos.index')`,
  `route('cooperative.pos-products.index')`,
  `route('cooperative.pos-categories.index')`.
