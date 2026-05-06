# Plan: Standarisasi Otorisasi — dari `hasRole` ke Spatie Permission

> **Dibuat:** 5 Mei 2026 — **Diperbarui:** 5 Mei 2026  
> **Status:** Plan — menunggu implementasi (Phase 0-3 API mobile sudah selesai, lihat `docs/improve2.md`)  
> **Referensi:** `docs/proses_bisnis/roles.md` (analisis gap), `docs/improve2.md` (Phase 0-5 audit)

---

## Konteks Terkini (5 Mei 2026)

ChatGPT telah menyelesaikan **Phase 0, 1, 2, dan 3** dari `docs/improve2.md`:

- **Phase 0** ✅ — Auth mobile API: login/logout/session/token rotation dengan abilities berbasis persona (`member:`, `ess:`, `work-orders:`, `technician:`, `admin:`)
- **Phase 1** ✅ — Member self-service API: `/api/v1/member/*` (15 endpoint)
- **Phase 2** ✅ — ESS mobile API: `/api/ess/*` (20 endpoint)
- **Phase 3** ✅ — Technician mobile API: `/api/technician/work-orders/*` (11 endpoint)

**Perubahan terhadap plan_role.md:**
1. API mobile sekarang sudah punya **token abilities sendiri** (`member:read`, `ess:write`, `work-orders:review`) yang merupakan string kustom, **bukan Spatie permission enum**
2. Ada **dua sistem ability yang berbeda**: web (Spatie `PermissionEnum`) vs API mobile (string abilities di Sanctum token)
3. Plan ini perlu diperbarui untuk mengakomodasi realitas tersebut — bukan memaksakan Spatie ke API mobile, tapi menyelaraskan keduanya

---

## Tujuan (Diperbarui)

1. **Ganti semua `hasRole()` / `hasAnyRole()` dengan Spatie permission** di seluruh policy dan controller **web** (Inertia)
2. **Perluas `PermissionEnum`** dari 38 permission menjadi ~70+ permission agar semua modul/fitur web terlindungi
3. **Perbaiki `RolePermissionSeeder`** — tambah role `Technician`, mapping permission lengkap untuk semua role
4. **Selaraskan API token abilities dengan Spatie permission** — bukan samakan string, tapi buat mapping agar abilities di `AuthController::abilitiesFor()` konsisten dengan permission web
5. **Standarisasi otorisasi frontend** pakai `v-can` directive
6. **Dokumentasikan ability matrix** untuk mobile team — agar token abilities (`member:read`, `ess:write`, dll) punya definisi jelas

---

## Arsitektur Saat Ini: Dua Sistem Ability

### Web (Inertia + Blade) — Spatie Permission
```
User → Role → Permission (PermissionEnum) → Policy / Gate / v-can
```
Contoh: `user->can('view_employee_all')` → dicek di `EmployeePolicy`

### API Mobile (Sanctum Token) — String Abilities
```
User → Token → abilities[] array of strings → Middleware ability:xxx
```
Contoh: `ability:member:read` → dicek di `routes/api.php`

### Mapping yang sudah jalan (AuthController::abilitiesFor())

| Persona | Token Ability String | Setara Spatie Permission |
|---------|---------------------|--------------------------|
| **System Admin / Admin Pusat** | `*` | Semua (bypass Gate) |
| **Anggota** | `member:read` | `view_cooperative_member` + `view_cooperative_loan` + `view_cooperative_report` |
| | `member:write` | `manage_cooperative_member` (self-only di controller) |
| | `cooperative:read` | `view_cooperative_member` + `view_cooperative_loan` |
| | `cooperative:write` | `manage_cooperative_member` (self-only) |
| **Employee** | `ess:read` | `view_employee_all` (scope unit) + `access_ess_portal` |
| | `ess:write` | `create_employee` (self-only) |
| | `attendance:read` | `view_attendance_all` (scope self/unit) |
| | `attendance:write` | (implisit, self-only) |
| | `payroll:read` | `view_payroll_all` (scope self) + `view_own_payslip` |
| **Technician** | `work-orders:read` | `view_work_order_all` (scope assigned) |
| | `work-orders:write` | `manage_work_order` (scope assigned) |
| | `work-orders:review` | `manage_work_order` (supervisor) |
| **Pengurus Koperasi** | `cooperative:read` | `view_cooperative_all` |
| | `cooperative:write` | `manage_cooperative_member` + `manage_cooperative_dues` + `manage_cooperative_payment` + `manage_cooperative_loan` + `approve_cooperative_loan` |
| | `pos:read` | `access_cooperative_pos` + `view_pos_reports` |
| | `pos:write` | `manage_pos_products` |
| | `reports:read` | `view_cooperative_report` |
| **Kasir Koperasi** | `cooperative:read` | `view_cooperative_member` + `view_cooperative_loan` |
| | `cooperative:write` | `manage_cooperative_payment` |
| | `pos:read` | `access_cooperative_pos` |
| | `pos:write` | (POS transaksi) |
| | `reports:read` | `view_cooperative_report` |

