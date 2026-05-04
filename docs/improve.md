# KojayaPro - Improvement & Recommendation Plan

**Tanggal Analisis:** 4 Mei 2026
**Versi:** 1.0.0
**Status:** Active Development

---

## Statistik Codebase Saat Ini

| Komponen | Jumlah |
|---|---|
| Models | 82 |
| Controllers | 73 |
| Vue Pages | 109 |
| Shared Components | 28 (+127 UI components) |
| Services | 31 |
| Migrations | 96 |
| Tests | 72 files (272 passed, 1614 assertions) |
| Factories | 40 (dari 82 models) |
| Policies | 14 |
| Form Requests | 100 |

---

## P0 - KRITIS: Keamanan (Security)

### 1. Implementasi Laravel Policies

**Masalah:** Tidak ada `app/Policies/` directory. Semua authorization dilakukan manual via `hasRole()` / `hasAnyRole()` di controller. `PermissionEnum` sudah dibuat tapi **tidak digunakan sama sekali**.

**Dampak:**
- Tidak bisa pakai `$this->authorize()` di controller
- Tidak bisa pakai `@can` / `v-can` di Vue frontend
- Logika authorization tersebar dan duplikat di banyak controller
- Sulit di-maintain dan rentan error

**Aksi:**
- [x] Buat Policy untuk setiap model utama: Employee, Project, Invoice, Payroll, Leave, Overtime, WorkOrder, Asset, Reimbursement, Budget, CooperativeMember, PurchaseRequest, PurchaseOrder
- [x] Daftarkan Policies di `AuthServiceProvider` atau auto-discovery
- [x] Implementasi `Gate::before()` untuk System Admin bypass
- [x] Gunakan `PermissionEnum` yang sudah ada untuk permission checks
- [ ] Ganti semua inline `hasRole()` di controller dengan `$this->authorize()`
- [x] Tambah `v-can` directive di Vue components

**Progress saat ini:**
- Policy utama sudah tersedia dan didaftarkan lewat `AppServiceProvider`; base `Controller` sekarang memakai `AuthorizesRequests`, sehingga `$this->authorize()` dapat dipakai konsisten di controller.
- Endpoint kritis `LeaveController::updateStatus`, `OvertimeController::approve/reject/destroy`, `PayrollController` actions, dan ESS access karyawan sudah memakai policy/Form Request authorization.
- Inline `hasRole()` masih tersisa di beberapa controller untuk kebutuhan query scoping, UI ability flags, dan modul yang belum dimigrasi penuh; ini perlu dilanjutkan bertahap agar tidak mengubah perilaku akses secara besar-besaran sekaligus.

**Files terdampak:**
- Baru: `app/Policies/*.php` (~15 files)
- Edit: Semua controller yang ada `hasRole()` checks
- Edit: `app/Providers/AppServiceProvider.php` atau `AuthServiceProvider`

---

### 2. Perbaiki Authorization di TechnicianWorkOrderController

**Masalah:** Authorization di-comment out di baris 36, sehingga user manapun bisa lihat work order manapun.

```php
// File: app/Http/Controllers/Api/TechnicianWorkOrderController.php:36
// if ($workOrder->assigned_to !== auth()->id()) { abort(403); }
```

**Aksi:**
- [x] Uncomment dan perbaiki authorization check
- [x] Tambah authorization di `start()` dan `complete()` methods
- [x] Verifikasi bahwa technician hanya bisa akses work order yang di-assign ke mereka

---

### 3. Perbaiki LogActivity Middleware Bug

**Masalah:** Operator precedence salah di middleware audit logging.

```php
// File: app/Http/Middleware/LogActivity.php
// BUG: evaluasinya (Auth::check() && POST) || PUT || DELETE
if (Auth::check() && $request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE'))

// FIX: tambah kurung
if (Auth::check() && ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')))
```

**Dampak:** PUT/DELETE requests dari user yang belum login tetap di-log, memenuhi audit log dengan data yang tidak berguna.

**Aksi:**
- [x] Perbaiki operator precedence dengan menambahkan kurung

---

### 4. Set Sanctum Token Expiration

**Masalah:** `config/sanctum.php` - `expiration` diset `null`. Token API tidak pernah kedaluwarsa, riskan jika token bocor.

**Aksi:**
- [x] Set token expiration ke reasonable value (misal 30 hari atau 90 hari)
- [ ] Implementasi token rotation mechanism
- [x] Tambah artisan command untuk cleanup expired tokens
- [x] Tambah token abilities (permissions) untuk mobile API

**Progress saat ini:**
- `SANCTUM_TOKEN_EXPIRATION` default 30 hari dan scheduler menjalankan `sanctum:prune-expired --hours=24`.
- API routes sekarang memakai named rate limiter dan Sanctum ability middleware (`profile:read`, `cooperative:*`, `work-orders:*`, `employee-documents:*`, `reports:read`, `pos:*`).
- Token rotation penuh masih belum dibuat karena belum ada endpoint login/token issuance khusus mobile di codebase.

```php
// config/sanctum.php
'expiration' => 30 * 24 * 60, // 30 hari dalam menit
```

---

### 5. Tambah Authorization di Endpoint Tanpa Auth Check

**Masalah:** Beberapa endpoint kritis tidak punya authorization:

| Controller | Method | Masalah |
|---|---|---|
| `LeaveController` | `updateStatus()` | Siapapun bisa approve/reject leave |
| `OvertimeController` | `approve()` / `reject()` | Tanpa authorization |
| `PayrollController` | `generate()` | Tanpa authorization |
| `EmployeeController` | `enableEssAccess()` | Tanpa authorization |
| `PayrollApprovalController` | `approve()` / `reject()` | Perlu diverifikasi |

