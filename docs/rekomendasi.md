# Rekomendasi Pengembangan Kojaya

**Tanggal audit:** 16 Mei 2026
**Lingkup:** Proses bisnis dan kualitas kode (KojayaPro + Kojayaku)
**Basis audit langsung:** `app/Http/Controllers`, `app/Services`, `app/Models`, `routes/web.php`, `routes/api.php`, `resources/js/pages`, `tests/`, dan dokumen di `docs/`.

> Catatan: dokumen ini **melengkapi** `docs/improve.md`, `docs/improve2.md`, dan `docs/improve3.md`, bukan mengulanginya. Item yang sudah selesai di dokumen-dokumen tersebut tidak diulang. Fokus dokumen ini adalah temuan baru yang masih bisa ditingkatkan setelah seluruh batch P0–P5, Phase 0–5, dan Phase A–D dijalankan.

---

## 1. Ringkasan Eksekutif

Aplikasi sudah pada level yang baik untuk kelas ERP koperasi internal: 56 controller utama, 96 model, 96 migration, 31 service, 14 policy, 70+ test feature, plus API mobile untuk 4 persona (member, ESS, technician, admin), payment foundation, push token, OpenAPI generator, monitoring, dan operator hardening. Hasil pekerjaan bertahap di `improve.md` → `improve3.md` cukup terlihat dampaknya pada keamanan API mobile, struktur Form Request, factory, dan UI bersama (DataTable, FilterBar, ConfirmDialog, StatsCard, PageContainer).

Setelah audit baru ini, gap yang paling bernilai untuk diselesaikan berikutnya **bukan menambah fitur**, melainkan:

1. Menutup celah **proses bisnis kritis** yang masih bisa membuat data koperasi tidak konsisten (resign anggota tanpa cek pinjaman aktif, default password ESS dari kode karyawan, pembayaran iuran tanpa lock period, rate limit audit log API yang belum ada).
2. Menyatukan **authorization enforcement** dengan policy/`PermissionEnum` agar tidak ada lagi controller yang membaca `hasRole()` langsung untuk keputusan akses yang penting.
3. Mengurangi **duplikasi query master data** di Inertia controller (Employee, Project) dengan reusable provider/composer agar payload SSR tidak membengkak.
4. Memperketat **data quality guardrail** untuk pinjaman, redemption, simpanan, dan procurement di level service.
5. Menyiapkan **observability dan log retensi** dasar agar incident produksi (payment stuck, push gagal, payroll salah) bisa ditelusuri.

---

## 2. Temuan Proses Bisnis

### 2.1 Resign anggota tidak memvalidasi pinjaman aktif & saldo simpanan

**Lokasi:** `app/Http/Controllers/Cooperative/CooperativeMemberController.php` method `resign()`.

Saat ini resign hanya mengubah `status` ke `RESIGNED` dan mengisi `resigned_at`. Tidak ada pengecekan:

- Pinjaman dengan status `DISBURSED`/`ACTIVE`/installment outstanding.
- Saldo simpanan yang belum ditarik (`cooperative_ledger_entries`).
- Redemption reward `PROCESSING` atau `SHIPPED`.
- Tagihan dues `UNPAID`/`PARTIAL`.

**Risiko bisnis:** anggota bisa "resign" lalu kabur dari pinjaman, atau saldo simpanan ter-orphan tanpa instruksi pencairan. Audit OJK/koperasi cenderung menemukan inkonsistensi ini saat tutup tahun.

**Rekomendasi:**

- Tambahkan `CooperativeMemberResignationGuard` (atau cukup method privat di controller/service) yang memblokir resign bila ada pinjaman aktif/installment outstanding/redemption belum delivered.
- Untuk simpanan dengan saldo > 0, paksa operator memilih salah satu: `transfer ke rekening`, `bayar pinjaman aktif`, `tahan sampai SHU dicairkan`, dan catat keputusan itu di `approval_logs` atau `cooperative_ledger_entries` dengan `transaction_type = MEMBER_RESIGNATION_SETTLEMENT`.
- Pindahkan logika resign ke `App\Services\Cooperative\CooperativeMemberService::resign()` dengan `DB::transaction` + `lockForUpdate()`, mengikuti pola `LoanService` dan `CooperativePaymentService`.