---

## Fase 1: Perluas `PermissionEnum`

### File: `app/Enums/PermissionEnum.php`

**38 permission existing tidak dihapus, hanya ditambah.** Berikut permission baru yang perlu dibuat:

### Finance & Accounting (12 permission baru)
```
view_invoice_all          | view_budget_all          | manage_budget
manage_petty_cash         | manage_bank_batch        | manage_bank_reconciliation
view_chart_of_accounts    | manage_chart_of_accounts | manage_journal_entries
view_trial_balance        | view_balance_sheet       | view_income_statement
manage_efaktur            | manage_reimbursement     | approve_reimbursement
```

### Projects (4)
```
view_project_all          | view_project_unit        | manage_project
manage_project_team
```

### Asset & Maintenance (6)
```
view_asset_all            | view_asset_unit          | manage_asset
view_work_order_all       | view_work_order_unit     | manage_work_order
```

### HR Master Data (6)
```
manage_departments        | manage_positions         | manage_job_grades
manage_work_shifts        | manage_salary_structures | manage_shift_rosters
```

### Employee Advanced (4)
```
manage_employee_transfer  | approve_employee_transfer
manage_employee_contract  | manage_employee_family
```

### Leave & Overtime (4)
```
view_leave_all            | view_leave_unit          | approve_leave
view_overtime_all         | view_overtime_unit       | approve_overtime
```

### ESS Portal (2)
```
access_ess_portal         | view_own_payslip
```

### Cooperative Extended (12)
```
manage_cooperative_points | manage_cooperative_rewards   | manage_cooperative_redemption
manage_cooperative_shu    | manage_cooperative_loan_types
manage_pos_categories     | manage_pos_products          | view_pos_reports
manage_pos_shu            | view_cooperative_ledger      | manage_cooperative_ledger
view_cooperative_all      | manage_cooperative_settings
```

### System (8)
```
manage_clients            | manage_vendors           | view_audit_logs
export_audit_logs         | manage_organizations     | manage_roles
manage_users              | view_reports
```

### Storage (3)
```
manage_spare_parts        | manage_warehouses        | view_stock
```

> **Total: 38 existing + ~65 baru = ~103 permission total**

---

## Fase 2: Perbaiki `RolePermissionSeeder`

### File: `database/seeders/RolePermissionSeeder.php`

### Tambah role `Technician`
```php
'Technician',  // tambahkan ke array $roles
```

### Mapping permission baru untuk setiap role

#### System Admin & Admin Pusat
```
Semua 103 permission (sync seperti existing)
```

#### HR Pusat
```
Existing: view_employee_all, create_employee, edit_employee, view_payroll_all, process_payroll
Tambah:   manage_departments, manage_positions, manage_job_grades, 
          manage_work_shifts, manage_salary_structures, manage_shift_rosters,
          view_leave_all, approve_leave, view_overtime_all, approve_overtime,
          access_ess_portal, manage_employee_contract, manage_employee_family
```

#### HR Unit
```
Existing: view_employee_unit, create_employee, edit_employee, view_payroll_unit, process_payroll
Tambah:   view_leave_unit, view_overtime_unit, access_ess_portal
```

#### Finance Pusat
```
Existing: view_payroll_all, process_payroll, approve_payroll, view_pr_all, view_po_all, view_grn_all
Tambah:   view_invoice_all, view_budget_all, manage_budget, manage_petty_cash,
          manage_bank_batch, manage_bank_reconciliation,
          view_chart_of_accounts, manage_chart_of_accounts, manage_journal_entries,
          view_trial_balance, view_balance_sheet, view_income_statement,
          manage_efaktur, manage_reimbursement, approve_reimbursement
```