**Aksi:**
- [x] Tambah authorization checks di semua method di atas
- [x] Pastikan hanya role yang berwenang (HR Manager, System Admin) yang bisa approve

---

### 6. Perbaiki `StoreBudgetRequest::authorize()` yang Return `false`

**Masalah:** Form request ini memblokir semua pembuatan budget.

```php
// File: app/Http/Requests/StoreBudgetRequest.php
public function authorize(): bool
{
    return false; // BUG: ini menolak semua request
}
```

**Aksi:**
- [x] Ganti `return false` ke `return true` atau implementasi proper authorization
- [x] Check Form Requests lainnya yang mungkin punya masalah sama: `UpdateBudgetRequest`, `StoreEmployeeFamilyRequest`, `UpdateEmployeeFamilyRequest`, `StoreMedicalCheckupRequest`, `UpdateMedicalCheckupRequest`

---

## P1 - TINGGI: Arsitektur & Kualitas Kode

### 7. Buat Form Requests untuk Semua Controller dengan Inline Validation

**Masalah:** ~12 controller menggunakan `$request->validate()` langsung di controller, bukan Form Request class terpisah. Ini melanggar separation of concerns dan best practice Laravel.

**Controller yang perlu Form Requests:**

| Controller | Form Requests Dibutuhkan |
|---|---|
| `EmployeeController` | `StoreEmployeeRequest`, `UpdateEmployeeRequest` |
| `AttendanceController` | `StoreAttendanceRequest` |
| `ProjectController` | `StoreProjectRequest`, `UpdateProjectRequest` |
| `LeaveController` | `StoreLeaveRequest` |
| `OvertimeController` | `StoreOvertimeRequest` |
| `ReimbursementController` | `StoreReimbursementRequest` |
| `WorkOrderController` | `StoreWorkOrderRequest` |
| `UserController` | `StoreUserRequest`, `UpdateUserRequest` |
| `PayrollController` | `GeneratePayrollRequest` |
| `RoleController` | `UpdateRoleRequest` |

**Aksi:**
- [x] Buat Form Request untuk setiap controller di atas
- [x] Pindahkan validation rules dari controller ke Form Request
- [x] Tambah custom error messages (Indonesian) di Form Request utama dan request CRUD baru
- [x] Update controller untuk menggunakan Form Request yang sudah dibuat

**Progress saat ini:**
- `StoreEmployeeRequest`, `UpdateEmployeeRequest`, `StoreAttendanceRequest`, `StoreProjectRequest`, `UpdateProjectRequest`, `StoreLeaveRequest`, `StoreOvertimeRequest`, `StoreReimbursementRequest`, `StoreWorkOrderRequest`, `StoreUserRequest`, `UpdateUserRequest`, dan `UpdateRoleRequest` sudah tersedia di codebase
- Batch lanjutan juga sudah menutup tiga inline validation yang tersisa di flow approval terkait: `RejectOvertimeRequest`, `RejectReimbursementRequest`, dan `UpdateLeaveStatusRequest`
- Audit lanjutan menambahkan Form Request untuk controller CRUD dan endpoint khusus di luar daftar awal, termasuk Department, Position, JobGrade, WorkShift, Warehouse, Petty Cash, Salary Structure, Organization, Asset, Spare Part, Budget, Project Team/Task/Resource/Gantt, Payroll THR/approval export, Attendance API, eFaktur, Finance Bank, Report, dokumen karyawan, dan technician checklist
- Audit terbaru tidak menemukan lagi `$request->validate()` atau `->validate()` inline di `app/Http/Controllers`

---

### 8. Buat Shared Frontend Utilities

**Masalah:** Banyak kode yang di-duplikat di 20+ halaman Vue.

**Utilities yang perlu dibuat:**

#### a. `resources/js/lib/formatters.ts`
```typescript
// Fungsi yang saat ini duplikat di 20+ halaman
export function formatCurrency(amount: number | string | null): string
export function formatDate(date: string | null): string
export function formatDateRange(start: string, end: string): string
export function formatNumber(num: number): string
export function formatPercentage(value: number): string
```

**Progress saat ini:**
- `resources/js/lib/formatters.ts` sudah tersedia dan dipakai lintas halaman seperti `PettyCash`, `Reimbursement`, `Project`, `Invoice`, `SalaryStructure`, Procurement, Project Finance, Dashboard, dan halaman koperasi
- Batch lanjutan menambahkan `formatDateTime` untuk audit log dan menghapus formatter currency lokal yang masih tersisa di halaman/komponen utama
- Audit terbaru tidak menemukan lagi local `formatCurrency` berbasis `Intl.NumberFormat` di `resources/js/pages` dan `resources/js/components`

#### b. `resources/js/composables/useTableFilters.ts`
```typescript
// Pattern yang duplikat di 15+ halaman
export function useTableFilters(filters: Ref<Record<string, string>>)
// Menyediakan: search, debounced filter, router.get with preserveState
```

**Progress saat ini:**
- `useTableFilters.ts` sudah tersedia dan sudah mulai dipakai di halaman `Budget`, `SalaryStructure`, `Position`, `Department`, `Reimbursement`, `Project`, dan `Invoice`
- Halaman lain yang masih memakai `watch + debounce + router.get` manual bisa dilanjutkan migrasinya bertahap

#### c. `resources/js/components/ConfirmDialog.vue`
- Ganti 28 raw `confirm()` browser dialog
- Styled, accessible confirmation dialog
- Support untuk judul, pesan, variant (danger/warning), confirm/cancel callbacks

