# Analisis Roles & Permission — Kojaya

> **Dibuat:** 5 Mei 2026  
> **Status:** Analisis gap antara fitur yang sudah ada dengan role yang sudah terdefinisi

---

## Ringkasan

Saat ini ada **13 role** dan **38 permission** yang terdaftar di sistem. Namun setelah diperiksa secara menyeluruh, terdapat beberapa kekurangan:

1. **Role yang dipakai di controller/policy tidak terdaftar di seeder** (Technician, Employee, Anggota sebagai employee)
2. **Role yang TIDAK mendapat permission apapun di seeder** (Site Manager, Karyawan, Admin Unit)
3. **Role yang tidak muncul di policy/controller manapun** tapi tetap dibuat (Karyawan tidak dipakai di manapun di backend)
4. **Fitur yang tidak dilindungi role apapun** (beberapa route web tidak punya check role, hanya mengandalkan sidebar hide)
5. **API Token abilities tidak sinkron dengan permission Spatie**

---

## 1. 13 Role yang Terdaftar di `RolePermissionSeeder`

| # | Role | Permission di Seeder | Dipakai Backend? |
|---|------|---------------------|-------------------|
| 1 | **System Admin** | 38 permission (all) | ✅ `Gate::before` bypass |
| 2 | **Admin Pusat** | 38 permission (all) | ✅ Organization scope + policies |
| 3 | **Admin Unit** | ❌ Tidak ada | ✅ Project, Asset, WorkOrder, Overtime, Reimbursement policies |
| 4 | **HR Pusat** | 5 permission | ✅ Employee, Payroll, Leave, Overtime policies |
| 5 | **HR Unit** | 5 permission | ✅ Employee, Payroll, Leave, Overtime policies |
| 6 | **Finance Pusat** | 6 permission | ✅ Budget, Invoice, Payroll, Bank, PettyCash policies |
| 7 | **Finance Unit** | 6 permission | ✅ Budget, Invoice, Payroll, Bank, PettyCash policies |
| 8 | **Project Manager** | 7 permission | ✅ Project, Asset, WorkOrder, Procurement policies |
| 9 | **Site Manager** | ❌ Tidak ada | ✅ Project, Asset, WorkOrder policies |
| 10 | **Karyawan** | ❌ Tidak ada | ❌ Tidak muncul di backend manapun |
| 11 | **Pengurus Koperasi** | 9 permission | ✅ Semua controller cooperative |
| 12 | **Kasir Koperasi** | 4 permission | ✅ Semua controller cooperative |
| 13 | **Anggota** | 2 permission | ✅ Loan view, Report view, member portal |

### Role tambahan yang digunakan di policy/controller tapi TIDAK terdaftar di seeder:

| Role | Digunakan di |
|------|-------------|
| **Technician** | `AssetPolicy`, `WorkOrderPolicy`, `AuthController::abilitiesFor()` |
| **Employee** (role `user` dengan `employee`) | `OvertimeRequestPolicy`, `ReimbursementPolicy`, `AuthController::abilitiesFor()`, sidebar check |

> **Masalah:** `Technician` tidak dibuat di `RolePermissionSeeder` tapi dipakai di 2 policy dan 1 controller. Setiap kali run seeder, role ini harus dibuat manual.

---

## 2. Permission yang Ada (38 item di `PermissionEnum`)

### Organizations (5)
`view_organization_all`, `view_organization_unit`, `create_organization`, `edit_organization`, `delete_organization`

### Users & Roles (5)
`view_user_all`, `view_user_unit`, `create_user`, `edit_user`, `delete_user`

### Employees (5)
`view_employee_all`, `view_employee_unit`, `create_employee`, `edit_employee`, `delete_employee`

### Attendance (3)
`view_attendance_all`, `view_attendance_unit`, `approve_attendance`

### Payroll (4)
`view_payroll_all`, `view_payroll_unit`, `process_payroll`, `approve_payroll`

### Procurement (7)
`view_pr_all`, `create_pr`, `approve_pr`, `view_po_all`, `create_po`, `view_grn_all`, `receive_grn`

### Cooperative (9)
`view_cooperative_member`, `manage_cooperative_member`, `manage_cooperative_dues`, `manage_cooperative_payment`, `view_cooperative_loan`, `manage_cooperative_loan`, `approve_cooperative_loan`, `access_cooperative_pos`, `view_cooperative_report`

---

## 3. Gap Analysis: Fitur yang Belum Dilindungi Role

Berikut daftar fitur/route yang **tidak memiliki pengecekan role di controller maupun policy**:

| Fitur | Route | Hanya dilindungi oleh |
|-------|-------|----------------------|
| **Dashboard** | `/dashboard` | Auth saja (semua role bisa lihat) |
| **Notifications** | `/notifications` | Auth saja |
| **Audit Logs** | `/audit-logs` | Auth saja |
| **Reports ERP** | `/reports` | Auth saja |
| **Departments** | `/departments` | Auth saja |
| **Job Grades** | `/job-grades` | Auth saja |
| **Positions** | `/positions` | Auth saja |
| **Work Shifts** | `/work-shifts` | Auth saja |
| **Salary Structures** | `/salary-structures` | Auth saja |
| **Shift Rosters** | `/shift-rosters` | Auth saja |
| **Spare Parts** | `/spare-parts` | Auth saja |
| **Warehouses** | `/warehouses` | Auth saja |
| **Clients** | `/clients` | Auth saja |
| **Vendors** | `/procurement/vendors` | Auth saja |
| **Employee Transfers** | `/employee-transfers` | Auth saja |
| **Employee Contracts** | `/employees/{}/contracts` | Auth saja |
| **ESS Portal** | `/ess/*` | Auth saja (hanya login check) |
| **Overtime** | `/overtime` | Policy (Employee bisa lihat) |
| **Petty Cash** | `/petty-cash` | Auth saja |
| **Bank Batches** | `/finance/bank-batches` | Auth saja |
| **Bank Reconciliation** | `/finance/bank-reconciliation` | Auth saja |
| **Chart of Accounts** | `/finance/chart-of-accounts` | Auth saja |
| **Journal Entries** | `/finance/journal-entries` | Auth saja |
| **Trial Balance** | `/finance/trial-balance` | Auth saja |
| **Balance Sheet** | `/finance/balance-sheet` | Auth saja |
| **Income Statement** | `/finance/income-statement` | Auth saja |
| **E-Faktur** | `/finance/efaktur` | Auth saja |
| **Points** | `/cooperative/points` | Auth saja |
| **Rewards** | `/cooperative/rewards` | Auth saja (hanya redemption yang dicek) |
| **SHU** | `/cooperative/shu` | Auth saja |
| **POS Categories** | `/cooperative/pos-categories` | Auth saja |
| **POS Transactions History** | `/cooperative/pos/transactions` | Auth saja |
| **POS Reports** | `/cooperative/pos/reports` | Auth saja |
| **POS SHU** | `/cooperative/pos/shu` | Auth saja |
| **Cooperative Payments** | `/cooperative/payments` | Auth saja |
| **Cooperative Ledger** | `/cooperative/ledger` | Auth saja |
| **Cooperative Dues** | `/cooperative/dues` | Auth saja |
| **Attendance Tracker** | `/attendances` | Auth (sidebar hide) |

> **Catatan penting:** Banyak fitur di atas hanya dilindungi oleh sidebar visibility (`adminOnly` di `AppSidebar.vue`), tapi route-nya sendiri tidak ada pengecekan role. Seorang user yang tahu URL-nya bisa langsung mengakses.

---

## 4. Gap Analysis: Permission vs. Fitur yang Tidak Punya Permission

Beberapa modul/fitur besar **tidak memiliki permission di `PermissionEnum`** sama sekali:

| Modul/Fitur | Permission? | 
|-------------|------------|
| **Finance - Budget/RKAP** | ❌ Tidak ada (pakai `hasRole` di policy) |
| **Finance - Invoices** | ❌ Tidak ada (pakai `hasRole` di policy) |
| **Finance - Bank Batches** | ❌ Tidak ada |
| **Finance - Bank Reconciliation** | ❌ Tidak ada |
| **Finance - Chart of Accounts** | ❌ Tidak ada |
| **Finance - Journal Entries** | ❌ Tidak ada |
| **Finance - Trial Balance** | ❌ Tidak ada |
| **Finance - Balance Sheet** | ❌ Tidak ada |
| **Finance - Income Statement** | ❌ Tidak ada |
| **Finance - E-Faktur** | ❌ Tidak ada |
| **Finance - Reimbursements** | ❌ Tidak ada (pakai `hasRole` di policy) |
| **Projects** | ❌ Tidak ada (pakai `hasRole` di policy) |
| **Asset Management** | ❌ Tidak ada (pakai `hasRole` di policy) |
| **Work Orders** | ❌ Tidak ada (pakai `hasRole` di policy) |
| **Leave** | ❌ Tidak ada (pakai `hasRole` di policy) |
| **Overtime** | ❌ Tidak ada (pakai `hasRole` di policy) |
| **HR Master Data** (departments, positions, dll) | ❌ Tidak ada |
| **Employee Transfers** | ❌ Tidak ada |
| **Employee Contracts** | ❌ Tidak ada |
| **ESS Portal** | ❌ Tidak ada |
| **Spare Parts / Warehouses** | ❌ Tidak ada |
| **Clients** | ❌ Tidak ada |
| **Vendors** | ❌ Tidak ada |
| **Audit Logs** | ❌ Tidak ada |
| **Reports ERP** | ❌ Tidak ada |
| **Notifications** | ❌ Tidak ada |
| **Cooperative - Points** | ❌ Tidak ada |
| **Cooperative - Rewards** | ❌ Tidak ada |
| **Cooperative - Redemption** | ❌ Tidak ada |
| **Cooperative - SHU** | ❌ Tidak ada |
| **Cooperative - POS Reports** | ❌ Tidak ada |
| **Cooperative - POS SHU** | ❌ Tidak ada |
| **Cooperative - POS Categories** | ❌ Tidak ada |
| **Cooperative - Loan Types** | ❌ Tidak ada |
| **Cooperative - Ledger** | ❌ Tidak ada |