#### Finance Unit
```
Existing: view_payroll_unit, process_payroll, view_pr_all, view_po_all, view_grn_all
Tambah:   view_budget_all (read only), manage_petty_cash, manage_bank_batch,
          view_chart_of_accounts, view_trial_balance, view_balance_sheet, view_income_statement
```

#### Admin Unit
```
Tambah:   view_employee_unit, view_attendance_unit, view_pr_all, view_po_all, view_grn_all,
          view_project_unit, view_asset_unit, view_work_order_unit,
          view_leave_unit, view_overtime_unit,
          manage_petty_cash, manage_reimbursement
```

#### Project Manager
```
Existing: view_pr_all, create_pr, approve_pr, view_po_all, create_po, view_grn_all, receive_grn
Tambah:   view_project_all, manage_project, manage_project_team,
          view_asset_all, manage_asset, view_work_order_all, manage_work_order,
          manage_clients, manage_vendors
```

#### Site Manager
```
Tambah:   view_project_unit, view_asset_unit, manage_asset, view_work_order_unit
```

#### Technician
```
Tambah:   view_asset_unit, view_work_order_unit
```

#### Pengurus Koperasi
```
Existing: view_cooperative_member, manage_cooperative_member, manage_cooperative_dues,
          manage_cooperative_payment, view_cooperative_loan, manage_cooperative_loan,
          approve_cooperative_loan, access_cooperative_pos, view_cooperative_report
Tambah:   manage_cooperative_points, manage_cooperative_rewards, manage_cooperative_redemption,
          manage_cooperative_shu, manage_cooperative_loan_types,
          manage_pos_categories, manage_pos_products, view_pos_reports,
          manage_pos_shu, view_cooperative_ledger, manage_cooperative_ledger,
          view_cooperative_all, manage_cooperative_settings
```

#### Kasir Koperasi
```
Existing: view_cooperative_member, manage_cooperative_payment, view_cooperative_loan, access_cooperative_pos
Tambah:   view_cooperative_report, view_pos_reports
```

#### Anggota
```
Existing: view_cooperative_member, view_cooperative_loan
Tambah:   (tidak ada tambahan — anggota hanya self-service via Kojayaku)
```

---

## Fase 3: Replace `hasRole` dengan Spatie Permission di Policy

### Files yang perlu diubah:

| Policy File | Perubahan |
|-------------|-----------|
| `BudgetPolicy.php` | `hasAnyRole` → `can('view_budget_all')` / `can('manage_budget')` |
| `InvoicePolicy.php` | `hasAnyRole` → `can('view_invoice_all')` |
| `LeavePolicy.php` | `hasAnyRole` → `can('view_leave_all')` / `can('view_leave_unit')` / `can('approve_leave')` |
| `OvertimeRequestPolicy.php` | `hasAnyRole` → `can('view_overtime_all')` / `can('view_overtime_unit')` / `can('approve_overtime')` |
| `ProjectPolicy.php` | `hasAnyRole` → `can('view_project_all')` / `can('view_project_unit')` / `can('manage_project')` |
| `AssetPolicy.php` | `hasAnyRole` → `can('view_asset_all')` / `can('view_asset_unit')` / `can('manage_asset')` |
| `WorkOrderPolicy.php` | `hasAnyRole` → `can('view_work_order_all')` / `can('view_work_order_unit')` / `can('manage_work_order')` |
| `ReimbursementPolicy.php` | `hasAnyRole` → `can('manage_reimbursement')` / `can('approve_reimbursement')` |

### Contoh transformasi:

**Sebelum** (`BudgetPolicy`):
```php
public function viewAny(User $user): bool
{
    return $this->hasAnyRole($user, ['System Admin', 'Admin Pusat', 'Finance Pusat', 'Finance Unit']);
}
```

**Sesudah**:
```php
public function viewAny(User $user): bool
{
    return $this->can($user, 'view_budget_all');
}
```

### Catatan untuk scope organization:
Untuk policy yang punya `sameOrganization()` logic (Asset, WorkOrder, Project), permission unit-level bisa dikombinasikan:

```php
public function view(User $user, Asset $asset): bool
{
    return $this->can($user, 'view_asset_all')
        || ($this->can($user, 'view_asset_unit') && $this->sameOrganization($user, $asset));
}
```

---

## Fase 4: Replace `hasRole` dengan Spatie Permission di Controller

### Files yang perlu diubah:

| Controller | Lokasi `hasRole` | Ganti dengan |
|------------|-----------------|-------------|
| `LoanController.php` | 4 inline check | Permission-based policy atau `can('view_cooperative_loan')` |
| `LoanTypeController.php` | 1 inline check | `can('manage_cooperative_loan_types')` |
| `CooperativeReportController.php` | 2 inline check | `can('view_cooperative_report')` |
| `RewardRedemptionController.php` | 2 inline check | `can('manage_cooperative_redemption')` |
| `PayrollApprovalController.php` | 1 inline check | `can('approve_payroll')` |
| `InvoiceController.php` | Cek inline | Policy sudah ada (`InvoicePolicy`), cukup panggil `$this->authorize()` |
| `PosApiController.php` | 2 inline check | `can('access_cooperative_pos')` |
| `CooperativeDuesApiController.php` | 1 inline check | `can('manage_cooperative_dues')` |
| `CooperativePaymentApiController.php` | 1 inline check | `can('manage_cooperative_payment')` |
| `LoanApiController.php` | 1 inline check | `can('view_cooperative_loan')` |
| `PosRegisterController.php` | Tanpa check | Tambah `can('access_cooperative_pos')` |
| `CooperativeDuesController.php` | Tanpa check | Tambah `can('manage_cooperative_dues')` |
| `CooperativePaymentController.php` | Tanpa check | Tambah `can('manage_cooperative_payment')` |
| `CooperativeLedgerController.php` | Tanpa check | Tambah `can('view_cooperative_ledger')` |
| `PointController.php` | Tanpa check | Tambah `can('manage_cooperative_points')` |
| `RewardController.php` | Tanpa check | Tambah `can('manage_cooperative_rewards')` |
| `AnnualShuController.php` | Tanpa check | Tambah `can('manage_cooperative_shu')` |
| `PosCategoryController.php` | Tanpa check | Tambah `can('manage_pos_categories')` |
| `PosProductController.php` | Tanpa check | Tambah `can('manage_pos_products')` |
| `PosSalesReportController.php` | Tanpa check | Tambah `can('view_pos_reports')` |
| `PosAnnualShuController.php` | Tanpa check | Tambah `can('manage_pos_shu')` |
| `PosTransactionHistoryController.php` | Tanpa check | Tambah `can('access_cooperative_pos')` |

### Finance controllers (semua belum ada check):
| Controller | Tambah permission |
|------------|------------------|
| `PettyCashAccountController` | `can('manage_petty_cash')` |
| `PettyCashTransactionController` | `can('manage_petty_cash')` |
| `FinanceBankController` | `can('manage_bank_batch')` |
| `BankReconciliationController` | `can('manage_bank_reconciliation')` |
| `ChartOfAccountController` | `can('manage_chart_of_accounts')` |
| `JournalEntryController` | `can('manage_journal_entries')` |
| `FinancialStatementController` | `can('view_trial_balance')` / `can('view_balance_sheet')` / `can('view_income_statement')` |
| `EfakturController` / `EfakturUiController` / `EfakturApiController` | `can('manage_efaktur')` |

### HR Master Data controllers (semua belum ada check):
| Controller | Tambah permission |
|------------|------------------|
| `DepartmentController` | `can('manage_departments')` |
| `PositionController` | `can('manage_positions')` |
| `JobGradeController` | `can('manage_job_grades')` |
| `WorkShiftController` | `can('manage_work_shifts')` |
| `SalaryStructureController` | `can('manage_salary_structures')` |
| `ShiftRosterController` | `can('manage_shift_rosters')` |

### Lainnya:
| Controller | Tambah permission |
|------------|------------------|
| `ClientController` | `can('manage_clients')` |
| `VendorController` | `can('manage_vendors')` |
| `EmployeeTransferController` | `can('manage_employee_transfer')` |
| `EmployeeContractController` | `can('manage_employee_contract')` |
| `AuditLogController` | `can('view_audit_logs')` |
| `AuditLogController::export` | `can('export_audit_logs')` |
| `SparePartController` | `can('manage_spare_parts')` |
| `WarehouseController` | `can('manage_warehouses')` |
| `UserController` | `can('manage_users')` |
| `RoleController` | `can('manage_roles')` |
| `OrganizationController` | `can('manage_organizations')` |
| `EssPortalController` | `can('access_ess_portal')` — untuk role employee |
| `ReportController` | `can('view_reports')` |