**Progress saat ini:**
- `ConfirmDialog.vue` sudah tersedia dan mulai digunakan di halaman `Client`, `User`, `Department`, `Position`, `PettyCash`, `Reimbursement`, `Overtime`, `Leave`, `Project`, `Project Resources`, `Budget`, `Organization`, `Employee`, `Assets`, `SalaryStructure`, `ProjectTeam`, `ProjectDocuments`, serta beberapa halaman `Procurement` (PR/PO/GRN)
- Raw `confirm()` di halaman Vue utama sudah dimigrasikan; reject flow yang sebelumnya memakai `prompt()` di `Procurement/PurchaseRequests/Show.vue` juga sudah diganti ke dialog form
- Audit terbaru tidak lagi menemukan `confirm()` atau `prompt()` mentah di `resources/js/pages` dan `resources/js/components`

#### d. `resources/js/components/EmptyState.vue`
- Empty state dengan icon, title, description, optional action button
- Digunakan di 10+ halaman yang masing-masing bikin sendiri

**Progress saat ini:**
- `EmptyState.vue` sudah dibuat
- `DataTable.vue` sudah memakai `EmptyState` sebagai empty-state default sehingga adopsinya otomatis mengikuti halaman yang memakai `DataTable`

#### e. `resources/js/components/FilterBar.vue`
- Reusable filter bar dengan search, organization select, status select
- Digunakan di 15+ halaman

**Progress saat ini:**
- `FilterBar.vue` sudah tersedia dan sekarang mulai dipakai di `Budget/Index.vue`, `Client/Index.vue`, `Department/Index.vue`, `Position/Index.vue`, dan `SalaryStructure/Index.vue`
- Komponen ini sekarang mendukung mode tanpa search input sehingga cocok untuk halaman yang hanya punya select filter
- Batch lanjutan masih bisa meneruskan adopsi `FilterBar` ke halaman list lain yang masih menyusun blok filter manual

#### f. `resources/js/components/StatsCard.vue`
- Stats card dengan icon, label, value, trend indicator
- Digunakan di 10+ halaman dashboard/module index

**Progress saat ini:**
- `StatsCard.vue` sudah dibuat
- Komponen ini sudah mulai dipakai di halaman `Client/Index.vue` untuk menggantikan card statistik yang sebelumnya hardcoded

**Aksi:**
- [x] Buat `lib/formatters.ts` dan refaktor 20+ halaman yang pakai `formatCurrency`
- [x] Buat `composables/useTableFilters.ts` dan refaktor halaman prioritas
- [x] Buat `ConfirmDialog.vue` dan ganti raw `confirm()` / `prompt()` di halaman Vue utama
- [x] Buat `EmptyState.vue`, `FilterBar.vue`, `StatsCard.vue`

---

### 9. Hapus Duplicate Components dan File Backup

**Masalah:** Ada 8 file component yang terduplikat dan 1 file backup di version control.

**Duplicate Components:**
- `components/Employee/` vs `components/Employee/Employee/` (4 files)
- `components/Compliance/` vs `components/Compliance/Compliance/` (2 files)
- `components/Status/` vs `components/Status/Status/` (2 files)

**File Backup:**
- `routes/web.php.backup`

**Aksi:**
- [x] Hapus direktori duplikat yang nested: `Employee/Employee/`, `Compliance/Compliance/`, `Status/Status/`
- [x] Hapus `routes/web.php.backup`
- [x] Hapus `TestJob` dari `app/Jobs/` (development artifact)
- [x] Verifikasi semua import path setelah penghapusan

**Progress saat ini:**
- Direktori duplikat nested yang disebutkan di atas tidak lagi ditemukan di codebase
- `routes/web.php.backup` juga tidak ditemukan
- `app/Jobs/` saat ini hanya berisi job aplikasi yang valid

---

### 10. Manfaatkan DataTable dan UI Components yang Sudah Ada

**Masalah:** `DataTable.vue` component sudah ada dan lengkap tapi hanya 1 dari ~20 halaman tabel yang menggunakannya. `Select` component juga ada tapi semua halaman pakai native `<select>`.

**Aksi:**
- [x] Refaktor halaman-halaman tabel berikut untuk pakai `DataTable`: Employee/Index, Client/Index, Invoice/Index, Payroll/Index, Overtime/Index, Reimbursement/Index, WorkOrders/Index, Assets/Index, Warehouses/Index, SpareParts/Index, AuditLogs/Index, User/Index, Cooperative/Members/Index
- [x] Ganti native `<select>` dengan `Select` component dari `ui/select/` pada halaman indeks prioritas P1 dan komponen Audit
- [x] Gunakan `StatusBadge` component untuk semua status display yang sebelumnya define `statusColors` locally

**Progress saat ini:**
- `DataTable` sudah dipakai di beberapa halaman procurement dan sekarang juga mulai diadopsi di `Client/Index.vue`, `Invoice/Index.vue`, `Reimbursement/Index.vue`, `Overtime/Index.vue`, `User/Index.vue`, `Assets/Index.vue`, `Employee/Index.vue`, `Payroll/Index.vue`, `Warehouses/Index.vue`, `WorkOrders/Index.vue`, `SpareParts/Index.vue`, `Cooperative/Members/Index.vue`, dan daftar audit log melalui `components/Audit/AuditLogList.vue`
- `StatusBadge` sudah dipakai pada tabel yang sudah dimigrasikan tersebut, termasuk label status kustom pada halaman yang sebelumnya memakai badge manual
- `FilterBar` juga sudah dipakai di area audit log dan halaman anggota koperasi sehingga adopsi filter bersama makin merata
- `SelectFilter.vue` dibuat sebagai wrapper `ui/select`, lalu dipakai di halaman indeks prioritas P1 dan komponen audit
- Audit terbaru tidak menemukan native `<select>` di halaman indeks prioritas P1: Employee, Client, Invoice, Payroll, Overtime, Reimbursement, WorkOrders, Assets, Warehouses, SpareParts, User, Cooperative/Members, dan AuditLogList/Filter