---

## 5. Masalah Arsitektur: Dua Sistem Otorisasi Paralel

Sistem saat ini menggunakan **dua pendekatan otorisasi** yang tidak konsisten:

### A. Permission-based (Spatie)
Digunakan di: `EmployeePolicy`, `PayrollPolicy`, `PurchaseRequestPolicy`, `PurchaseOrderPolicy`, `CooperativeMemberPolicy`

### B. Role-based checking (`hasRole` / `hasAnyRole`)
Digunakan di: `BudgetPolicy`, `InvoicePolicy`, `ProjectPolicy`, `AssetPolicy`, `WorkOrderPolicy`, `LeavePolicy`, `OvertimeRequestPolicy`, `ReimbursementPolicy`, `LoanController`, `LoanTypeController`, `CooperativeReportController`, `PosApiController`, `RewardRedemptionController`, `CooperativeDuesApiController`, `CooperativePaymentApiController`

### C. Tidak ada check
Ratusan route lain (sekitar 60+ endpoint) tidak memiliki check role/permission apapun selain `auth`.

---

## 6. API Token Abilities vs Permission Spatie

`AuthController::abilitiesFor()` mendefinisikan **token abilities** terpisah dari permission Spatie:

| Role | Token Abilities |
|------|----------------|
| System Admin / Admin Pusat | `['*']` |
| Anggota | `member:read`, `member:write`, `cooperative:read`, `cooperative:write` |
| Employee | `ess:read`, `ess:write`, `attendance:read`, `attendance:write`, `payroll:read` |
| Technician | `work-orders:read`, `work-orders:write` |
| Pengurus Koperasi / Kasir | `cooperative:read`, `cooperative:write`, `pos:read`, `pos:write`, `reports:read` |

**Masalah:** Token abilities ini adalah string bebas yang tidak terhubung ke `PermissionEnum`. Ada dua sistem izin yang berbeda — satu untuk web (Spatie) dan satu untuk API (token abilities).

---

## 7. Rekomendasi Perbaikan

### Prioritas Tinggi 🔴

1. **Tambahkan role `Technician` ke `RolePermissionSeeder`**
   Role ini dipakai di policy dan `AuthController` tapi belum dibuat di seeder.

2. **Tambahkan permission ke `Site Manager` dan `Admin Unit`**
   Kedua role ini dipakai di policy tapi tidak punya permission satupun di seeder. Minimal tambahkan permission yang relevan dengan domain mereka.

3. **Tambahkan pengecekan role di controller finance**
   Semua route finance (`/finance/*`) tidak ada pengecekan role — siapa pun yang login bisa akses chart of accounts, journal entries, trial balance, dll.

4. **Lindungi HR Master Data**
   Route `/departments`, `/job-grades`, `/positions`, `/work-shifts`, `/salary-structures`, `/shift-rosters` hanya dilindungi `auth`.

5. **Lindungi route cooperative yang belum ada check**
   Points, Rewards, SHU, POS Categories, POS Reports, Ledger tidak memiliki pengecekan role.

### Prioritas Sedang 🟡

6. **Buat permission untuk modul Finance**
   Tambahkan ke `PermissionEnum`: `view_finance_all`, `manage_finance`, `manage_budget`, dll.

