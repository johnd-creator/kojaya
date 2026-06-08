# Analisis Roles & Permission — Kojaya

> **Diperbarui:** 7 Juni 2026 (Admin Koperasi seed hardening)  
> **Status:** Sinkron dengan implementasi terbaru

---

## Ringkasan

Saat ini ada **15 role** dan **126 permission** di `PermissionEnum`. Map dari role ke permission sudah terdefinisi di `RolePermissionSeeder`. Dokumen ini menyajikan matrix role dan aturan permission, dihasilkan dari kode, bukan snapshot manual.

## 15 Role yang Terdaftar di `RolePermissionSeeder`

| Role | Permission Count | Deskripsi |
|------|-----------------|-----------|
| System Admin | 126 (all) | Akses penuh tanpa batasan |
| Admin Pusat | 126 (all) | Akses penuh, dibatasi organization scope |
| Admin Unit | 12 | Operasional unit: employee, attendance, procurement view, project unit, asset unit, petty cash |
| HR Pusat | 18 | Employee management, payroll, HR master data, leave, overtime, ESS, contracts |
| HR Unit | 9 | Employee unit, payroll unit, leave/overtime view, ESS |
| Finance Pusat | 21 | Full finance: invoices, budget, petty cash, bank, COA, journal, statements, efaktur, reimbursement, payroll |
| Finance Unit | 12 | Finance unit: budget view, petty cash, bank batch, COA view, statements view |
| Project Manager | 16 | Projects, procurement (full lifecycle), assets, work orders, clients, vendors |
| Site Manager | 4 | Project unit view, asset unit, work order unit view |
| Technician | 2 | Asset unit view, work order unit view |
| Employee | 5 | ESS portal, own attendance, own payroll, own payslip |
| Pengurus Koperasi | 22 | Semua modul cooperative: members, dues, payments, loans, points, rewards, SHU, POS, ledger, settings |
| Admin Koperasi | 16 | Operasional cooperative: members, dues, payments, loans, points, rewards, redemptions, POS inventory, POS reports, ledger |
| Kasir Koperasi | 6 | Cooperative members view, payments, loans view, POS, reports |
| Anggota | 2 | Member self-service view: own member data, own loan |

## Semua Permission (126 — dari `PermissionEnum`)

### Organizations (5)
`view_organization_all`, `view_organization_unit`, `create_organization`, `edit_organization`, `delete_organization`

### Users & Roles (5)
`view_user_all`, `view_user_unit`, `create_user`, `edit_user`, `delete_user`

### Employees (5)
`view_employee_all`, `view_employee_unit`, `create_employee`, `edit_employee`, `delete_employee`

### Attendances (3)
`view_attendance_all`, `view_attendance_unit`, `approve_attendance`

### Payroll (4)
`view_payroll_all`, `view_payroll_unit`, `process_payroll`, `approve_payroll`

### Procurement (7)
`view_pr_all`, `create_pr`, `approve_pr`, `view_po_all`, `create_po`, `view_grn_all`, `receive_grn`

### Finance & Accounting (18)
`view_invoice_all`, `view_budget_all`, `manage_budget`, `manage_petty_cash`, `manage_bank_batch`, `manage_bank_reconciliation`, `view_chart_of_accounts`, `manage_chart_of_accounts`, `manage_journal_entries`, `view_trial_balance`, `view_balance_sheet`, `view_income_statement`, `manage_efaktur`, `manage_reimbursement`, `approve_reimbursement`

### Projects (4)
`view_project_all`, `view_project_unit`, `manage_project`, `manage_project_team`

### Asset & Maintenance (6)
`view_asset_all`, `view_asset_unit`, `manage_asset`, `view_work_order_all`, `view_work_order_unit`, `manage_work_order`

### HR Master Data (6)
`manage_departments`, `manage_positions`, `manage_job_grades`, `manage_work_shifts`, `manage_salary_structures`, `manage_shift_rosters`

### Employee Advanced (4)
`manage_employee_transfer`, `approve_employee_transfer`, `manage_employee_contract`, `manage_employee_family`

### Leave & Overtime (6)
`view_leave_all`, `view_leave_unit`, `approve_leave`, `view_overtime_all`, `view_overtime_unit`, `approve_overtime`

### ESS Portal (2)
`access_ess_portal`, `view_own_payslip`