---

### 11. Tambah Rate Limiting di API Routes

**Masalah:** API routes (`routes/api.php`) tidak punya throttle middleware. Rentan terhadap brute force dan abuse.

**Aksi:**
- [x] Tambah `throttle:60,1` middleware untuk API routes umum
- [x] Tambah rate limiter untuk auth/login flow
- [x] Tambah rate limiter untuk write endpoints (POST, PUT, DELETE)
- [x] Konfigurasi custom rate limiter di `AppServiceProvider`

**Progress saat ini:**
- `routes/api.php` sudah memakai `throttle:api` untuk read endpoints dan `throttle:api-write` untuk write endpoints
- Custom rate limiter `api` dan `api-write` sudah terdaftar di `AppServiceProvider`
- Fortify login limiter juga sudah aktif untuk authentication flow

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // API routes
});
```

---

### 12. Implementasi Inertia Deferred Props

**Masalah:** Semua data di-load sebelum page render, menyebabkan initial load lambat. Inertia v2 deferred props tidak digunakan sama sekali.

**Aksi:**
- [x] Implementasi deferred props di halaman berat: Dashboard, Reports, Cooperative/Members/Index, Payroll/Index
- [x] Tambah skeleton/loading fallback pada area deferred prioritas
- [x] Manfaatkan pola Inertia v2 untuk deferred props di halaman prioritas

**Progress saat ini:**
- Deferred props sudah dipakai di `DashboardController`, `ReportController`, `PayrollController`, dan `CooperativeMemberController`
- Coverage arsitektur untuk deferred props sudah ada di `tests/Feature/P1ArchitectureTest.php`
- Skeleton/loading fallback masih bisa dilanjutkan sebagai batch UI berikutnya

```php
// Controller
return Inertia::render('Dashboard', [
    'stats' => Inertia::defer(fn() => $this->getStats()),
    'charts' => Inertia::defer(fn() => $this->getChartData()),
]);
```

---

### 13. Perbaiki Service Instantiation

**Masalah:** `PayrollController` meng-instantiate service dengan `new` bukan dependency injection.

```php
// Salah
$pph21Service = new Pph21TerService;