7. **Buat permission untuk modul HR Master Data**
   Tambahkan: `manage_departments`, `manage_positions`, `manage_job_grades`, dll.

8. **Buat permission untuk Project Management**
   Tambahkan: `view_project_all`, `manage_project`, dll.

9. **Buat permission untuk Asset & Work Order**
   Tambahkan: `view_asset_all`, `manage_asset`, `view_work_order_all`, `manage_work_order`, dll.

10. **Buat permission untuk Cooperative lengkap**
    Tambahkan: `manage_rewards`, `manage_points`, `manage_shu`, `manage_pos_reports`, `manage_loan_types`, dll.

### Prioritas Rendah 🟢

11. **Hapus role `Karyawan` jika tidak dipakai**
    Role `Karyawan` ada di seeder tapi tidak dicek di policy/controller manapun (semua check menggunakan `Employee` atau `hasRole('Employee')`).

12. **Selaraskan API token abilities dengan Spatie permission**
    Token abilities saat ini adalah string terpisah. Idealnya abilities API juga mengacu ke permission yang sama (atau minimal selaras).

13. **Standarisasi pendekatan otorisasi**
    Pilih salah satu: semua pakai permission Spatie, atau semua pakai role check. Campuran dua sistem saat ini membingungkan dan rawan celah.

14. **Tambahkan `Leave` dan `Overtime` permission**
    Saat ini pakai `hasRole` di policy. Sebaiknya ada permission agar bisa diberikan granular ke role yang memang butuh tanpa hardcode role di policy.

---

## 8. Mapping Ideal: Role → Fitur

| Role | Fitur yang Seharusnya Diakses |
|------|------------------------------|
| **System Admin** | Semua (existing) |
| **Admin Pusat** | Semua kecuali user management super admin (existing) |
| **Admin Unit** | Dashboard, Employee (unit), Attendance (unit), Payroll (unit), Projects (unit), Assets (unit), Work Orders (unit), Cooperative (unit) |
| **HR Pusat** | Employee (all), HR Master Data (all), Attendance (all), Leave (all), Overtime (all), Payroll (all), ESS |
| **HR Unit** | Employee (unit), HR Master Data (view), Attendance (unit), Leave (unit), Overtime (unit), Payroll (unit), ESS |
| **Finance Pusat** | Invoices, Budget/RKAP, Petty Cash, Bank Batches, Bank Reconciliation, Chart of Accounts, Journal, Trial Balance, Balance Sheet, Income Statement, E-Faktur, Reimbursements, Payroll (approve) |
| **Finance Unit** | Invoices (view unit), Petty Cash, Bank Batches, Bank Reconciliation, Reimbursements, Payroll (view unit) |
| **Project Manager** | Projects (manage), Assets (manage), Work Orders (manage), Procurement (all), Clients, Vendors |
| **Site Manager** | Projects (view unit), Assets (view/manage unit), Work Orders (view/manage unit) |
| **Technician** | Work Orders (own assigned), Assets (view assigned) |
| **Pengurus Koperasi** | Semua modul cooperative (manage members, dues, payments, loans, points, rewards, POS, reports) |
| **Kasir Koperasi** | POS (kasir), Members (view), Payments (manage), Loans (view), Rewards/Points (view) |
| **Anggota** | Kojayaku portal (self-service: simpanan, pinjaman, poin, transaksi, profil), self-only |

> Role `Karyawan` sebaiknya diganti menjadi `Employee` dan fungsinya dibedakan dari employee biasa yang hanya bisa lihat data sendiri di ESS.

---

## 9. Kesimpulan

**Kekurangan utama:**

1. **15 role terdaftar (13 di seeder + 2 di controller) tapi hanya 5 yang punya permission terdefinisi dengan baik** — sisanya kosong atau pakai hardcode role check
2. **~35 fitur/route tidak dilindungi role apapun** — hanya dilindungi `auth` middleware
3. **Permission yang ada (38) terlalu sedikit** dibanding fitur yang sudah dibangun (140+ pages, 50+ controllers)
4. **Role `Technician` tidak ada di seeder** tapi dipakai di production code
5. **Role `Karyawan` ada di seeder** tapi tidak dipakai di production code manapun
6. **API token abilities tidak sinkron** dengan Spatie permission system
7. **Dual authorization pattern** — campuran permission + role check yang tidak konsisten

**Rekomendasi singkat:** Minimal tambahkan `Technician` ke seeder, lindungi semua route finance & cooperative yang belum ada check, dan buat permission untuk modul-modul yang sudah ada (finance, projects, assets, HR master data, cooperative lanjutan).
