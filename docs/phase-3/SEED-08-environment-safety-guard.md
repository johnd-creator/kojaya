# SEED-08: Environment Safety Guard

## 1. Ringkasan Eksekutif

Dokumen ini menetapkan arsitektur dan spesifikasi teknis dari perlindungan lingkungan (*Environment Safety Guard*) terpusat pada **SEED-08**. Implementasi ini menghapuskan duplikasi dan desentralisasi pengecekan lingkungan yang tersebar di berbagai seeder, mencegah kebocoran data uji (*test fixtures*) ke lingkungan non-lokal seperti `production`, `staging`, `qa`, dan `development`, serta memastikan konsistensi mutlak antara konfigurasi aplikasi (`config('app.env')`) dan lingkungan runtime (`app()->environment()`).

Deliverable utama dari tugas SEED-08:
1. Enum klasifikasi profil eksekusi seeder: [`SeederExecutionProfile`](../../app/Support/SeedSafety/SeederExecutionProfile.php) (`ProductionSafe`, `LocalTestFixture`, `TestOnlyFixture`).
2. Registri keamanan seeder 1:1 otoritatif: [`SeederSafetyRegistry`](../../app/Support/SeedSafety/SeederSafetyRegistry.php) yang mengklasifikasikan seluruh 19 seeder di `database/seeders/` secara lengkap dan terverifikasi otomatis.
3. Guard runtime terpusat dan fail-closed: [`SeederEnvironmentGuard`](../../app/Support/SeedSafety/SeederEnvironmentGuard.php) yang memvalidasi konsistensi lingkungan runtime vs konfigurasi dan menegakkan matriks izin eksekusi.
4. Migrasi seluruh 11 seeder non-produksi ke pemanggilan terpusat `SeederEnvironmentGuard::assertAllowed(static::class)`.
5. Pengamanan [`DatabaseSeeder`](../../database/seeders/DatabaseSeeder.php) dengan pemeriksaan konsistensi terpusat dan pengawalan panggilan fixture lokal.
6. Refaktor layanan reset data [`CooperativeTestDataResetService`](../../app/Services/Cooperative/CooperativeTestDataResetService.php) dan perintah CLI [`CooperativeResetTestData`](../../app/Console/Commands/CooperativeResetTestData.php) untuk mengonsumsi `SeederEnvironmentGuard`.
7. Suite pengujian komprehensif pada [`SeederEnvironmentGuardTest`](../../tests/Feature/SEED08/SeederEnvironmentGuardTest.php) (Scenarios A melalui W, 26 tests, 85 assertions).
8. Pemutakhiran pengujian keamanan dan analisis statis pada [`DatabaseSeederSafetyTest`](../../tests/Feature/DatabaseSeederSafetyTest.php) dan [`SeederSafetyStaticAnalysisTest`](../../tests/Feature/SeederSafetyStaticAnalysisTest.php).

---

## 2. Masalah & Solusi Arsitektur

### A. Masalah Sebelumnya
Sebelum SEED-08:
- Setiap seeder non-referensi menulis logika pengecekan lingkungannya sendiri (`in_array((string) config('app.env'), [...])` atau `app()->environment(...)`).
- Terjadi inkonsistensi sintaks dan pesan error antar-seeder.
- Tidak ada validasi silang antara `config('app.env')` dan `app()->environment()`. Jika terjadi anomali di mana salah satu mengarah ke `testing` sementara yang lain mengarah ke `production`, sistem berisiko meloloskan eksekusi seeder dev ke lingkungan produksi.
- Opsi bawaan Artisan `--force` pada `php artisan db:seed` hanya membungkam prompt konfirmasi terminal di lingkungan produksi, sehingga tidak dapat diandalkan untuk mencegah mutasi berbahaya jika seeder tidak memiliki *hard runtime guard*.