// Benar
public function __construct(
    private Pph21TerService $pph21Service
) {}
```

**Aksi:**
- [x] Ganti manual instantiation ke constructor injection di PayrollController
- [x] Audit semua controller untuk pattern yang sama

**Progress saat ini:**
- Audit controller saat ini tidak lagi menemukan pattern `new ...Service` di layer controller
- Area ini terlihat sudah dirapikan sebelumnya, sehingga fokus P1 yang masih aktif bergeser ke Form Request dan refaktor UI/table

---

## P2 - SEDANG: Testing

### 14. Buat Model Factories untuk Model yang Sering Di-test

**Masalah:** Hanya 9 factory untuk 83 models. Banyak test membuat model secara manual dengan `Model::create()`.

**Factories yang perlu dibuat (prioritas berdasarkan frekuensi penggunaan di tests):**

| Priority | Factory | Model |
|---|---|---|
| Tinggi | `ProjectFactory` | Project |
| Tinggi | `AssetFactory` | Asset |
| Tinggi | `WorkOrderFactory` | WorkOrder |
| Tinggi | `MaintenanceScheduleFactory` | MaintenanceSchedule |
| Tinggi | `BudgetFactory` | Budget, BudgetLine |
| Tinggi | `VendorFactory` | Vendor |
| Tinggi | `PurchaseRequestFactory` | PurchaseRequest |
| Tinggi | `PurchaseOrderFactory` | PurchaseOrder |
| Tinggi | `AttendanceFactory` | Attendance |
| Tinggi | `LeaveFactory` | Leave |
| Tinggi | `PayrollFactory` | Payroll |
| Sedang | `DepartmentFactory` | Department |
| Sedang | `PositionFactory` | Position |
| Sedang | `JobGradeFactory` | JobGrade |
| Sedang | `WorkShiftFactory` | WorkShift |
| Sedang | `CooperativeMemberFactory` | CooperativeMember |
| Sedang | `PosProductFactory` | PosProduct |
| Sedang | `PosCategoryFactory` | PosCategory |
| Sedang | `ReimbursementFactory` | Reimbursement |
| Sedang | `PettyCashAccountFactory` | PettyCashAccount |
| Rendah | `NotificationFactory` | Notification |
| Rendah | `SparePartFactory` | SparePart |
| Rendah | `WarehouseFactory` | Warehouse |

**Aksi:**
- [x] Buat factory untuk 15+ model prioritas tinggi
- [x] Refaktor tests yang ada untuk menggunakan factory
- [x] Tambah custom states di factory yang sudah ada (misal: `InvoiceFactory::overdue()`)

**Progress saat ini:**
- Factory coverage naik jauh dari baseline awal. Codebase sekarang memiliki factory untuk area Project, Asset, Work Order, Maintenance Schedule, Budget/BudgetLine, Vendor, Purchase Request, Purchase Order, Attendance, Leave, Payroll, Cooperative Member, Reimbursement, Petty Cash Account, Warehouse, Spare Part, Department, Position, JobGrade, WorkShift, Salary Structure, Shift Roster, Work Order checklist/parts, dan model pendukung lain.
- Tests P2 yang baru ditambahkan sudah memakai factory sebagai sumber data utama, sehingga setup test tidak lagi bergantung pada `Model::create()` manual di banyak modul prioritas.
- `InvoiceFactory::overdue()` sudah ditambahkan sebagai state kustom untuk memperkaya coverage skenario tagihan jatuh tempo.
- Factory minor yang sempat tertinggal kini juga sudah tersedia, termasuk `PosProductFactory`, `PosCategoryFactory`, dan `NotificationFactory`, lalu mulai dipakai di coverage test POS dan notification.

---

### 15. Ganti Stub Tests dengan Tests Asli

**Masalah:** 3 test file hanya berisi placeholder `get('/')`.

| File | Status |
|---|---|
| `tests/Feature/UserManagementTest.php` | Stub - hanya `get('/')` |
| `tests/Feature/RoleManagementTest.php` | Stub - hanya `get('/')` |
| `tests/Feature/Feature/EmployeeScopeTest.php` | Stub - duplikat |

**Aksi:**
- [x] `UserManagementTest` - test CRUD user, assign role, assign organization, delete user
- [x] `RoleManagementTest` - test list roles, update permissions, role-based access
- [x] Hapus `tests/Feature/Feature/EmployeeScopeTest.php` (duplikat dari `tests/Feature/EmployeeScopeTest.php`)

**Progress saat ini:**
- `UserManagementTest` dan `RoleManagementTest` sudah berisi skenario feature test nyata, bukan lagi placeholder `get('/')`.
- File duplikat `tests/Feature/Feature/EmployeeScopeTest.php` sudah dihapus, dan verifikasi full suite memastikan hanya test `EmployeeScopeTest` yang aktif.

---

### 16. Tambah Feature Tests untuk Module Tanpa Tests

**Module yang sama sekali tidak punya tests:**

| Module | Tests Dibutuhkan |
|---|---|
| **Leave Management** | Request, approve, reject, balance deduction, cancel |
| **Reimbursement** | Submit, add items, approve, reject, payment |
| **Petty Cash** | Create account, top-up, transaction, balance tracking |
| **Asset Management** | CRUD, reading, status transitions |
| **Warehouse** | CRUD, stock management |
| **Spare Parts** | CRUD, stock adjustment |
| **Salary Structure** | Configure components, apply to employee |
| **Shift Roster** | Assign, rotate, conflict detection |
| **Organization** | CRUD, switch context |
| **Department / Position / JobGrade / WorkShift** | CRUD, scoping |
| **Work Order Web** | Create, assign, complete, parts usage (API sudah ditest) |
| **Report Generation** | Generate consolidated, compliance, export |
| **Payroll Full Pipeline** | Create period, run calculation, approve, dispatch |
| **Attendance Full Flow** | Check-in, check-out, late detection, geofence |

**Aksi:**
- [x] Prioritaskan: Leave, Reimbursement, Petty Cash, Payroll Pipeline
- [x] Kemudian: Asset, Warehouse, Spare Parts
- [x] Kemudian: Salary Structure, Shift Roster, Organization setup modules
- [x] Terakhir: Report generation, Work Order web flow

**Progress saat ini:**
- Coverage modul prioritas P2 sudah ditambahkan melalui `LeaveManagementTest`, `ReimbursementManagementTest`, `PettyCashManagementTest`, `PayrollPipelineTest`, `AssetManagementTest`, `WarehouseManagementTest`, `SparePartManagementTest`, `OrganizationManagementTest`, `HrMasterDataManagementTest`, `SalaryStructureManagementTest`, `ShiftRosterManagementTest`, `AttendanceManagementTest`, `WorkOrderWebFlowTest`, dan `ReportGenerationTest`.
- Batch tambahan juga ikut menstabilkan flow yang sudah ada seperti procurement web flow, audit log, dan integrasi role/user agar full suite tetap konsisten dengan perilaku aplikasi saat ini.
- Verifikasi terakhir pada full suite berada di kondisi hijau dengan `267 passed (1563 assertions)`.

---

### 17. Tambah Negative/Edge Case Tests

**Masalah:** Sebagian besar tests hanya cover happy path. Missing: invalid input, unauthorized access, concurrent operations, boundary values.

**Aksi:**
- [x] Tambah tests untuk unauthorized access di setiap endpoint
- [x] Tambah tests untuk invalid input (negative amounts, empty required fields, exceeded string length)
- [x] Tambah tests untuk boundary values (zero salary, maximum overtime, empty payroll period)
- [x] Tambah tests untuk concurrent operations (double-submit, race conditions)

**Progress saat ini:**
- Test P2 dan follow-up P0/P1 sudah banyak menutup jalur unauthorized, invalid input, dan boundary value pada modul payroll, reimbursement, leave, attendance, organization setup, procurement, work order, dan reporting.
- Coverage double-submit sekarang mulai ditambahkan, termasuk guard agar submit approval payroll tidak membuat pending approval ganda untuk payroll yang sama, create-PO dari PR tidak menghasilkan purchase order duplikat saat request diulang, receive GRN tidak menggandakan item penerimaan maupun stok spare part, approval pembayaran koperasi tidak menggandakan `paid_amount` invoice maupun ledger entry saat approval dipicu ulang, `mark as paid` pada invoice menjadi no-op yang aman saat request yang sama terkirim ulang, dan pembayaran reimbursement hanya bisa diproses dari status `APPROVED` serta aman terhadap retry setelah status `PAID`.
- True parallel-process coverage sekarang juga sudah ada melalui test POS yang menjalankan dua worker process bersamaan untuk `client_reference` yang sama dan memverifikasi hanya satu transaksi, satu stock movement, dan satu pengurangan stok yang benar-benar tercatat.
- Concurrent/race-condition lintas modul yang lebih luas masih bisa diperluas lagi, tetapi statusnya bukan lagi “belum ada true parallel request testing”.

---

### 18. Perbaiki 2 Skipped Tests di NotificationSystemTest

**Masalah:** 2 tests di `NotificationSystemTest` di-skip karena Sanctum configuration issues.

**Aksi:**
- [x] Investigasi kenapa Sanctum configuration bermasalah
- [x] Fix configuration dan un-skip tests
- [x] Pastikan Sanctum works untuk API token auth di tests

**Progress saat ini:**
- `NotificationSystemTest` sudah aktif penuh tanpa skipped test, dan flow session/API notifikasi berjalan normal pada test suite.
- Penguatan konfigurasi Sanctum dan ability middleware di batch P0 membantu menutup issue konfigurasi yang sebelumnya membuat coverage notifikasi tertunda.

---

## P3 - SEDANG: Konsistensi Kode

### 19. Standardisasi `casts` Declaration di Models

**Masalah:** Campuran `protected $casts` property dan `protected function casts(): array` method.

**Models dengan `$casts` property (perlu migrasi ke method):**
- Project, ProjectTask, ProjectTeam, AuditLog, NotificationPreference
- PettyCashAccount, PettyCashTransaction, Reimbursement, PayrollApproval
- EmployeeFamily, ProjectMilestone, EmployeeCertificate

**Aksi:**
- [x] Konversi semua `$casts` property ke `casts()` method
- [x] Konsistenkan dengan convention Laravel 12

**Progress saat ini:**
- Audit terbaru tidak lagi menemukan `protected $casts` di `app/Models`
- Batch P3 juga merapikan model support yang sebelumnya belum masuk batch awal, termasuk `MedicalCheckup`, `ProjectDocument`, `ProjectAssetAllocation`, `ProjectBudgetItem`, `ProjectPayrollAllocation`, dan `ReimbursementItem`

---

### 20. Standardisasi Relationship Return Types

**Masalah:** Campuran FQN, short name, dan tanpa return type di relationship methods.

**Aksi:**
- [x] Import relationship class di setiap model
- [x] Gunakan short name: `BelongsTo`, `HasMany`, `BelongsToMany`, `MorphMany`, dll
- [x] Tambah return type ke relationship methods prioritas P3 yang sebelumnya belum konsisten

**Progress saat ini:**
- Model yang disentuh pada batch P3 sudah diseragamkan ke short import + typed relationships, termasuk `Project*` models, `Client`, `Department`, `WorkShift`, `JobGrade`, `Warehouse`, `Organization`, `User`, `Attendance`, `SalaryStructure`, dan `Invoice`
- Relasi baru yang ditambahkan juga langsung memakai return type yang konsisten

---

### 21. Standardisasi UUID Handling

**Masalah:** Campuran `HasUuids` trait dan manual `boot()` dengan `Str::uuid()`.

**Models tanpa `HasUuids` tapi pakai UUID:**
- Project, ProjectTask, ProjectTeam, ProjectMilestone (manual `$keyType = 'string'`)
- Client, Invoice, PayrollApproval (manual UUID di controller)

**Aksi:**
- [x] Gunakan `HasUuids` trait secara konsisten di semua model target P3
- [x] Hapus manual UUID generation di controllers untuk model yang sudah memakai `HasUuids`

**Progress saat ini:**
- `Project`, `ProjectTask`, `ProjectTeam`, `ProjectMilestone`, `Client`, `Invoice`, dan `PayrollApproval` kini konsisten memakai `HasUuids`
- UUID manual yang dihapus dari controller mencakup flow project, project task, project team, project document, client, dan pembuatan `PayrollApproval`
- UUID manual yang masih ada di codebase dipakai untuk batch/pivot/helper flow yang tidak dimodelkan via Eloquent UUID model target P3

---

### 22. Tambah `HasOrganizationScope` ke Models yang Missing

**Masalah:** Models berikut punya `organization_id` tapi tidak pakai `HasOrganizationScope`:
- Attendance, Client, Department, JobGrade, Leave, LeaveType
- Position, Project, WorkShift, ShiftRoster, SalaryStructure, Warehouse, SparePart

**Aksi:**
- [x] Audit setiap model yang punya `organization_id`
- [x] Tambah `HasOrganizationScope` trait pada model yang memang punya kolom tersebut di schema aktual
- [x] Pastikan data isolation antar organization berjalan di model target P3

**Progress saat ini:**
- `Attendance`, `Client`, `Department`, `Project`, `SalaryStructure`, `Warehouse`, `SparePart`, dan `PettyCashAccount` sudah memakai `HasOrganizationScope`
- Checklist awal dokumen tidak sepenuhnya sinkron dengan migration; model yang ternyata tidak memiliki `organization_id` tidak dipaksa memakai trait ini agar tidak menambah bug baru

---

### 23. Tambah Missing Relationships di Models

**Masalah:** Beberapa relationship penting tidak didefinisikan:

| Model | Missing Relationships |
|---|---|
| `Department` | `employees()` |
| `JobGrade` | `employees()` |
| `WorkShift` | `employees()` |
| `Client` | `invoices()` |
| `Vendor` | `purchaseOrders()` |
| `Warehouse` | `purchaseOrders()`, `goodsReceiveNotes()` |
| `Organization` | `departments()`, `projects()`, `invoices()`, `employees()`, `assets()`, `workOrders()`, `budgets()`, `vendors()` |
| `User` | `auditLogs()` |

**Aksi:**
- [x] Tambah missing relationships di setiap model prioritas P3
- [x] Pastikan relationship baru siap dipakai untuk eager loading di controller/service

**Progress saat ini:**
- Relationship yang semula hilang kini sudah tersedia di `Department`, `JobGrade`, `WorkShift`, `Client`, `Vendor`, `Warehouse`, `Organization`, dan `User`
- Coverage arsitektur P3 ditambahkan untuk memastikan relasi utama tetap tersedia pada batch berikutnya

---

## P4 - RENDAH: Frontend UX/UI

### 24. Tambah Skeleton Loading States

**Masalah:** Component `Skeleton` ada tapi tidak pernah dipakai. Halaman kosong sampai data loaded.

**Aksi:**
- [x] Tambah skeleton states di halaman yang data-nya di-defer
- [x] Implementasi skeleton untuk tabel, stats cards, form fields prioritas
- [x] Gunakan pattern Inertia deferred + skeleton fallback

**Progress saat ini:**
- Skeleton fallback kini dipakai pada halaman deferred prioritas: `Dashboard`, `Reports`, `Payroll/Index`, dan `Cooperative/Members/Index`
- Fallback lama yang masih berupa blok `animate-pulse` mentah diganti ke komponen `Skeleton` bawaan proyek agar pattern loading lebih konsisten
- Ditambah `aria-live` pada loading fallback supaya perubahan state lebih ramah pembaca layar

---

### 25. Implementasi Accessibility (a11y)

**Masalah:** Hanya 1 dari 109 halaman yang punya ARIA attributes.

**Aksi:**
- [x] Tambah `role` attributes di tabel dan interactive elements prioritas
- [x] Tambah `aria-label` di icon-only buttons prioritas
- [x] Tambah `aria-live` regions untuk dynamic content loading
- [x] Tambah skip-to-content link di layout
- [x] Tambah `aria-describedby` di dialog konfirmasi
- [x] Implementasi focus trap di dialogs
- [x] Tambah keyboard navigation support baseline

**Progress saat ini:**
- `AppShell` dan `AppContent` kini punya skip link `Lewati ke konten utama` dan target `#main-content`
- `DataTable` kini punya `aria-label` dan `role="table"` sebagai baseline a11y untuk tabel reusable
- `ConfirmDialog` kini menghubungkan deskripsi dengan `aria-describedby`
- Icon-only action buttons prioritas pada halaman client dan anggota kini punya `aria-label`
- Focus trap dialog mengikuti behavior dari komponen dialog Reka UI/shadcn yang sudah dipakai proyek