### 2.2 Default password ESS adalah Employee Code

**Lokasi:** `app/Http/Controllers/EmployeeController.php::enableEssAccess`.

```php
'password' => Hash::make($employee->employee_code),
```

Employee code biasanya di-print di kartu, dipajang di papan kantor, dan diketahui rekan kerja. Ini melanggar prinsip baseline keamanan akun, terlebih untuk aplikasi yang akan menyimpan payslip.

**Rekomendasi:**

- Generate password acak (`Str::password(16)`), simpan satu kali ke tabel `password_resets` atau kirim langsung ke email karyawan via mailable.
- Set `must_change_password = true` di tabel `users` atau gunakan `password_changed_at` lalu paksa redirect ke halaman ganti password pada login pertama.
- Setelah link reset disampaikan, jangan tampilkan plaintext password di UI atau log.

### 2.3 Pembayaran iuran belum mengintegrasikan period lock

**Lokasi:** `app/Services/Cooperative/CooperativePaymentService.php` (approve/reconcile).

`OperatorProcedureController` sudah punya endpoint lock/unlock period (`cooperative_period_locks`), tetapi service `approve` dan `reconcile` belum melakukan pengecekan `CooperativePeriodLock` untuk periode invoice yang dibayar. Akibatnya, setelah operator close period, payment masih bisa di-approve dan ledger entry baru tetap masuk ke periode yang sudah dikunci.

**Rekomendasi:**

- Tambahkan helper `assertPeriodOpen(string $period, string $module)` yang melempar `DomainException` jika period lock aktif.
- Panggil dari `CooperativePaymentService::approve`, `LoanService::recordPayment`, `JournalEntryService::create`, dan `AnnualShuDistributionService::close`.
- Buat exception khusus (`CooperativePeriodLockedException`) sehingga controller bisa render pesan dalam Bahasa Indonesia tanpa membocorkan stack trace.
- Tambahkan `Phase4PeriodLockEnforcementTest` yang memastikan period yang ter-lock memblokir 4 jalur tersebut.

### 2.4 Pinjaman tidak memvalidasi exposure/eligibility anggota

**Lokasi:** `app/Services/Cooperative/LoanService::apply`.

Saat anggota mengajukan pinjaman baru, service tidak mengecek:

- Ada/tidaknya pinjaman aktif yang masih `DISBURSED` dengan installment outstanding.
- Total exposure (sum outstanding) terhadap maksimum loan-to-saving rasio.
- Lama keanggotaan minimum (misal 3 bulan) atau status `ACTIVE`.
- Kapasitas angsuran terhadap simpanan wajib bulanan.

**Rekomendasi:**

- Buat `LoanEligibilityService` yang mengevaluasi rule per `LoanType` (misal `min_membership_months`, `max_outstanding_loans`, `max_loan_to_saving_ratio`, `max_dpd_history`).
- Konfigurasi rule di kolom JSON pada `loan_types.eligibility_rules` atau tabel `loan_eligibility_rules` agar pengurus bisa mengubah tanpa deploy.
- Tampilkan reason penolakan otomatis ke anggota di endpoint `POST /api/v1/member/loans` dan portal Kojayaku.

### 2.5 Receipt digital pembayaran belum ter-generate

**Konteks:** Kojayaku dan KojayaPro sudah mencatat pembayaran iuran, tetapi belum ada artefak resmi (receipt PDF dengan nomor seri) yang bisa diunduh anggota.

**Rekomendasi:**