### B. Solusi SEED-08
SEED-08 memperkenalkan arsitektur keamanan terpusat (*single source of truth*):
```text
                  Seeder Invocations
             (DatabaseSeeder / Direct Run / CLI)
                           │
                           ▼
          SeederEnvironmentGuard::assertEnvironmentConsistency()
        ┌─────────────────────────────────────────────────────────┐
        │  1. config('app.env') === app()->environment() ?        │
        │     - Mismatch -> THROW LogicException (Zero mutation)   │
        │     - Empty/Null -> THROW LogicException                │
        │  2. Exact lowercase normalization                       │
        └─────────────────────────────────────────────────────────┘
                           │
                           ▼
               SeederSafetyRegistry::profileFor($class)
        ┌─────────────────────────────────────────────────────────┐
        │  Resolve 1:1 Profile:                                   │
        │  - ProductionSafe (8 seeders)                           │
        │  - LocalTestFixture (9 seeders)                         │
        │  - TestOnlyFixture (2 seeders)                          │
        └─────────────────────────────────────────────────────────┘
                           │
                           ▼
          SeederEnvironmentGuard::isAllowed($profile, $env)
        ┌─────────────────────────────────────────────────────────┐
        │  Match against Authoritative Environment Matrix:        │
        │  - ProductionSafe: [prod, staging, qa, dev, local, test, pw]
        │  - LocalTestFixture: [local, test, pw]                  │
        │  - TestOnlyFixture: [test, pw]                          │
        │                                                         │
        │  Denied -> THROW LogicException (Zero mutation)         │
        │  Permitted -> Proceed with execution                    │
        └─────────────────────────────────────────────────────────┘
```

---

## 3. Profil Eksekusi & Matriks Lingkungan Otoritatif

### A. Profil Eksekusi (`SeederExecutionProfile`)

| Profil | Nilai Enum | Deskripsi & Tujuan |
| :--- | :--- | :--- |
| `ProductionSafe` | `'PRODUCTION_SAFE'` | Data referensi idempoten, peran & hak akses, parameter operasional default yang aman dijalankan di semua lingkungan termasuk produksi. |
| `LocalTestFixture` | `'LOCAL_TEST_FIXTURE'` | Data persona sintetis non-produksi (P01-P10, P12-P13), akun sosial demo, transaksi POS demo, dan simulasi keuangan kanonikal. |
| `TestOnlyFixture` | `'TEST_ONLY_FIXTURE'` | Fixture uji anomali/negatif (persona P11 `BLOCKED_UNKNOWN` / SEED-06) dan fixture UI Audit. Dilarang keras di lingkungan lokal maupun tingkat atas. |

### B. Matriks Lingkungan Otoritatif

| Lingkungan | `ProductionSafe` | `LocalTestFixture` | `TestOnlyFixture` |
| :--- | :---: | :---: | :---: |
| `production` | ✅ Diizinkan | ❌ **DITOLAK** | ❌ **DITOLAK** |
| `staging` | ✅ Diizinkan | ❌ **DITOLAK** | ❌ **DITOLAK** |
| `qa` | ✅ Diizinkan | ❌ **DITOLAK** | ❌ **DITOLAK** |
| `development` | ✅ Diizinkan | ❌ **DITOLAK** | ❌ **DITOLAK** |
| `local` | ✅ Diizinkan | ✅ Diizinkan | ❌ **DITOLAK** |
| `testing` | ✅ Diizinkan | ✅ Diizinkan | ✅ Diizinkan |
| `playwright` | ✅ Diizinkan | ✅ Diizinkan | ✅ Diizinkan |
| Lingkungan lain / tidak dikenal (`sandbox`, `preview`, dll.) | ❌ **DITOLAK** | ❌ **DITOLAK** | ❌ **DITOLAK** |

---

## 4. Klasifikasi Registri Seeder Otoritatif (`SeederSafetyRegistry`)

Tepat 19 seeder terdaftar secara 1:1 pada [`SeederSafetyRegistry`](../../app/Support/SeedSafety/SeederSafetyRegistry.php) (selain `DatabaseSeeder` sebagai root orchestrator):