---

### 26. Standardisasi Container dan Spacing

**Masalah:** Campuran `max-w-7xl`, `max-w-4xl`, dan tanpa max-width. Padding tidak konsisten (`p-4 sm:p-6` vs `p-6`).

**Aksi:**
- [x] Definisikan container max-width per page type (list, form, detail)
- [x] Standardisasi padding pattern untuk batch halaman prioritas
- [x] Dokumentasikan spacing convention

**Progress saat ini:**
- Ditambahkan komponen shared `PageContainer` dengan variant `list`, `detail`, dan `form`
- Pattern spacing dasar diseragamkan ke `px-4 py-6 sm:px-6` pada container bersama
- Halaman prioritas batch ini (`Dashboard`, `Reports`, `Payroll/Index`, `Cooperative/Members/Index`, `Client/Index`) sudah memakai container bersama tersebut

---

### 27. Perbaiki SSR Entry Point

**Masalah:** `ssr.ts` tidak initialize theme dan tidak set global routes, berbeda dengan `app.ts`. Ini bisa menyebabkan hydration mismatch.

**Aksi:**
- [x] Sinkronkan `ssr.ts` dengan `app.ts`
- [x] Tambah route initialization dan theme handling di SSR

**Progress saat ini:**
- Shared app bootstrap dipindah ke helper bersama agar `app.ts` dan `ssr.ts` memakai route global dan directive `v-can` yang sama
- `appearance` kini dishare lewat Inertia props sehingga SSR bisa membaca preferensi tema yang sama dengan client
- Build SSR lulus setelah sinkronisasi ini, sehingga risiko hydration mismatch dari route/theme initialization menurun