- Tambahkan `CooperativeReceipt` model + migration dengan kolom `receipt_no` (format `RC-{period}-{seq}`), `payment_id`, `member_id`, `pdf_path`, `issued_at`, `issued_by`.
- Buat `CooperativeReceiptService::issue(CooperativePayment $payment): CooperativeReceipt` yang dipanggil otomatis saat `CooperativePaymentService::reconcile()` menandai `PAID`.
- Tambahkan endpoint `GET /api/v1/member/payments/{payment}/receipt` (signed URL serupa payslip) untuk download.
- Receipt wajib idempotent: nomor seri tidak berubah jika webhook duplikat masuk.

### 2.6 Notifikasi belum punya event-driven outbox

**Konteks:** Saat ini notifikasi push/database dipicu inline di service (`CooperativePaymentService`, `LoanService`). Jika service push (FCM) sedang down, transaction commit-nya tetap jalan tetapi notifikasi gagal dan tidak ter-retry.

**Rekomendasi:**

- Pakai pola **transactional outbox**: tambahkan tabel `notification_outbox` (event_type, payload, target_user_id, status, attempts, last_error). Tulis ke outbox dalam transaksi yang sama.
- Worker queue `ProcessNotificationOutbox` (`ShouldQueue` + `WithoutOverlapping`) yang memproses outbox setiap 30 detik dan retry dengan backoff.
- Hapus notifikasi inline di service domain agar SoC (separation of concerns) lebih jelas; service hanya menulis outbox.

### 2.7 Audit Logs API tidak punya rate limit

**Lokasi:** `routes/api.php`

```php
Route::middleware(['auth:web'])->prefix('audit-logs')->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/export', [AuditLogController::class, 'export']);
    ...
});
```

Endpoint `audit-logs` dan `audit-logs/export` (yang biasanya berat) tidak pakai `throttle:api`. Tindakan export berulang bisa membuat database I/O tinggi.

**Rekomendasi:**

- Tambahkan `throttle:api` di group `audit-logs`.
- Untuk `export`, pertimbangkan throttle yang lebih ketat (`throttle:audit-export,5,1`) dan pindahkan export ke queued job dengan email link download.

### 2.8 Procurement breadcrumb status belum visible ke pengguna

**Konteks:** PR → PO → GRN → Invoice → Payment sudah ada, tapi UI belum menampilkan timeline/status terhubung. Operator harus berpindah halaman untuk melihat dokumen turunan.

**Rekomendasi:**

- Pada halaman PR/PO/GRN, tampilkan blok "Lifecycle Procurement" dengan link ke dokumen turunan dan badge status.
- Tambahkan kolom virtual `procurement_status_label` di model PR/PO/GRN, atau gunakan `accessor` Eloquent.
- Tampilkan `next_approver_name` dan `pending_since` (selisih dengan `now()`) sehingga operator bisa mengambil tindakan.

---

## 3. Temuan Kualitas Kode

### 3.1 Authorization masih bercampur antara `hasRole()` dan policy

**Lokasi:** `app/Http/Controllers/Api/AuthController.php::abilitiesFor()`.

Mapping role → ability di `AuthController` masih membaca `hasRole('Anggota')`, `hasRole('Employee')`, `hasRole('Technician')` secara hardcoded. Ini meng-override desain `PermissionEnum` (yang lebih granular) untuk persona-persona kunci.

Risiko: jika di masa depan permission berubah (misal `Anggota` boleh menambah ability `member:loan-write` tetapi tidak `member:write`), perubahan harus dilakukan di dua tempat (seeder permission + AuthController).

**Rekomendasi:**

- Tambahkan kolom `app_abilities` (JSON) di tabel `roles` (Spatie permission mendukung custom column) atau buat `App\Auth\TokenAbilityResolver` yang membaca `PermissionEnum` untuk turunkan ability.
- Mapping role → ability disimpan sebagai konstanta `App\Auth\TokenAbilities::ROLE_MAP` yang juga dipakai oleh `RolePermissionSeeder`. Test memastikan kedua sumber sinkron.
- Setelah migrasi, hapus seluruh `hasRole()` literal di `AuthController` dan ganti dengan `TokenAbilityResolver::for($user, $app)`.