---

## Fase 5: Selaraskan API Token Abilities dengan Spatie Permission

### File: `app/Http/Controllers/Api/AuthController.php`

**Status saat ini:** `abilitiesFor()` sudah mengeluarkan token abilities berbasis persona (`member:read`, `ess:write`, `work-orders:review`). Ini sudah berfungsi baik untuk API mobile.

**Masalah:** String abilities ini tidak sinkron dengan `PermissionEnum`. Developer harus ingat dua set permission berbeda.

**Solusi:** Jangan ubah string abilities (karena mobile app sudah bergantung). Sebaliknya, **buat mapping dokumentasi** dan **harmonisasi penamaan** agar konsisten:

### Prinsip harmonisasi

| Scope | Token Ability | Spatie Permission |
|-------|--------------|-------------------|
| Member self-service | `member:read` → `view_cooperative_*` (self-only) | Permission web untuk admin (broad) |
| Employee ESS | `ess:read` → `view_employee_*` (self-only) | Permission web untuk HR (broad) |
| Technician field | `work-orders:read` → `view_work_order_*` (assigned) | Permission web untuk manager (broad) |
| Admin web | Tidak pakai API token | `*_all` / `*_unit` + semua permission |

### Yang perlu diubah di `AuthController::abilitiesFor()`:

1. **Tambahkan `Technician` abilities** (saat ini belum terdaftar di `$abilities` map)
2. **Tambahkan `Employee` abilities** (saat ini ada di route tapi belum di map role)

### Sebelum (cek role technician belum ada mapping):
```php
// Technician: abilities hanya implicit
```

### Sesudah:
```php
if ($user->hasRole('Technician')) {
    $abilities = ['work-orders:read', 'work-orders:write'];
}
```

### API controller tetap pakai ability middleware di route

Ini sudah benar — API mobile pakai `ability:member:read` di route, controller tidak perlu policy. Tidak ada perubahan untuk API controllers.

```php
// routes/api.php — existing, no change needed
Route::middleware(['auth:sanctum', 'ability:member:read'])->group(function () {
    Route::get('/v1/member/dashboard', [MemberSelfServiceController::class, 'dashboard']);
});
```

### Yang perlu ditambah: dokumentasi untuk mobile team

Buat section di `docs/api.md` yang menjelaskan mapping ability → fitur:

```
## Token Abilities

| Ability String | Bisa Akses |
|----------------|-----------|
| member:read | Dashboard, profil, savings, loan list, SHU, notifikasi anggota |
| member:write | Update profil, upload bukti bayar, apply loan, support ticket |
| ess:read | Dashboard ESS, profil, shift roster, payslip, compliance |
| ess:write | Update profil, apply leave/cancel, apply overtime, apply reimbursement |
| attendance:read | Today history, riwayat absensi |
| attendance:write | Check-in, check-out |
| work-orders:read | List work order, detail, timeline |
| work-orders:write | Start, complete, update checklist, upload evidence/parts, sync, escalate |
| work-orders:review | Reopen work order (supervisor) |
| cooperative:read | Lihat semua data anggota, pinjaman, tagihan |
| cooperative:write | Manage anggota, iuran, pembayaran, pinjaman, approval |
| pos:read | Akses POS, view produk, view report |
| pos:write | Transaksi POS, manage produk |
| reports:read | View laporan koperasi |
```

---

## Fase 6: Frontend — Gunakan `v-can` Directive

### Kondisi saat ini
- `HandleInertiaRequests` sudah share `auth.roles` dan `auth.permissions`
- `v-can` directive sudah ada di `appSetup.ts`
- `AppSidebar.vue` menggunakan `isMember`, `isEmployee` untuk hide/show menu

### Perubahan
- **Sidebar menu visibility** sebaiknya pakai `v-can` bukan role check — lebih aman karena permission bisa diberikan ke role manapun
- **Pastikan semua tombol aksi di halaman** (create, edit, delete, approve) dilindungi `v-can`