### A. ProductionSafe (8 Seeder)
1. `TaxRuleSeeder` (Pph21 Ter 2024 reference)
2. `RolePermissionSeeder` (Spatie RBAC definitions)
3. `LoanTypeSeeder` (Default cooperative loan types)
4. `JobGradeSeeder` (HR reference)
5. `LeaveTypeSeeder` (HR reference)
6. `SalaryComponentTypeSeeder` (Payroll reference)
7. `WorkShiftSeeder` (Attendance reference)
8. `CooperativeReferenceSeeder` (KOP-001 anchor & contribution types)

### B. LocalTestFixture (9 Seeder)
1. `CooperativeFixtureReferenceSeeder` (KOP-001 / KBU-001 / ISO-999 topology)
2. `CooperativePersonaSeeder` (P01-P10, P12, P13 User & Member personas)
3. `CooperativeMemberLifecycleSeeder` (Deterministic verification & approval states)
4. `CooperativeFinancialFixtureSeeder` (Invoices, payments, receipts, ledgers, POS)
5. `CooperativeSeeder` (Legacy cooperative demo fixtures)
6. `AnggotaSeeder` (Legacy member demo fixtures)
7. `DemoDataSeeder` (Legacy broad ERP demo fixtures)
8. `InvoiceSeeder` (Generic invoice fixtures)
9. `CooperativeManagerRoleSeeder` (Legacy manager role seeder)

### C. TestOnlyFixture (2 Seeder)
1. `UiAuditSeeder` (Isolated synthetic UI test environment fixtures)
2. `CooperativeEdgeCaseFixtureSeeder` (Canonical P11 `BLOCKED_UNKNOWN` persona & anomaly fixtures)

### D. Penjaminan Kelengkapan Otomatis (`assertCompleteCoverage`)
Metode `SeederSafetyRegistry::assertCompleteCoverage()` memindai direktori `database/seeders/`, membandingkan seluruh berkas `*Seeder.php` (kecuali `DatabaseSeeder.php`) dengan registri, dan melempar `LogicException` jika ditemukan seeder baru yang belum terklasifikasi atau seeder terdaftar yang tidak ada di disk.

---

## 5. Perlindungan Konsistensi Lingkungan (`assertEnvironmentConsistency`)

`SeederEnvironmentGuard::assertEnvironmentConsistency()` menerapkan aturan ketat:
```php
$configured = (string) config('app.env');
$runtime = (string) app()->environment();

if ($configured === '' || $runtime === '') {
    throw new LogicException("Empty environment detected: runtime='{$runtime}' configured='{$configured}'. Execution denied.");
}

if ($configured !== $runtime) {
    throw new LogicException("Seeder environment mismatch detected: runtime={$runtime} configured={$configured}. Execution denied.");
}

return $configured;
```

**Karakteristik Pengamanan:**
1. **Zero Tolerance Mismatch:** Jika konfigurasi dan runtime berbeda (misal `runtime=testing`, `configured=staging`), eksekusi digagalkan seketika sebelum baris kode seeder mana pun dijalankan.
2. **Fail-Closed:** Jika lingkungan bernilai string kosong atau lingkungan asing, sistem menolak eksekusi.
3. **Exact Lowercase Matching:** Nilai seperti `LOCAL` atau `Production` ditolak karena tidak sesuai dengan konvensi lowercase Laravel.

---

## 6. Integrasi dengan Reset Tooling (SEED-07)

[`CooperativeTestDataResetService`](../../app/Services/Cooperative/CooperativeTestDataResetService.php) dan [`CooperativeResetTestData`](../../app/Console/Commands/CooperativeResetTestData.php) kini sepenuhnya mengonsumsi `SeederEnvironmentGuard`:
- Batasan lingkungan dasar reset diambil dari `SeederEnvironmentGuard::allowedEnvironmentsFor(SeederExecutionProfile::LocalTestFixture)`.
- Batasan opsi `--with-edge-cases` divalidasi menggunakan `SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::TestOnlyFixture, $env)`.
- Pemeriksaan konsistensi lingkungan dijalankan sebelum pratinjau (`--dry-run`) maupun eksekusi mutasi langsung (`reset()`).

---

## 7. Ketahanan terhadap Flag `--force`