### 3.2 Inertia controller mengulang query master data di setiap action

**Lokasi:** `app/Http/Controllers/EmployeeController.php` (index/create/edit) dan controller serupa untuk Project, Reimbursement, Procurement.

`Department::orderBy('name')->get()`, `Position::orderBy('name')->get()`, `JobGrade::orderBy('level')->get()`, `WorkShift::orderBy('name')->get()`, dan `Organization::orderBy('name')->get()` muncul **3 kali** di file yang sama dan **berulang di banyak controller HR**. Ini:

- Memperberat SSR payload (master data yang sama dikirim ulang setiap navigasi).
- Sulit di-cache karena query tersebar.
- Tidak lazy: kalaupun create/edit form sebenarnya hanya butuh sebagian.

**Rekomendasi:**

- Buat `App\Support\HrMasterDataProvider` (singleton) dengan method `forSelectInputs(string $context = 'employee')` yang mengembalikan koleksi yang dibutuhkan. Tambahkan caching via `Cache::remember('hr-master-data', 300, …)` dan invalidasi via observer pada model master.
- Atau, gunakan **Inertia Shared Data**: register di `HandleInertiaRequests::share()` sebagai `Inertia::lazy(fn () => $provider->forSelectInputs())`, lalu konsumsi di Vue via `usePage().props.hr_master`.
- Untuk halaman list seperti `EmployeeController::index`, master data filter cukup di-load lewat `Inertia::defer()` agar table cepat tampil dulu.

### 3.3 `EssPortalController::enableEssAccess` melakukan side-effect tanpa transaksi

**Lokasi:** `EmployeeController::enableEssAccess`.

Method tersebut: cek user existence → `User::create(...)` → `assignRole('Employee')` → `$employee->update(['user_id' => …])`. Jika `assignRole` gagal (mis. role tidak ada di guard yang benar), user terlanjur dibuat tetapi `user_id` di employee tidak terisi. Pada percobaan kedua, gagal di check `User::where('email', …)->exists()`.

**Rekomendasi:**

- Bungkus dengan `DB::transaction(...)`.
- Pindahkan logika ke `App\Services\Hr\EmployeeEssProvisioningService::enable(Employee $employee): User`.
- Tambahkan unit test yang men-simulasikan kegagalan `assignRole` (mis. role belum di-seed) untuk memastikan rollback bekerja.

### 3.4 `ProjectGanttController` masih pakai `DB::table('project_task_dependencies')` tanpa model

**Lokasi:** `app/Http/Controllers/ProjectGanttController.php`.

```php
DB::table('project_task_dependencies')->insert([...]);
DB::table('project_task_dependencies')->join(...)->where(...)->delete();
```

Tidak ada model Eloquent untuk `project_task_dependencies` walaupun tabelnya jelas representasi entitas. Ini melanggar Laravel Boost guideline `Avoid DB::; prefer Model::query()`.

**Rekomendasi:**

- Buat `App\Models\ProjectTaskDependency` (UUID, BelongsTo `predecessor` & `task`).
- Refactor controller agar pakai `ProjectTaskDependency::create()` dan `ProjectTaskDependency::whereHas('task', …)->delete()`.
- Tambahkan factory + feature test untuk gantt link CRUD.

### 3.5 Frontend masih punya 5 lokasi `axios` yang bypass Inertia

**Lokasi:**

- `resources/js/pages/Project/Show.vue` (team availability)
- `resources/js/components/project/GanttChart.vue` (load + CRUD task & dependency)
- `resources/js/components/project/ProjectFinancials.vue` (3 endpoint paralel)