---

## P5 - FEATURE BARU: Sesuai Roadmap Dokumentasi

### 28. Module Simpan Pinjam (Loans)

**Status:** Implementasi inti sudah tersedia untuk admin dan API anggota.

**Yang perlu dibuat:**
- [x] Models: `Loan`, `LoanType`, `LoanInstallment`, `LoanPayment`
- [x] Migrations untuk tabel di atas
- [x] Enums: `LoanStatus`, `InstallmentStatus`
- [x] Service: `LoanCalculatorService` (bunga, angsuran, denda)
- [x] Controller + Form Requests
- [x] Vue Pages: Loan/Index, Loan/Create, Loan/Show, LoanCalculator
- [x] API Endpoints untuk Kojayaku: apply, track, calculator
- [x] Approval workflow integration
- [x] Tests

**Progress saat ini:**
- Modul pinjaman sekarang mencakup master `LoanType`, transaksi `Loan`, jadwal `LoanInstallment`, dan pembayaran `LoanPayment`
- Alur web koperasi tersedia untuk pengajuan, approval, pencairan, pencatatan angsuran, dan kalkulator pinjaman
- API Kojayaku tersedia untuk apply pinjaman, lihat daftar/status pinjaman sendiri, dan simulasi kalkulator
- Posting ledger otomatis dibuat untuk `LOAN_DISBURSEMENT` dan `LOAN_PAYMENT`
- Approval log tercatat pada status pengajuan, persetujuan, penolakan, dan pencairan
- Coverage feature test ditambahkan untuk web flow, API anggota, kalkulator, dan pencatatan ledger