Pada Laravel, perintah:
```bash
php artisan db:seed --force
```
hanya membungkam konfirmasi interaktif terminal `confirmToProceed()` saat aplikasi berada dalam status produksi. Karena `SeederEnvironmentGuard::assertAllowed(static::class)` diletakkan pada baris pertama di dalam metode `run()` dari setiap kelas seeder, penggunaan flag `--force` sama sekali tidak dapat mem-bypass guard runtime ini.

Uji verifikasi khusus pada `SeederEnvironmentGuardTest::test_artisan_db_seed_force_flag_cannot_bypass_environment_guard` membuktikan bahwa pemanggilan `Artisan::call('db:seed', ['--class' => CooperativePersonaSeeder::class, '--force' => true])` di lingkungan `production` tetap menghasilkan `LogicException` dan mutasi database bernilai tepat 0.

---

## 8. Verifikasi & Matriks Pengujian

Pengujian SEED-08 mencakup:

1. **[`SeederEnvironmentGuardTest`](../../tests/Feature/SEED08/SeederEnvironmentGuardTest.php) (26 tests, 85 assertions):**
   - **Scenario A:** Konsistensi runtime == configured lolos (`testing`, `local`).
   - **Scenario B:** Mismatch runtime=testing vs configured=staging ditolak dengan pesan presisi.
   - **Scenario C:** Mismatch runtime=local vs configured=production ditolak dengan pesan presisi.
   - **Scenario D:** Nilai lingkungan kosong ditolak seketika.
   - **Scenario E:** Penegakan huruf kecil (lowercase exact match).
   - **Scenario F - L:** Profil `ProductionSafe` diizinkan di `production`, `staging`, `qa`, `development`, `local`, `testing`, dan `playwright`.
   - **Scenario M:** Profil `ProductionSafe` ditolak di lingkungan tak dikenal (`sandbox`, `preview`, `custom`).
   - **Scenario N - P:** Profil `LocalTestFixture` diizinkan di `local`, `testing`, dan `playwright`.
   - **Scenario Q:** Profil `LocalTestFixture` ditolak keras di `production` dengan 0 mutasi pengguna/anggota.
   - **Scenario R:** Profil `LocalTestFixture` ditolak keras di `staging` dengan 0 mutasi pengguna/anggota.
   - **Scenario S:** Profil `LocalTestFixture` ditolak di `qa` dan `development`.
   - **Scenario T:** Profil `LocalTestFixture` ditolak di lingkungan tak dikenal.
   - **Scenario U:** Profil `TestOnlyFixture` diizinkan di `testing` dan `playwright`.
   - **Scenario V:** Profil `TestOnlyFixture` ditolak keras di `local`.
   - **Scenario W:** Profil `TestOnlyFixture` ditolak di `production`, `staging`, `qa`, `development`.
   - **Extra Guarantees:** Uji resistensi flag `--force`, uji kelas tidak terdaftar, dan uji kelengkapan 1:1 `SeederSafetyRegistry`.

2. **[`DatabaseSeederSafetyTest`](../../tests/Feature/DatabaseSeederSafetyTest.php) (11 tests, 167 assertions):**
   - Pengujian terpadu keamanan `DatabaseSeeder` terhadap lingkungan staging, production, dan local.

3. **[`SeederSafetyStaticAnalysisTest`](../../tests/Feature/SeederSafetyStaticAnalysisTest.php) (6 tests, 182 assertions):**
   - Analisis statis yang memastikan setiap seeder di disk bebas dari operasi destruktif, tidak membuat kredensial sembarangan, memanggil `SeederEnvironmentGuard::assertAllowed()`, dan terdaftar 100% pada registri.

4. **Regresi Penuh Suite Terkait:**
   - `CooperativeResetTestDataCommandTest` (23 passed, 125 assertions)
   - `CooperativePersonaSeederTest` & `CooperativeMemberLifecycleSeederTest` (34 passed, 498 assertions)
   - `UiAuditSeederTest` & `CooperativeReferenceSeederTest` (16 passed, 183 assertions)
   - Verifikasi partisi shard PHPUnit CI (`php bin/ci/phpunit-shard verify`): 280 test files, 100% terdistribusi merata across 4 shards.