`axios` tidak melalui Wayfinder, tidak otomatis di-update saat route URL berubah, dan tidak share CSRF/Inertia state secara konsisten. Project ini sudah pakai Wayfinder; konsistensinya sebaiknya dijaga.

**Rekomendasi:**

- Migrasikan call axios ke action class Wayfinder + `useForm()` atau `router.reload({ only: […] })`.
- Untuk gantt chart yang butuh JSON murni (bukan Inertia), pertahankan axios tapi ekstrak ke `resources/js/api/projectGantt.ts` dan pakai client `axios.create({ baseURL: '/api', headers })` agar konsisten dengan endpoint API lain.
- Tambahkan `.eslintrc` rule `no-restricted-imports` untuk `axios` di luar folder `api/` agar regresi dijaga.

### 3.6 `Welcome.vue.bak` masih ada di repo

**Lokasi:** `resources/js/pages/Welcome.vue.bak`.

File backup sebelumnya disebut sudah dibersihkan di `improve.md` Bagian 9, tetapi file ini masih ada. Backup di version control adalah anti-pattern karena Git sudah menyimpan history.

**Rekomendasi:**

- Hapus `resources/js/pages/Welcome.vue.bak`.
- Tambahkan `*.bak` ke `.gitignore` agar tidak ter-commit lagi.
- Tambahkan `tests/Feature/Static/RepoHygieneTest.php` yang men-`scandir` repo dan gagal jika menemukan `.bak`/`.orig`/`.tmp` di luar `vendor/` dan `node_modules/`.

### 3.7 `formatRupiah` lokal masih ada di `Exceptions/Dashboard.vue` dan `settings/Components.vue`

**Lokasi:**

- `resources/js/pages/Exceptions/Dashboard.vue`: `function formatRupiah(v: number) { return "Rp " + v.toLocaleString("id-ID"); }`
- `resources/js/pages/settings/Components.vue`: inline `format: (val) => 'Rp ${val.toLocaleString("id-ID")}'`
- `resources/js/pages/Dashboard.vue`: helper `formatNumber` lokal (sudah ada di `lib/formatters.ts`).

**Rekomendasi:**

- Ganti dengan `formatCurrency`/`formatNumber` dari `resources/js/lib/formatters.ts`.
- Tambahkan ESLint rule custom `no-local-currency-formatter` (atau cukup unit test snapshot pencarian regex `Rp\s\$\{`) untuk mencegah kembali.

### 3.8 Service domain belum punya kontrak interface

**Konteks:** `LoanService`, `CooperativePaymentService`, `PaymentGatewayService` sudah cukup besar (200–500 LOC), tetapi tidak punya interface. Ini menyulitkan penggantian provider (mis. ganti payment gateway dari Midtrans ke Xendit) dan mocking di test unit.

**Rekomendasi:**

- Definisikan kontrak: `App\Contracts\Cooperative\LoanServiceContract`, `App\Contracts\Integrations\PaymentGatewayProvider`.
- Bind ke implementasi konkret di `AppServiceProvider`.
- Khusus payment gateway, buat adapter `MidtransProvider`, `XenditProvider`, `InternalProvider`, dipilih lewat `config('payment.provider')`.

### 3.9 OpenAPI tidak otomatis terdeteksi drift di CI

**Konteks:** `OpenApiController` dan `OpenApiSnapshotCommand` sudah ada, dan `PhaseDOpenApiSnapshotTest` sudah membantu, tetapi belum ada CI gate yang menolak PR jika OpenAPI berubah tanpa update snapshot.

**Rekomendasi:**

- Jalankan `php artisan openapi:snapshot --check` di `.github/workflows/ci.yml` agar PR fail kalau snapshot drift.
- Tambahkan command-mode `--update` untuk developer regenerasi lokal.
- Versi-kan `docs/openapi.json` di repo sehingga reviewer bisa lihat diff kontrak API langsung di PR.

### 3.10 Test suite belum di-paralelkan dan tidak ada coverage threshold