---

### 29. Module Points & Rewards

**Status:** Dideskripsikan di docs tapi **tidak ada implementasi**.

**Yang perlu dibuat:**
- [ ] Models: `PointTransaction`, `Reward`, `RewardRedemption`
- [ ] Service: `PointService` (earn, redeem, expire)
- [ ] Controller + Form Requests
- [ ] Vue Pages: Points/Index, Rewards/Index, Redemptions/Index
- [ ] API Endpoints untuk Kojayaku
- [ ] Tests

---

### 30. ESS Portal (Employee Self-Service)

**Status:** Hanya attendance dan leave self-service yang ada. Belum ada portal lengkap.

**Yang perlu ditambahkan:**
- [ ] ESS Dashboard page (overview jam kerja, cuti, gaji terakhir)
- [ ] Payslip self-view page (lihat slip gaji sendiri)
- [ ] Profile self-edit page (update data pribadi)
- [ ] Certificate & MCU self-view page (lihat sertifikat dan MCU sendiri)

---

### 31. Chart of Accounts / General Ledger

**Status:** Hanya cooperative-specific ledger yang ada (`Cooperative/Ledger/Index.vue`). Tidak ada full accounting ledger.

**Yang perlu dibuat:**
- [ ] Models: `ChartOfAccount`, `JournalEntry`, `JournalEntryLine`
- [ ] Service: `JournalEntryService`, `FinancialStatementService`
- [ ] Vue Pages: ChartOfAccounts/Index, JournalEntries/Index, TrialBalance, BalanceSheet, IncomeStatement
- [ ] Neraca (Balance Sheet) dan Laba Rugi (Income Statement) reports

---

### 32. E-Faktur UI

**Status:** Backend service dan API ada, tapi **tidak ada halaman UI** untuk manage e-Faktur.

**Yang perlu dibuat:**
- [ ] Vue Pages: Efaktur/Index (list faktur), Efaktur/Submit, Efaktur/Status
- [ ] Integrasi dengan `DjpEfakturApiService` yang sudah ada

---

### 33. Bank Reconciliation UI

**Status:** Backend service ada, halaman Bank Batches ada, tapi **tidak ada halaman reconciliation**.

**Yang perlu dibuat:**
- [ ] Vue Pages: BankReconciliation/Index, BankReconciliation/Show
- [ ] UI untuk match statement lines dengan invoices/payments

---

### 34. Kojayaku Web App (Member Portal)

**Status:** Phase 3 di roadmap, belum dimulai.

**Yang perlu dibuat (sesuai roadmap):**
- [ ] Member authentication flow (login/register)
- [ ] Member Dashboard (overview simpanan, pinjaman, poin)
- [ ] Savings pages (balance, history, statements)
- [ ] Loan pages (apply, track, calculator)
- [ ] Points & Rewards pages (balance, redeem, history)
- [ ] Transaction history pages
- [ ] Profile pages
- [ ] Notification pages
- [ ] Mobile-responsive design (mobile-first)

---

## Summary - Checklist Per Prioritas

### P0 - Keamanan (Estimasi: 1-2 minggu)
1. [ ] Implementasi Laravel Policies (~15 files)
2. [ ] Perbaiki TechnicianWorkOrderController authorization
3. [ ] Perbaiki LogActivity middleware operator precedence
4. [ ] Set Sanctum token expiration
5. [ ] Tambah authorization di 5+ endpoint tanpa auth check
6. [ ] Perbaiki StoreBudgetRequest::authorize()

### P1 - Arsitektur (Estimasi: 2-3 minggu)
7. [ ] Buat Form Requests untuk 12 controller
8. [ ] Buat shared frontend utilities (6 files)
9. [ ] Hapus duplicate components dan file backup
10. [ ] Refaktor halaman tabel ke DataTable component
11. [ ] Tambah rate limiting di API routes
12. [ ] Implementasi Inertia deferred props
13. [ ] Perbaiki service instantiation

### P2 - Testing (Estimasi: 3-4 minggu)
14. [x] Buat 15+ model factories
15. [x] Ganti 3 stub tests
16. [x] Tambah feature tests untuk 14 module
17. [~] Tambah negative/edge case tests
18. [x] Perbaiki 2 skipped notification tests

### P3 - Konsistensi (Estimasi: 1-2 minggu)
19. [x] Standardisasi casts declaration
20. [x] Standardisasi relationship return types
21. [x] Standardisasi UUID handling
22. [x] Tambah HasOrganizationScope
23. [x] Tambah missing relationships

### P4 - Frontend UX (Estimasi: 2 minggu)
24. [x] Tambah skeleton loading states
25. [x] Implementasi accessibility
26. [x] Standardisasi container dan spacing
27. [x] Perbaiki SSR entry point

### P5 - Feature Baru (Estimasi: 6-8 minggu)
28. [x] Module Simpan Pinjam (Loans)
29. [ ] Module Points & Rewards
30. [ ] ESS Portal
31. [ ] Chart of Accounts / General Ledger
32. [ ] E-Faktur UI
33. [ ] Bank Reconciliation UI
34. [ ] Kojayaku Web App

---

**Total Estimasi Waktu:** 15-21 minggu (jika dikerjakan sequential)
**Rekomendasi:** Kerjakan P0 dan P1 paralel dengan development feature baru (P5)

---

*Dokumen ini dibuat otomatis dari analisis codebase. Update status checkbox seiring progress.*
*Terakhir diperbarui: 4 Mei 2026*