### Cooperative Extended (22)
`view_cooperative_member`, `manage_cooperative_member`, `manage_cooperative_dues`, `manage_cooperative_payment`, `view_cooperative_loan`, `manage_cooperative_loan`, `approve_cooperative_loan`, `access_cooperative_pos`, `view_cooperative_report`, `manage_cooperative_points`, `manage_cooperative_rewards`, `manage_cooperative_redemption`, `manage_cooperative_shu`, `manage_cooperative_loan_types`, `manage_pos_categories`, `manage_pos_products`, `view_pos_reports`, `manage_pos_shu`, `view_cooperative_ledger`, `manage_cooperative_ledger`, `view_cooperative_all`, `manage_cooperative_settings`

### System / Admin (8)
`manage_clients`, `manage_vendors`, `view_audit_logs`, `export_audit_logs`, `manage_organizations`, `manage_roles`, `manage_users`, `view_reports`

### Storage (3)
`manage_spare_parts`, `manage_warehouses`, `view_stock`

## API Token Abilities (Sanctum)

`AuthController::abilitiesFor()` mendefinisikan token abilities untuk mobile API:

| Role | Token Abilities |
|------|----------------|
| System Admin / Admin Pusat | `['*']` |
| Anggota | `member:read`, `member:write`, `cooperative:read`, `cooperative:write` |
| Employee | `ess:read`, `ess:write`, `attendance:read`, `attendance:write`, `payroll:read` |
| Technician | `work-orders:read`, `work-orders:write` |
| Pengurus Koperasi / Kasir | `cooperative:read`, `cooperative:write`, `pos:read`, `pos:write`, `reports:read` |

> **Catatan:** Token abilities dan Spatie permission adalah dua sistem berbeda yang melayani konteks berbeda: web admin (Spatie) vs mobile API (Sanctum token abilities). Controller API mobile menggunakan `ability:` middleware untuk memverifikasi token.

## Aturan Otorisasi

### 1. Permission-based (Spatie) — digunakan di web controller
Controller yang menggunakan `authorizePermission()` atau `$this->authorize()` via policy yang memeriksa Spatie permission.

### 2. Role-based — digunakan di policy tertentu
Beberapa policy menggunakan `hasRole()` atau `hasAnyRole()` untuk domain yang lebih cocok dengan role check.

### 3. Ownership check — controller API mobile
API mobile (Anggota, Employee, Technician) menggunakan ownership check untuk memastikan user hanya mengakses data mereka sendiri, selain ability check dari token.

### 4. v-can directive — frontend guard
Tombol aksi di halaman menggunakan `v-can="'permission_name'"` untuk menyembunyikan tombol tanpa permission. Backend tetap menjadi enforcement utama.

## Testing

### Role Smoke Test
Setiap role utama diuji:
- Bisa mengakses halaman yang sesuai dengan permission-nya (200/OK)
- Mendapat 403 pada halaman yang bukan miliknya
- Menu sidebar ditampilkan sesuai permission

### Controller Authorization Test
`Phase4ControllerAuthorizationTest` memverifikasi setiap controller menggunakan authorization (permission, policy, atau ownership).

### Operator Hardening Test
`Phase4Phase5OperatorHardeningTest` memverifikasi endpoint operator (approval inbox, closing, reconciliation, exceptions, export) tersedia dan terlindungi.

## Kekinian vs Analisis Sebelumnya

Dokumen ini menggantikan snapshot manual yang lama. Semua gap yang diidentifikasi di analisis sebelumnya sudah diatasi:

- ✅ Technician ada di seeder (126 permission penuh)
- ✅ Semua 15 role memiliki permission yang sesuai di seeder
- ✅ Finance, HR Master Data, Projects, Asset, WorkOrder, Leave, Overtime sudah memiliki permission
- ✅ Cooperative extended (points, rewards, SHU, POS categories, reports, loan types, ledger) sudah memiliki permission
- ✅ Storage, clients, vendors, audit logs, reports sudah memiliki permission
- ✅ Controller authorization sudah diterapkan di semua controller
- ✅ v-can directive sudah tersedia untuk tombol aksi di frontend
- ✅ API token abilities terpisah dengan baik untuk mobile API

## Prosedur Pemeliharaan

1. **Menambah permission baru:** Tambahkan case di `PermissionEnum`, assign ke role di `RolePermissionSeeder::run()`, dan jalankan `php artisan db:seed --class=RolePermissionSeeder`
2. **Menambah role baru:** Tambahkan di array `$roles` di `RolePermissionSeeder`, assign permission yang sesuai
3. **Update dokumen ini:** Jalankan test matrix untuk regenerasi daftar.
4. **Jangan mengedit manual:** Gunakan always source dari `PermissionEnum` dan `RolePermissionSeeder`.