### Contoh di sidebar:
```vue
<!-- Sebelum: hardcode role -->
<template v-if="!isEmployee">
  <NavItem title="Attendance Tracker" ... />
</template>

<!-- Sesudah: pakai v-can -->
<template v-can="'view_attendance_all'">
  <NavItem title="Attendance Tracker" ... />
</template>
```

---

## Fase 7: Hapus Role yang Tidak Dipakai

### Role `Karyawan`
- Tidak dipakai di policy/controller manapun
- Semua check menggunakan role `Employee`
- **Rekomendasi:** hapus dari `RolePermissionSeeder` atau rename menjadi alias `Employee`

### Role `Employee`
- Perlu ditambahkan ke `RolePermissionSeeder` (saat ini hanya dipakai implisit via controller)
- Mapping permission: `view_employee_unit`, `access_ess_portal`, `view_attendance_unit`, `view_payroll_unit`, `view_own_payslip`

---

## Fase 8: Bersihkan `Gate::before` & `BasePolicy`

### Hapus `hasAnyRole` dari `BasePolicy`
Setelah semua policy bermigrasi ke permission, `hasAnyRole` helper di `BasePolicy` bisa dihapus.

### Update `AppServiceProvider::registerPolicies()`
Pastikan `Gate::before` hanya tetap untuk bypass System Admin (existing):
```php
Gate::before(fn ($user): ?bool => $user->hasRole('System Admin') ? true : null);
```
Ini tidak perlu diubah — System Admin selalu bypass semua Gate.

---

## Urutan Implementasi (Rekomendasi)

| Fase | Estimasi File | Risiko | Dependensi |
|------|--------------|--------|------------|
| **Fase 1** — Perluas `PermissionEnum` | 1 file | Rendah | Tidak ada |
| **Fase 2** — Perbaiki `RolePermissionSeeder` | 1 file | Rendah | Fase 1 |
| **Fase 3** — Replace policy | 8 file | Sedang | Fase 2 |
| **Fase 4** — Replace controller + tambah check | ~30 file | Sedang-tinggi | Fase 3 |
| **Fase 5** — Selaraskan API token abilities | ~2 file | Rendah | Tidak ada (independent) |
| **Fase 6** — Frontend `v-can` | ~5 file | Rendah | Fase 3 |
| **Fase 7** — Hapus role tidak dipakai | 1 file | Rendah | Fase 2 |
| **Fase 8** — Bersihkan BasePolicy | 2 file | Rendah | Fase 3 |

### Cara menjalankan:
1. **Fase 1-2 dulu** — permission baru dibuat tanpa mengubah policy controller. Aman.
2. **Fase 5 bisa paralel** — tidak dependen pada fase lain. Hanya update `AuthController` dan docs.
3. **Fase 3** — ubah policy satu per satu, jalankan test terkait di setiap policy.
4. **Fase 4** — tambah `$this->authorize()` atau `can()` di constructor/action controller.
5. **Fase 6-8** — cleanup setelah semua jalan.
6. **Setiap fase selesai, run:** `php artisan db:seed --class=RolePermissionSeeder` dan `php artisan test --compact`

### Catatan: Jangan sentuh API mobile routes
**38 endpoint mobile** (`/api/v1/member/*`, `/api/ess/*`, `/api/technician/*`) yang sudah dibangun di Phase 0-3 **TIDAK perlu disentuh**. Permission web Spatie dan token ability Sanctum adalah dua sistem berbeda untuk dua platform berbeda:

| Platform | Auth Method | Authorization |
|----------|------------|---------------|
| Web (Inertia) | Session + CSRF | Spatie Permission via Policy/Gate |
| Mobile API | Sanctum Token | Ability middleware di route |

---

## Fase Bonus: Dokumentasi Mobile Ability Matrix

### File baru: `docs/proses_bisnis/ability_matrix.md`

Tujuan: Tim mobile perlu tahu ability apa yang diperlukan untuk fitur mana. Bikin tabel referensi:

### Member App Abilities