**Konteks:** Test ratusan, run sequential cukup lama. PHPUnit 11 + Laravel 12 mendukung `--parallel` dan coverage report.

**Rekomendasi:**

- Konfigurasikan `phpunit.xml` untuk parallel testing dan tambahkan `php artisan test --parallel` di CI.
- Tambahkan `--coverage --min=70` (atau threshold yang sesuai) di pipeline. Kegagalan coverage otomatis menolak PR.
- Pisahkan test smoke (fast) vs feature lengkap dengan grup `@group smoke` agar PR check sub-3-menit, dan full suite di-run pada `main`.

### 3.11 `Health` dan `MetricsService` membaca tabel raw

**Lokasi:** `app/Monitoring/Health.php`, `app/Services/Monitoring/MetricsService.php`.

`DB::table('failed_jobs')`, `DB::table('webhook_logs')`, `DB::table('push_notification_logs')` adalah pemakaian raw DB yang valid (tabel infra), tetapi test `PhaseD1ObservabilityTest` perlu memastikan tabel-tabel ini eksis. Saat ini, jika migration `webhook_logs` belum jalan, `MetricsService::failedWebhooks()` melempar exception alih-alih mengembalikan `0`.

**Rekomendasi:**

- Bungkus dengan `try { … } catch (QueryException) { return 0; }`.
- Atau, pakai `Schema::hasTable('webhook_logs')` di constructor lalu cache hasilnya.
- Tambahkan health check yang melaporkan `webhook_log_table_missing` sebagai warning, bukan failure, agar deployment baru tidak gagal hanya karena tabel observability belum dibuat.

### 3.12 Beberapa policy belum dibuat untuk model sensitif

**Konteks:** Policy yang ada: 14 (`AssetPolicy` … `WorkOrderPolicy`). Yang belum:

- `Loan` (sekarang `LoanController` pakai abort_unless inline).
- `RewardRedemption` (operator action), `CooperativeMember`, `CooperativePayment`, `CooperativeShuPeriod`, `Organization`.

**Rekomendasi:**

- Tambahkan policy untuk 6 model di atas mengikuti `BasePolicy` yang sudah ada.
- Pakai `$this->authorize('approve', $loan)` di controller, bukan helper privat `authorizeLoanApproval()`.
- Tambahkan auto-discovery `Gate::policy()` di `AuthServiceProvider`/`AppServiceProvider` jika konvensi Laravel 12 belum auto-detect (Laravel 12 sudah auto-discover, tinggal pastikan namespace cocok).

---

## 4. Roadmap Eksekusi yang Direkomendasikan

### Sprint 1 (1 minggu) – Quick wins keamanan & kebersihan ✅ SELESAI (16 Mei 2026)

- [x] Hapus `Welcome.vue.bak`, tambahkan repo hygiene test (`tests/Feature/RepoHygieneTest.php`).
- [x] Migrasikan ESS provisioning ke random password + reset link (`app/Services/Hr/EmployeeEssProvisioningService.php` + 5 test).
- [x] Tambahkan `throttle:audit-logs` (30/min) dan `throttle:audit-export` (5/min) ke API audit-logs.
- [x] Bersihkan 3 lokasi formatter Rupiah lokal (`Exceptions/Dashboard.vue`, `Dashboard.vue`, `settings/Components.vue`) + regression test.
- [x] Wrap `MetricsService` & `Health::counts()` dengan defensive try-catch + `Schema::hasTable` (`tests/Unit/Services/MonitoringDefensiveTest.php`).

**Hasil:** 14 test baru, semuanya hijau. Tidak ada regresi ke modul lain.

**Catatan saat eksekusi:** Ditemukan ~24 test feature pra-existing yang sudah failing di `main` sebelum Sprint 1 dimulai (terutama `AuditLogTest`, `ClientControllerTest`, `EmployeeTransferTest`, `OrganizationManagementTest`, `LeaveManagementTest`, `OrganizationManagementTest`, beberapa di `CooperativeFeatureTest`/`CooperativeLoanFeatureTest`). Semua failing test ini **bukan akibat Sprint 1** dan kelihatannya disebabkan permission mapping HR/Admin/Audit yang belum lengkap di `RolePermissionSeeder`. Ini layak dijadikan Sprint 1.5 sebelum lanjut Sprint 2.

### Sprint 1.5 (3-5 hari) – Stabilkan baseline test ✅ SELESAI (16 Mei 2026)

Berdasarkan temuan di akhir Sprint 1, sebelum Sprint 2 sebaiknya:

- [x] Audit `RolePermissionSeeder` untuk role yang dipakai test: `HR Unit`, `HR Pusat`, `Admin Pusat`, Finance, Project Manager, koperasi, dan role operasional lain yang dipakai fixture test.
- [x] Audit Form Request dan controller authorization di modul Client, EmployeeTransfer, Organization, Audit, Finance, HR, Storage, Procurement, Cooperative, Report, User, dan Role Management.
- [x] Re-run full test suite dan tutup gap permission satu-per-satu sehingga baseline kembali hijau: `485 passed`, `6 risky`, `1 incomplete`, `0 failed`.

**Hasil:** baseline PHPUnit kembali tanpa failed. Perbaikan utama meliputi fixture test yang sekarang memberi permission/role eksplisit, mapping permission role yang dilengkapi untuk Finance Unit dan Project Manager, serta penyesuaian test terhadap segregation-of-duties guard agar pembuat transaksi tidak menyetujui transaksinya sendiri.

### Sprint 2 (2 minggu) – Proses bisnis kritis koperasi

- [x] `CooperativeMemberResignationGuard` + service refactor + test.
- [x] `LoanEligibilityService` + konfigurasi rule per `LoanType` + test.
- [x] Period lock enforcement di `CooperativePaymentService`, `LoanService`, `JournalEntryService`, `AnnualShuDistributionService`.
- [x] Receipt digital pembayaran (`CooperativeReceipt` + `CooperativeReceiptService`).

**Hasil:** proses resign, eligibility pinjaman, transaksi periode terkunci, dan receipt pembayaran sudah punya guard/service terpusat dan coverage di `tests/Feature/Cooperative/Sprint2BusinessHardeningTest.php`.

### Sprint 3 (2 minggu) – Authorization & arsitektur

- [x] 6 policy baru/aktif (Loan, RewardRedemption, CooperativeMember, CooperativePayment, CooperativeShuPeriod, Organization).
- [x] Refactor `AuthController::abilitiesFor` ke `TokenAbilityResolver` berbasis permission.
- [x] Eksternalisasi axios call di project frontend ke action Wayfinder dan `resources/js/api/`.
- [x] `ProjectTaskDependency` model + refactor `ProjectGanttController`.

**Hasil:** controller tidak lagi memakai `hasRole()` literal untuk keputusan akses, aksi pinjaman web/API memakai policy, ability token dipusatkan di service resolver, dan dependency Gantt memakai model Eloquent.

### Sprint 4 (2 minggu) – Reliability & DX ✅ SELESAI (16 Mei 2026)

- [x] Transactional outbox untuk notifikasi.
- [x] Service interface untuk `LoanServiceContract`, `PaymentGatewayProvider`.
- [x] OpenAPI drift CI gate + snapshot file di repo.
- [x] Parallel test + coverage threshold di CI.

**Hasil:** notifikasi sekarang punya tabel `notification_outboxes`, job retry `ProcessNotificationOutbox`, command/schedule `notifications:outbox:process`, dan metrik `failed_notification_outboxes` di health/metrics. Kontrak service dipindah ke `App\Contracts`, snapshot OpenAPI diversi-kan di `docs/openapi.json` dengan `bin/openapi.sh check`, dan CI menjalankan parallel PHPUnit dengan threshold coverage 70%.