| Screen | Method | Ability |
|--------|--------|---------|
| Dashboard | GET | `member:read` |
| Profile | GET/PUT | `member:read` / `member:write` |
| Savings | GET | `member:read` |
| Dues Invoices | GET | `member:read` |
| Payment History | GET | `member:read` |
| Upload Payment Proof | POST | `member:write` |
| Loans | GET | `member:read` |
| Apply Loan | POST | `member:write` |
| SHU | GET | `member:read` |
| Notifications | GET | `member:read` |
| Support Tickets | GET/POST | `member:read` / `member:write` |

### ESS App Abilities

| Screen | Method | Ability |
|--------|--------|---------|
| Dashboard | GET | `ess:read` |
| Profile | GET/PUT | `ess:read` / `ess:write` |
| Attendance Today | GET | `attendance:read` |
| Attendance History | GET | `attendance:read` |
| Check-in | POST | `attendance:write` |
| Check-out | POST | `attendance:write` |
| Geofence | GET | `attendance:read` |
| Shift Roster | GET | `ess:read` |
| Leaves | GET | `ess:read` |
| Apply Leave | POST | `ess:write` |
| Cancel Leave | POST | `ess:write` |
| Overtime | GET | `ess:read` |
| Apply Overtime | POST | `ess:write` |
| Reimbursements | GET | `ess:read` |
| Apply Reimbursement | POST | `ess:write` |
| Payslips | GET | `ess:read` |
| Download Payslip | GET | `ess:read` |
| Compliance | GET | `ess:read` |
| Notifications | GET | `ess:read` |

### Technician App Abilities

| Screen | Method | Ability |
|--------|--------|---------|
| Work Orders | GET | `work-orders:read` |
| Work Order Detail | GET | `work-orders:read` |
| Start WO | POST | `work-orders:write` |
| Complete WO | POST | `work-orders:write` |
| Update Checklist | POST | `work-orders:write` |
| Upload Attachment | POST | `work-orders:write` |
| Use Spare Part | POST | `work-orders:write` |
| Sync Offline | POST | `work-orders:write` |
| Timeline | GET | `work-orders:read` |
| Escalate | POST | `work-orders:write` |
| Reopen (supervisor) | POST | `work-orders:review` |

---

## Verifikasi

Setelah semua fase selesai, verifikasi dengan:

```bash
# 1. Seeder berjalan tanpa error
php artisan db:seed --class=RolePermissionSeeder

# 2. Permission count sesuai (~103)
php artisan tinker --execute="echo \App\Enums\PermissionEnum::cases() count: " . count(\App\Enums\PermissionEnum::cases());

# 3. Cek permission per role
php artisan tinker --execute="echo Spatie\Permission\Models\Role::with('permissions')->get()->map(fn(\$r) => \$r->name . ': ' . \$r->permissions->count())->join(PHP_EOL);"

# 4. Test semua policy
php artisan test --compact --filter=Policy

# 5. Test controller yang diubah
php artisan test --compact --filter=Loan
php artisan test --compact --filter=Cooperative
php artisan test --compact --filter=Finance

# 6. Run semua test
php artisan test --compact
```

---

## Catatan Penting

- **Dua sistem authorization berbeda untuk dua platform berbeda** — ini disengaja:
  - **Web (Inertia):** Spatie Permission + Policy/Gate + `v-can` — granular, admin-oriented
  - **Mobile API:** Sanctum token abilities — simpel, string-based, self-service oriented
  - Jangan memaksakan salah satu ke platform lain
- **Jangan hapus `Gate::before` System Admin bypass** — ini disengaja agar admin selalu bisa akses tanpa perlu mapping 103 permission
- **Permission `*_all` vs `*_unit`** — pola scope: `_all` untuk pusat (lihat semua), `_unit` untuk unit (hanya organization sendiri)
- **Jangan sentuh `Login`, `Register`, `Password` routes** — otentikasi tidak perlu permission
- **`HasOrganizationScope` trait** — file ini TIDAK perlu diubah karena scope data berdasarkan role `System Admin, Admin Pusat, HR Pusat, Finance Pusat` masih valid setelah migrasi
- **API mobile routes (`/api/v1/member/*`, `/api/ess/*`, `/api/technician/*`) sudah selesai** — 38 endpoint dengan ability middleware yang berfungsi. Jangan disentuh di plan ini.
- **Phase 4 dari `improve2.md`** (operator procedure hardening) adalah kelanjutan natural setelah plan ini selesai — karena role matrix dan policy yang rapi adalah prerequisite untuk approval lintas modul dan audit trail.