---

## 5. Acceptance Criteria Per Sprint

### Sprint 1
- Repo hygiene test hijau di CI dan tidak ada `.bak` di `git ls-files`.
- ESS provisioning baru menghasilkan password random dan email reset terkirim (atau toast UI menampilkan link sekali pakai).
- `php artisan route:list --path=audit-logs` menunjukkan middleware throttle.
- Tidak ada `Rp\s\$\{` atau `formatRupiah` di luar `resources/js/lib`.

### Sprint 2
- Resign anggota gagal (HTTP 422) jika ada pinjaman aktif/redemption belum delivered, dengan pesan Bahasa Indonesia.
- Pengajuan pinjaman ke-2 oleh anggota yang masih punya outstanding loan mendapat HTTP 422 dengan reason eligibility.
- Pembayaran ke periode terkunci mendapat error "Periode telah dikunci".
- Setelah pembayaran iuran approved, tersedia receipt PDF yang bisa diunduh anggota lewat signed URL.

### Sprint 3
- Tidak ada `hasRole(` literal di controller untuk keputusan akses (boleh hanya untuk UI flag/scope).
- Semua aksi pinjaman pakai `$this->authorize('action', $loan)`.
- 5 axios call di frontend project sudah migrasi atau dipindah ke `resources/js/api/`.
- Test `Phase4ControllerAuthorizationTest` masih hijau setelah refactor ability resolver.

### Sprint 4
- Notifikasi yang gagal dikirim akan ter-retry tanpa intervensi manual; dashboard menampilkan jumlah outbox `failed`.
- `bin/openapi.sh check` (atau `php artisan openapi:snapshot --check`) gagal di CI ketika kontrak diubah tanpa snapshot update.
- `php artisan test --parallel` selesai < 50% wall clock dari mode sequential.
- CI gagal di PR yang menurunkan coverage di bawah threshold.

---

## 6. Hal yang Sebaiknya Tidak Dikerjakan Sekarang

Untuk menjaga fokus, beberapa hal yang **sengaja tidak diprioritaskan** di rekomendasi ini meskipun sering muncul sebagai keinginan:

- **Microservices/event-bus** — saat ini service-oriented monolith masih cocok untuk skala koperasi.
- **GraphQL** — REST + Wayfinder + OpenAPI sudah cukup; menambah GraphQL akan menggandakan permukaan API tanpa ROI jelas.
- **Multi-tenancy database-per-tenant** — `organization_id` scoping cukup untuk masa kini; rewrite tenancy mahal.
- **AI/ML credit scoring** — disebut di `architecture.md` jangka panjang, tapi data koperasi belum cukup volume; lebih baik tunggu sampai ada minimal 12 bulan data pinjaman bersih.
- **Native Android/iOS** baru sebelum API contract benar-benar stabil — selesaikan dulu Sprint 3 dan 4 sebelum invest mobile native.

---

## 7. Penutup

Aplikasi sudah pada level "siap operasi awal", dan langkah berikutnya bukan menambah fitur melainkan memastikan setiap proses bisnis kritis (resign, pinjaman, payment, period lock) tidak bisa menghasilkan data inkonsisten, serta arsitektur kode tetap bisa di-maintain saat tim membesar. Sprint 1–2 fokus ke proses bisnis dan keamanan yang dampaknya langsung ke compliance dan kepercayaan anggota; Sprint 3–4 fokus ke keberlanjutan arsitektur dan kualitas engineering.

Setelah seluruh sprint di atas selesai, dokumen ini sebaiknya digabung sebagai sub-bagian "Phase E – Hardening" di `docs/improve3.md` agar history rekomendasi tetap satu jalur.

---

*Dokumen ini disusun berdasarkan audit langsung terhadap kode di repo per 16 Mei 2026, bukan sekadar paraphrase dari dokumen perbaikan sebelumnya.*
