# SEED-01: Test Data Contract & Dataset Matrix

## Dokumen Informasi

- **Platform:** Kojaya (KojayaPro & Kojayaku)
- **Fase Roadmap:** Phase 3 — Seed & Test Data (`SEED-01`)
- **Status:** Authoritative Contract / Baseline Specification
- **Otoritas Kode:**
  - `DatabaseSeeder`: [database/seeders/DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php)
  - `MemberLifecycleExperience`: [app/Enums/Cooperative/MemberLifecycleExperience.php](../../app/Enums/Cooperative/MemberLifecycleExperience.php)
  - `RolePermissionSeeder`: [database/seeders/RolePermissionSeeder.php](../../database/seeders/RolePermissionSeeder.php)
  - `CooperativeReferenceSeeder`: [database/seeders/CooperativeReferenceSeeder.php](../../database/seeders/CooperativeReferenceSeeder.php)
  - `CooperativeMemberFactory`: [database/factories/CooperativeMemberFactory.php](../../database/factories/CooperativeMemberFactory.php)
- **Baseline Git HEAD:** `fa316aef5be1445c87fec681b2d8b44296f21579` (Pasca merge PR #64 / ONB-09)
- **Dokumentasi Pendukung:**
  - [docs/onboarding/first-login-lifecycle-experience.md](../onboarding/first-login-lifecycle-experience.md)
  - [docs/onboarding/google-sso-member-matching.md](../onboarding/google-sso-member-matching.md)
  - [docs/architecture.md](../architecture.md)

---

## 1. Eksekutif Ringkasan & Misi SEED-01

Tujuan dari `SEED-01` adalah meletakkan fondasi desain, kontrak data, dan inventarisasi komprehensif bagi seluruh rangkaian **Phase 3 (Seed & Test Data)**. Dokumen ini mendefinisikan secara otoritatif:
1. **Data apa** yang harus ada untuk menguji seluruh proses bisnis koperasi.
2. **Mengapa** setiap dataset dibutuhkan dalam pengujian fungsional dan E2E (Phase 4).
3. **Seeder dan factory mana** yang saat ini telah tersedia di repositori `main`.
4. **Kesenjangan (gap)** apa yang masih ada dan harus diselesaikan oleh tugas SEED-02 hingga SEED-09.
5. **Persona dan siklus hidup (lifecycle)** mana yang diwakili oleh data uji.
6. **Environment mana** yang diizinkan untuk memuat data tersebut.
7. **Identitas deterministik** dan konvensi penamaan yang wajib digunakan untuk mencegah tabrakan data (*data collision*) dan menjaga keamanan data produksi.

> [!IMPORTANT]
> SEED-01 **TIDAK** mengubah kode produksi, migrasi, konfigurasi, maupun implementasi seeder yang sudah berjalan. Dokumen ini adalah kontrak spesifikasi tunggal (*source of truth*) agar tugas implementasi seeder berikutnya (SEED-02 s.d. SEED-09) dapat dibangun tanpa membuat data uji *ad-hoc*.

---

## 2. Arsitektur Seeder Saat Ini (Existing Landscape)

### 2.1. Pemisahan Lingkungan di `DatabaseSeeder`
Repositori saat ini di [database/seeders/DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php#L14-L34) telah menerapkan pemisahan tegas antara data referensi yang aman untuk produksi (*production-safe reference data*) dan data demo/fixture yang hanya boleh dijalankan di lingkungan lokal/pengujian:

```text
DatabaseSeeder::run()
  ├── [Production-Safe Reference — Dijalankan di semua environment]
  │     ├── TaxRuleSeeder
  │     ├── RolePermissionSeeder
  │     ├── LoanTypeSeeder
  │     ├── JobGradeSeeder
  │     ├── LeaveTypeSeeder
  │     ├── SalaryComponentTypeSeeder
  │     ├── WorkShiftSeeder
  │     └── CooperativeReferenceSeeder
  │
  └── [Local Only Guarded Fixture — app()->environment('local')]
        ├── CooperativeSeeder
        ├── AnggotaSeeder
        └── DemoDataSeeder
```

Selain seeder yang dipanggil langsung oleh `DatabaseSeeder`, terdapat tiga seeder mandiri (*standalone seeders*):
- `CooperativeManagerRoleSeeder.php` (Guarded: `local`, `testing`, `playwright`)
- `InvoiceSeeder.php` (Guarded: `local`, `testing`, `playwright`)
- `UiAuditSeeder.php` (Guarded ketat: `testing`, `playwright` — tidak termasuk `local`)

---

## 3. Inventarisasi Lengkap Seeder (Seeder Inventory)

Berdasarkan inspeksi mendalam terhadap seluruh berkas di [database/seeders/](../../database/seeders/), berikut adalah tabel inventarisasi seluruh 15 seeder yang ada di repositori:

| Nama Seeder | Tujuan & Ruang Lingkup | Environment Diizinkan | Production-Safe? | Deterministik? | Strategi Idempoten | Entitas Utama | Kredensial Pengguna? | Data Finansial Sintetis? | Risiko & Kesenjangan Saat Ini | Disposisi Phase 3 |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TaxRuleSeeder** | Inisialisasi tarif pajak default PPh 21 TER 2024 | Semua | **Ya** | Ya | `updateOrCreate(['code'])` | `TaxRule` | Tidak | Tidak | Tidak ada risiko. | `KEEP` |
| **RolePermissionSeeder** | Registrasi seluruh peran ERP & Koperasi beserta hak akses (Spatie) | Semua | **Ya** | Ya | `firstOrCreate(['name'])` + `syncPermissions()` | `Role`, `Permission` | Tidak | Tidak | Wajib dijalankan sebelum seeder persona apapun. | `KEEP` |
| **LoanTypeSeeder** | Master data produk pinjaman kanonikal (Darurat, Produktif, Konsumtif) | Semua | **Ya** | Ya | `firstOrCreate(['code'])` | `LoanType` | Tidak | Tidak | Melindungi perubahan parameter suku bunga operator saat deployment. | `KEEP` |
| **JobGradeSeeder** | Master grade jabatan HR (Pelaksana s.d. Direksi) | Semua | **Ya** | Ya | `firstOrCreate(['code'])` | `JobGrade` | Tidak | Tidak | Dependensi modul HR & ERP. | `KEEP` |
| **LeaveTypeSeeder** | Master jenis cuti (Tahunan, Sakit, Melahirkan, Menikah, dll.) | Semua | **Ya** | Ya | `firstOrCreate(['name'])` | `LeaveType` | Tidak | Tidak | Dependensi modul absensi & cuti karyawan. | `KEEP` |
| **SalaryComponentTypeSeeder** | Master komponen gaji (P1, P2, TGT, TPL, TP) | Semua | **Ya** | Ya | `firstOrCreate(['code'])` | `SalaryComponentType` | Tidak | Tidak | Dependensi modul payroll. | `KEEP` |
| **WorkShiftSeeder** | Master jadwal kerja shift dan non-shift | Semua | **Ya** | Ya | `firstOrCreate(['name'])` | `WorkShift` | Tidak | Tidak | Dependensi absensi & HRM. | `KEEP` |
| **CooperativeReferenceSeeder** | Master data referensi koperasi: Organisasi induk `KOP-001`, jenis simpanan (POKOK, WAJIB, SUKARELA, KHUSUS), kategori POS | Semua | **Ya** | Ya | `firstOrCreate(['code'])` / `firstOrCreate(['slug'])` | `Organization`, `CooperativeContributionType`, `PosCategory` | Tidak | Tidak | Aman untuk produksi; tidak membuat user atau transaksi sintetis. | `KEEP` |
| **CooperativeManagerRoleSeeder** | Fixture role dan akun Manajer Koperasi mandiri | `local`, `testing`, `playwright` | **Tidak** | Ya | `updateOrCreate(['email'])` | `Role`, `Permission`, `Organization`, `User` | **Ya** (`manajer.kop@koj.id` / `password`) | Tidak | Tidak dipanggil di `DatabaseSeeder`; membuat user dengan sandi statis. | `REPLACE LATER` (Dimigrasikan ke SEED-03) |
| **CooperativeSeeder** | Fixture komprehensif demo koperasi: 2 cabang, 10 anggota aktif, iuran wajib bulanan, katalog POS, 9 transaksi POS, penutupan SHU 2025 | `local`, `testing`, `playwright` | **Tidak** | Parsial | `updateOrCreate` & `firstOrCreate` | `Organization`, `CooperativeMember`, `CooperativeDuesInvoice`, `CooperativePayment`, `PosProduct`, `PosTransaction`, `CooperativeShuPeriod` | **Ya** (`admin.kop@koj.id`, `kasir@koperasijayabersama.id` / `password`) | **Ya** (Iuran, POS, SHU) | Kalkulasi iuran wajib menggunakan `Carbon::now()`, memanipulasi `Carbon::setTestNow()`, anggota hanya berstatus ACTIVE tanpa akun User login. | `REUSE` & `EXTEND LATER` (Dipegang oleh SEED-04 & SEED-05) |
| **AnggotaSeeder** | Fixture 10 anggota koperasi demo terklasifikasi (`DEMO-ANG-001` s.d. `010`) beserta riwayat iuran wajib | `local`, `testing`, `playwright` | **Tidak** | Parsial | `updateOrCreate(['no_anggota'])` | `CooperativeMember`, `CooperativeDuesInvoice`, `CooperativePayment` | Tidak (Hanya data member tanpa user login) | **Ya** (Iuran pokok & wajib) | Anggota tidak memiliki relasi `User` (tidak bisa login Web/Mobile). Periode iuran bergantung pada `Carbon::now()`. | `REUSE` & `EXTEND LATER` (Diorganisir di SEED-04) |
| **DemoDataSeeder** | Fixture demo ERP skala besar: karyawan, absensi, cuti, lembur, aset, work order, petty cash, project, vendor | `local`, `testing`, `playwright` | **Tidak** | Parsial | `updateOrCreate` & `firstOrCreate` | `Organization`, `Department`, `Employee`, `User`, `Asset`, `WorkOrder`, `Project`, dll. | **Ya** (Beberapa user karyawan / `password`) | **Ya** (Petty cash, invoice proyek) | Terlalu besar dan berfokus pada ERP umum, bukan alur inti koperasi/anggota. Mengandalkan `RolePermissionSeeder`. | `REFERENCE ONLY` (Dipertahankan untuk demo ERP, tidak diubah di Phase 3) |
| **InvoiceSeeder** | Pembuatan tagihan proyek acak via Factory | `local`, `testing`, `playwright` | **Tidak** | **Tidak** (Menggunakan `Invoice::factory()->count(5)`) | Tidak ada (Insert acak) | `Invoice` | Tidak | **Ya** (Tagihan ERP acak) | Non-deterministik; menghasilkan data acak yang membuat assertion E2E tidak stabil. | `REPLACE LATER` (Bila modul invoice ERP diuji) |
| **UiAuditSeeder** | Fixture pengujian visual regression (Playwright) dengan tanggal statis `2026-01-15`, organisasi `AUD-*`, anggota `AUD-*`, kredit toko, POS, pinjaman | `testing`, `playwright` (Menolak `local`!) | **Tidak** | **Sangat Tinggi** | `updateOrCreate(['code'])` | `Organization`, `User`, `CooperativeMember`, `MemberStoreAccount`, `PosProduct`, `Loan` | **Ya** (`ui.*@kojaya.test` / `UiAudit!2026`) | **Ya** (Kredit toko, pinjaman, tagihan iuran) | Dirancang khusus untuk snapshot visual Playwright dan menolak dijalankan di environment `local`. | `REFERENCE ONLY` (Tetap terisolasi untuk visual test) |
| **DatabaseSeeder** | Orkestrator utama pemanggilan seeder | Tergantung sub-seeder | **Ya** (Secara default; fixture dibatasi `local`) | Mengikuti sub-seeder | Mengikuti sub-seeder | Seluruh entitas | Mengikuti sub-seeder | Mengikuti sub-seeder | Memanggil `CooperativeSeeder`, `AnggotaSeeder`, dan `DemoDataSeeder` sekaligus pada `local`. | `EXTEND LATER` (SEED-07 untuk orkestrasi reseed) |

---

## 4. Evaluasi Mekanisme Environment Guard (Keamanan Lingkungan)

### 4.1. Pola Guard Saat Ini
Seluruh seeder fixture demo non-produksi dilindungi oleh pemeriksaan environment pada awal method `run()`:
```php
if (! in_array((string) config('app.env'), ['local', 'testing', 'playwright'], true)) {
    throw new \LogicException('<SeederName> is only available in local, testing, or playwright environments.');
}
```

Khusus untuk `UiAuditSeeder`, restriksi dibuat lebih ketat:
```php
if (! in_array((string) config('app.env'), ['testing', 'playwright'], true)) {
    throw new \LogicException('UiAuditSeeder is only available in testing or playwright environments.');
}
```

### 4.2. Bukti Uji Keamanan (Test Evidence)
Mekanisme ini telah diverifikasi secara otomatis oleh dua suite pengujian:
1. [tests/Feature/DatabaseSeederSafetyTest.php](../../tests/Feature/DatabaseSeederSafetyTest.php):
   - Membuktikan bahwa seeder demo melempar `LogicException` jika `app.env` disetel ke `production`, `staging`, `qa`, maupun `development`.
2. [tests/Feature/SeederSafetyStaticAnalysisTest.php](../../tests/Feature/SeederSafetyStaticAnalysisTest.php):
   - Memverifikasi secara statis bahwa:
     - Tidak ada seeder referensi yang memanggil operasi destruktif (`truncate`, `forceDelete`, `DROP TABLE`).
     - Tidak ada seeder referensi yang membuat user atau password (`User::create`, `Hash::make`).
     - Seeder operasional referensi menggunakan `firstOrCreate` agar konfigurasi operator tidak tertimpa.
     - Seluruh seeder non-referensi memiliki guard `LogicException`.
     - Seluruh berkas seeder di `database/seeders/` terklasifikasi secara lengkap.

### 4.3. Rekomendasi Penguatan pada SEED-08
- Menjaga seluruh guard di atas agar tidak dilemahkan.
- Menambahkan parameter CLI safety guard (misal flag `--force-dev-fixture`) jika seeder dijalankan via Artisan command kustom.
- Menegaskan bahwa di environment staging atau produksi, perintah `db:seed` default hanya mengeksekusi kelas dengan klasifikasi `PRODUCTION_SAFE_REFERENCE`.

---

## 5. Inventarisasi Factory (Factory Landscape)

Terdapat 63 factory yang ditemukan pada [database/factories/](../../database/factories/). Di bawah ini adalah inventarisasi factory yang relevan secara langsung terhadap domain Koperasi, Anggota, Autentikasi, dan Keuangan:

| Nama Factory | Definisi Default Signifikan | State Khusus yang Tersedia | Tingkat Determinisme | Cocok untuk Otomasi Uji (Unit/Feature)? | Cocok untuk Persistent DEV Seed? | Kesenjangan / Keterbatasan Utama |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **UserFactory** | `email_verified_at => now()`, `password => 'password'` | `unverified()`, `withTwoFactor()` | Acak (Faker) | **Ya** | **Tidak** | Menghasilkan email acak; tidak memiliki state role bawaan (`assignRole`). |
| **CooperativeMemberFactory** | Menghasilkan relasi otomatis: `Organization`, `Employee`, `User`. Status default: `PENDING / PENDING` | `active()`, `pendingReview()`, `pending()`, `revision()`, `rejected()` | Acak (Faker) | **Ya** | **Tidak** | Relasi nested (`Organization`, `Employee`, `User`) otomatis dibuat saat factory dijalankan tanpa parameter. Belum ada named state untuk `blockedUnknown()`. |
| **OrganizationFactory** | `level => 'L0'`, `type => 'HEAD_OFFICE'`, `is_active => true` | Tidak ada | Acak (Faker) | **Ya** | **Tidak** | Kode organisasi acak (`ORG#####`); untuk DEV seed diperlukan kode deterministik seperti `KOP-001`. |
| **EmployeeFactory** | Relasi otomatis `Organization`, status `ACTIVE` | Tidak ada | Acak (Faker) | **Ya** | **Tidak** | `employee_code` acak (`EMP#####`). |
| **SocialAccountFactory** | Provider `google`, `provider_id` acak numerik, token dummy | `google()` | Acak (Faker) | **Ya** | **Tidak** | Membutuhkan `UserFactory` jika `user_id` tidak disuplai. Cocok untuk mock OAuth, bukan persona deterministik statis. |
| **CooperativeContributionTypeFactory** | Category `WAJIB`, default amount 50.000 | `pokok()`, `wajib()` | Acak (Faker code) | **Ya** | **Tidak** | Menghasilkan kode unik acak yang berpotensi konflik jika dipanggil berulang kali. |
| **CooperativeLedgerEntryFactory** | Entry type acak (`SAVINGS_DEPOSIT`, dll.), amount acak | Tidak ada | Acak (Faker) | **Ya** | **Tidak** | Ledger entries acak tidak membentuk rekonsiliasi saldo yang konsisten. |
| **MemberStoreAccountFactory** | `balance => 0`, `credit_limit => 0`, status `Active` | `withLimit(int)` | Moderat | **Ya** | **Tidak** | Bergantung pada `CooperativeMemberFactory`. |
| **LoanFactory** | Plafon 3.000.000, 6 bulan, bunga 1.5%, status `Applied` | `active()` | Formula kalkulasi internal | **Ya** | **Tidak** | Membuat `Organization`, `LoanType`, `CooperativeMember`, dan `User` secara berantai bila tidak di-override. |
| **LoanTypeFactory** | Emergency loan acak | Tidak ada | Acak (Faker) | **Ya** | **Tidak** | Seeder `LoanTypeSeeder` lebih disukai untuk konsistensi. |
| **PosCategoryFactory** | Kategori acak | Tidak ada | Acak (Faker) | **Ya** | **Tidak** | Slug acak. |
| **PosProductFactory** | Produk POS dengan harga & stok acak | Tidak ada | Acak (Faker) | **Ya** | **Tidak** | SKU dan barcode acak, menyulitkan pengujian barcode scanner pada POS. |
| **PointTransactionFactory** | Poin acak | Tidak ada | Acak (Faker) | **Ya** | **Tidak** | Saldo poin tidak teragregasi secara deterministik. |
| **MemberPaymentIntentFactory** | Intent QRIS pending | Tidak ada | Acak (Faker) | **Ya** | **Tidak** | Reference acak `PAY-########`. |
| **CooperativeReceiptFactory** | **PENTING:** Memanggil `create()` pada relasi nested di dalam `definition()`! | Tidak ada | Efek samping database (*DB write side-effects*) | **Hati-hati** | **Sangat Tidak Cocok** | Mengeksekusi penulisan ke DB bahkan saat hanya memanggil `definition()` / `make()`. Berbahaya jika digunakan di luar sandbox. |

### 5.1. Prinsip: Test Factory ≠ Persistent DEV Seed Data
Factory sangat ideal untuk **automated unit & feature tests** yang berjalan di dalam transaksi `RefreshDatabase` (SQLite `:memory:`), di mana data dibuat cepat dan segera dimusnahkan.

Sebaliknya, **Persistent DEV Seed Data** mensyaratkan:
1. Identitas yang stabil dan dapat diprediksi (email, NIK, nomor anggota tetap sama di setiap proses reseed).
2. Kredensial login yang diketahui oleh tester/developer manual.
3. Keterkaitan relasional yang valid (User memiliki Role yang tepat, terhubung ke Member, memiliki saldo yang konsisten dengan mutasi ledger).
4. Tidak adanya entitas yatim (*orphaned entities*) akibat pembuatan acak berulang.

---

## 6. Pemetaan Siklus Hidup Anggota (Member Lifecycle Dataset Contract)

Sesuai arsitektur yang diresmikan pada `ONB-08` dan `ONB-09` melalui enum [app/Enums/Cooperative/MemberLifecycleExperience.php](../../app/Enums/Cooperative/MemberLifecycleExperience.php), kombinasi status basis data dipetakan secara kanonikal ke dalam 6 pengalaman siklus hidup:

```text
[status = PENDING, validation_status = PENDING]
   │
   ├──▶ WAITING_VERIFICATION (Menunggu Verifikasi Admin)
   │
[status = PENDING, validation_status = PENDING_VALIDATION]
   │
   ├──▶ UNDER_REVIEW (Menunggu Approval Pengurus)
   │
[status = INACTIVE, validation_status = REVISION]
   │
   ├──▶ REVISION_REQUIRED (Perlu Revisi Profil/Berkas)
   │
[status = INACTIVE, validation_status = REJECTED]
   │
   ├──▶ REJECTED (Pendaftaran Ditolak)
   │
[status = ACTIVE, validation_status = ACTIVE]
   │
   ├──▶ ACTIVE (Anggota Aktif Penuh)
   │
[Semua kombinasi lain / INACTIVE/INACTIVE / RESIGNED / Korup]
   │
   └──▶ BLOCKED_UNKNOWN (Akses Dibatasi — HTTP 403 Fail-Closed)
```

### 6.1. Matriks Kontrak Siklus Hidup

| Pengalaman Siklus Hidup | Nilai `status` DB | Nilai `validation_status` DB | Factory State Tersedia? | Fixture di Demo Seeder Saat Ini? | Akun User Login? | Akun Google SSO? | Hak Akses Web Fortify | Hak Akses Kojayaku Mobile | Akses Finansial & POS? | Profil Dapat Diedit Anggota? | Perilaku Google SSO Matching | Tujuan Pengujian Phase 4 |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **WAITING_VERIFICATION** | `PENDING` | `PENDING` | `pending()` | **Belum Ada** (Hanya ada di ONB-09 test fixture) | Ya | Ya (Optional) | Redirect ke `/member/onboarding` | Status `WAITING_VERIFICATION` | ❌ Ditolak (403) | ✅ Ya (Hanya kolom profil non-PII) | Diizinkan match; mengaitkan akun | Verifikasi admin koperasi, penolakan akses dini fitur finansial |
| **UNDER_REVIEW** | `PENDING` | `PENDING_VALIDATION` | `pendingReview()` | **Belum Ada** (Hanya ada di UiAudit / test) | Ya | Ya (Optional) | Redirect ke `/member/onboarding` | Status `UNDER_REVIEW` | ❌ Ditolak (403) | ❌ Read-only (Sedang diperiksa) | Diizinkan match; mengaitkan akun | Persetujuan akhir pengurus koperasi (*maker-checker*) |
| **REVISION_REQUIRED** | `INACTIVE` | `REVISION` | `revision()` | **Belum Ada** (Hanya ada di UiAudit / test) | Ya | Ya (Optional) | Redirect ke `/member/onboarding` | Status `REVISION_REQUIRED` | ❌ Ditolak (403) | ✅ Ya (Wajib perbaiki catatan verifikator) | Diizinkan match; mengaitkan akun | Alur pengiriman ulang revisi pendaftaran oleh calon anggota |
| **REJECTED** | `INACTIVE` | `REJECTED` | `rejected()` | **Belum Ada** | Ya | Ya (Optional) | Redirect ke `/member/onboarding` | Status `REJECTED` | ❌ Ditolak (403) | ❌ Read-only (Telah ditolak) | Diizinkan match; mengaitkan akun | Tampilan penolakan permanen dan alasan penolakan |
| **ACTIVE** | `ACTIVE` | `ACTIVE` | `active()` | **Ada** (10 member di `CooperativeSeeder`, 10 di `AnggotaSeeder`) | Parsial (Hanya staf; member demo belum ada User) | Parsial | Dashboard penuh `/member` | Dashboard penuh Kojayaku | ✅ Diizinkan | ✅ Ya (Profil anggota normal) | Diizinkan login langsung | Seluruh transaksi simpan pinjam, belanja toko, poin, SHU |
| **BLOCKED_UNKNOWN** | Kombinasi lain (misal: `ACTIVE/PENDING` atau `RESIGNED`) | Nilai apapun | **Tidak Ada** (Perlu ditambahkan di SEED-04) | **Belum Ada** | Ya | Tidak | **HTTP 403 Forbidden** fail-closed | **HTTP 403 Forbidden** fail-closed | ❌ Ditolak (403) | ❌ Tidak | Gagal match / fail-closed | Uji keamanan pencegahan pembobolan otorisasi (*tampering/edge-case*) |

---

## 7. Matriks Persona Deterministik (Persona Matrix)

Untuk mendukung seluruh skenario pengujian fungsional dan otomatisasi pada Phase 4, didefinisikan **13 Definisi Persona Deterministik Koperasi**, yang terbagi menjadi **12 Persona Baseline Valid (P01–P10, P12, P13)** dan **1 Persona Edge-Case Tidak Sah / Korup (P11)**. Persona **P14 dan P15 DI-RESERVED** untuk kebutuhan persona tenaga kerja (*workforce/employee*) anak usaha di masa depan tanpa mengubah penomoran ID.

> [!NOTE]
> Seluruh persona ini bersifat sintetis (*synthetic identities*). Tidak ada satu pun data anggota atau karyawan riil yang digunakan.

> [!IMPORTANT]
> **Aturan Bisnis Entitas Hukum Koperasi vs Anak Usaha (SEED-02R1):**
> Hanya entitas legal koperasi (`KOP-001`) yang berhak memiliki anggota koperasi (`CooperativeMember`), proses onboarding anggota, simpanan, pinjaman, dan SHU.
> `KBU-001` adalah PT Anak Usaha (*subsidiary company*). `KBU-001` dapat memiliki tenaga kerja (`Employee`), pengguna operasional (`User`), tetapi **DILARANG KERAS memiliki `CooperativeMember`** (`CooperativeMember count = 0`).
> `ISO-999` adalah organisasi pihak ketiga sintetis yang terisolasi untuk pengujian otorisasi multi-tenant; tidak menerima persona anggota default pada SEED-03.

```text
                               ┌────────────────────────┐
                               │   P01 System Admin     │
                               └───────────┬────────────┘
                                           │
         ┌─────────────────────────────────┴─────────────────────────────────┐
         ▼                                                                   ▼
┌─────────────────────────────────┐                       ┌──────────────────────────────────┐
│ Koperasi Utama (KOP-001)        │                       │ PT Anak Usaha (KBU-001)          │
│ Legal Cooperative Entity        │                       │ Subsidiary Commercial Company    │
│ (OWNS ALL COOPERATIVE MEMBERS)  │                       │ (NO COOPERATIVE MEMBERS)         │
└────────────────┬────────────────┘                       └────────────────┬─────────────────┘
                 │                                                                 │
                 ├─ P02 Pengurus Koperasi                                          ├─ P14 [RESERVED: Future Employee PT]
                 ├─ P03 Manajer Koperasi                                           └─ P15 [RESERVED: Future Admin/HR PT]
                 ├─ P04 Admin Koperasi
                 ├─ P05 Kasir Koperasi
                 │
                 └─ [Anggota Berdasarkan Siklus Hidup - KOP-001]
                       ├─ P06 Anggota WAITING_VERIFICATION
                       ├─ P07 Anggota UNDER_REVIEW
                       ├─ P08 Anggota REVISION_REQUIRED
                       ├─ P09 Anggota REJECTED
                       ├─ P10 Anggota ACTIVE (Password Fortify)
                       ├─ P11 Anggota BLOCKED_UNKNOWN (Optional Edge-Case / Excluded from Baseline DEV)
                       ├─ P12 Anggota ACTIVE + Google SSO Terhubung
                       └─ P13 Anggota ACTIVE Murni (Tanpa Google SSO)
```

### 7.1. Kontrak Detail Setiap Persona

| Persona ID | Nama Persona & Tujuan Uji | Peran (Role) | Organisasi | User Diperlukan? | Employee Diperlukan? | Member Diperlukan? | SocialAccount Diperlukan? | Siklus Hidup Target | Web Login? | Mobile Login? | Portal Anggota? | Akses Fitur Finansial? | Kasus Uji Utama (Phase 4) |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **P01** | **System Admin Platform**<br>Manajemen global, audit log, konfigurasi sistem | `System Admin` | `KOP-001` | **Ya** | Opsional | Tidak | Tidak | N/A | **Ya** | Tidak | Tidak | N/A (Hak admin penuh) | Bypass izin, audit log global, setup modul |
| **P02** | **Pengurus Koperasi Utama**<br>Approval akhir pendaftaran anggota, approval pinjaman, void POS | `Pengurus Koperasi` | `KOP-001` | **Ya** | Opsional | Opsional | Tidak | N/A | **Ya** | Tidak | Tidak | **Otoritas Approval** | Approval anggota baru (ONB-03/09), persetujuan pinjaman, penutupan SHU |
| **P03** | **Manajer Koperasi**<br>Review pinjaman, pengawasan kredit toko, approval void POS | `Manajer Koperasi` | `KOP-001` | **Ya** | Opsional | Opsional | Tidak | N/A | **Ya** | Tidak | Tidak | **Otoritas Review** | Review berjenjang pinjaman (*maker-checker* sebelum Pengurus) |
| **P04** | **Admin Koperasi**<br>Import CSV anggota, verifikasi data/NIK, kelola iuran & katalog POS | `Admin Koperasi` | `KOP-001` | **Ya** | Opsional | Opsional | Tidak | N/A | **Ya** | Tidak | Tidak | **Otoritas Verifikasi** | Verifikasi pendaftaran anggota, import data, tagihan iuran bulanan |
| **P05** | **Kasir Koperasi**<br>Operasional kasir POS, transaksi belanja, pembayaran kredit toko | `Kasir Koperasi` | `KOP-001` | **Ya** | Opsional | Tidak | Tidak | N/A | **Ya** | Tidak | Tidak | **Kasir POS** | Transaksi POS tunai, QRIS, dan *member store credit* |
| **P06** | **Anggota Menunggu Verifikasi**<br>Calon anggota baru masuk via SSO/form, menunggu verifikasi Admin | `Anggota` | `KOP-001` | **Ya** | Tidak | **Ya** | Opsional | `WAITING_VERIFICATION` | **Ya** | **Ya** | Halaman Onboarding | ❌ Ditolak (403) | Pengujian verifikasi Admin Koperasi, proteksi rute simpanan |
| **P07** | **Anggota Menunggu Approval Pengurus**<br>Sudah diverifikasi Admin, menunggu approval Pengurus | `Anggota` | `KOP-001` | **Ya** | Tidak | **Ya** | Opsional | `UNDER_REVIEW` | **Ya** | **Ya** | Halaman Onboarding | ❌ Ditolak (403) | Pengujian approval Pengurus Koperasi (*second layer approval*) |
| **P08** | **Anggota Perlu Revisi**<br>Berkas ditolak sebagian oleh Admin/Pengurus, diminta memperbaiki data | `Anggota` | `KOP-001` | **Ya** | Tidak | **Ya** | Opsional | `REVISION_REQUIRED` | **Ya** | **Ya** | Halaman Onboarding | ❌ Ditolak (403) | Pengujian alur edit profil koreksi pendaftaran |
| **P09** | **Anggota Ditolak**<br>Pendaftaran keanggotaan ditolak permanen | `Anggota` | `KOP-001` | **Ya** | Tidak | **Ya** | Opsional | `REJECTED` | **Ya** | **Ya** | Halaman Onboarding (Read-only) | ❌ Ditolak (403) | Pengujian halaman penolakan dan proteksi pengiriman ulang |
| **P10** | **Anggota Aktif Kanonikal**<br>Anggota aktif penuh dengan autentikasi sandi reguler | `Anggota` | `KOP-001` | **Ya** | Tidak | **Ya** | Tidak | `ACTIVE` | **Ya** | **Ya** | Dashboard Lengkap | ✅ Diizinkan Penuh | Cek saldo simpanan, ajukan pinjaman, riwayat transaksi toko |
| **P11** | **Anggota Status Korup / Mismatch (Optional Edge)**<br>Kombinasi status tidak sah untuk uji fail-closed keamanan; **dikecualikan dari baseline default DEV** | `Anggota` | `KOP-001` | **Ya** | Tidak | **Ya** | Tidak | `BLOCKED_UNKNOWN` | ❌ 403 Forbidden | ❌ 403 Forbidden | ❌ 403 Forbidden | ❌ Ditolak (403) | Uji keamanan gerbang middleware `EnsureMemberFullyActive` (Fixture opsional/on-demand) |
| **P12** | **Anggota Aktif Terhubung Google SSO**<br>Anggota aktif dengan akun Google terhubung di `social_accounts` | `Anggota` | `KOP-001` | **Ya** | Tidak | **Ya** | **Ya** (`google`) | `ACTIVE` | **Ya** (SSO/Pwd) | **Ya** (SSO/Pwd) | Dashboard Lengkap | ✅ Diizinkan Penuh | Pengujian autentikasi cepat Google SSO, auto-login Mobile |
| **P13** | **Anggota Aktif Tanpa Google SSO**<br>Anggota aktif murni berbasis email dan sandi lokal | `Anggota` | `KOP-001` | **Ya** | Tidak | **Ya** | Tidak | `ACTIVE` | **Ya** (Pwd) | **Ya** (Pwd) | Dashboard Lengkap | ✅ Diizinkan Penuh | Pengujian alur tautkan Google SSO dari profil pengguna (`/auth/google/link`) |
| **P14** | **[RESERVED] Tenaga Kerja / Karyawan PT Anak Usaha**<br>Dicadangkan untuk persona karyawan masa depan KBU-001 (Bukan Anggota Koperasi) | `Employee` (Reserved) | `KBU-001` | Tidak | Tidak | **Tidak (0 Member)** | Tidak | N/A | Tidak | Tidak | Tidak | ❌ Dilarang | Dicadangkan untuk pengujian modul ERP HRM/Payroll anak usaha masa depan (Di luar SEED-03) |
| **P15** | **[RESERVED] Admin / HR Operasional PT Anak Usaha**<br>Dicadangkan untuk staf administratif masa depan KBU-001 | `Admin` (Reserved) | `KBU-001` | Tidak | Tidak | **Tidak (0 Member)** | Tidak | N/A | Tidak | Tidak | Tidak | ❌ Dilarang | Dicadangkan untuk pengujian otorisasi operasional internal anak usaha masa depan (Di luar SEED-03) |

---

## 8. Konvensi Penamaan & Identitas Deterministik (Deterministic Identity Convention)

Untuk mencegah tabrakan data (*collision*), memudahkan penelusuran (*searchability*), dan memastikan tidak ada data riil yang bocor, Phase 3 menetapkan konvensi penamaan sintetik terstandarisasi:

### 8.1. Konvensi Identitas Persona

| Komponen Identitas | Pola / Aturan Format | Contoh Sintetis | Catatan Keamanan |
| :--- | :--- | :--- | :--- |
| **Email Pengguna (`users.email`)** | `seed.<role-alias>@kojaya.test` | `seed.admin.kop@kojaya.test`<br>`seed.member.active@kojaya.test` | Domain khusus RFC 2606 `.test` menjamin email tidak pernah terkirim ke internet. |
| **Nomor Anggota (`no_anggota` / `member_no`)** | `DEV-KOP-###` (Hanya untuk KOP-001) | `DEV-KOP-001`<br>`DEV-KOP-010` | Jelas berlabel `DEV`, terurut, dan terikat kode organisasi KOP-001. KBU-001 tidak memiliki namespace nomor anggota. |
| **Nomor Induk Kependudukan (NIK)** | `31749900000000##` (16 digit) | `3174990000000001` s.d. `3174990000000099` | Menggunakan kode wilayah fiktif `317499` yang tidak ada di Dukcapil. |
| **Nomor Telepon** | `0812999900##` | `081299990001` s.d. `081299990099` | Jelas nomor telepon dummy. |
| **Nomor Pokok Wajib Pajak (NPWP)** | `99.999.999.9-999.0##` | `99.999.999.9-999.001` | Format sintetik terisolasi. |
| **Kode Karyawan (`employee_code`)** | `DEV-EMP-###` | `DEV-EMP-001` | Jelas karyawan sintetis. |
| **Google SSO Sub / Provider ID** | `google-seed-sub-###` | `google-seed-sub-001` | String deterministik untuk mock token OAuth. |
| **Rekening Bank Anggota** | `9900000000##` (Bank: `BANK-DEMO`) | `990000000001` | Mencegah referensi bank nyata. |
| **Nomor Referensi Tagihan Iuran** | `INV-SEED-YYYYMM-###` | `INV-SEED-202606-001` | Memuat periode tahun-bulan dan nomor urut. |
| **Nomor Referensi Pembayaran** | `PAY-SEED-YYYYMM-###` | `PAY-SEED-202606-001` | Memuat periode dan nomor urut. |
| **Nomor Referensi Transaksi POS** | `POS-SEED-YYYYMMDD-###` | `POS-SEED-20260601-001` | Jelas transaksi POS sintetis. |
| **Nomor Kontrak Pinjaman** | `LOAN-SEED-YYYY-###` | `LOAN-SEED-2026-001` | Memuat tahun dan nomor urut pinjaman. |

---

## 9. Kebijakan Kredensial Pengujian (Credential Policy)

1. **Sandi Statis di Lingkungan Non-Produksi:**
   - Seluruh persona dev deterministik pada SEED-03 akan menggunakan sandi default:
     ```text
     password
     ```
   - Sandi ini hanya berlaku di lingkungan `local`, `testing`, dan `playwright`.
2. **Larangan Kredensial di Seeder Referensi Produksi:**
   - Seeder dengan kategori `PRODUCTION_SAFE_REFERENCE` **TIDAK BOLEH** membuat akun pengguna atau menyimpan sandi teks maupun hash.
   - Pelanggaran terhadap aturan ini ditegakkan oleh static analysis `SeederSafetyStaticAnalysisTest::test_reference_seeders_do_not_create_users_or_passwords()`.
3. **Kepemilikan Seeder Persona:**
   - Pembuatan akun pengguna berpassword statis dimiliki secara eksklusif oleh **SEED-03 (User & Member Persona Seeder)**.
   - SEED-03 wajib memiliki environment guard fail-closed yang membatalkan eksekusi jika dijalankan di luar lingkungan yang diizinkan.

---

## 10. Matriks Hak Akses & Peran Koperasi (Role & Permission Authority)

Merujuk pada [database/seeders/RolePermissionSeeder.php](../../database/seeders/RolePermissionSeeder.php#L201-L330), berikut adalah pemetaan kewenangan operasional untuk peran koperasi:

```text
Pengurus Koperasi ───▶ Validasi & Approval Akhir Anggota, Approval Pinjaman, Void POS, Pembagian SHU
        ▲
        │ (Maker-Checker Escalation)
Manajer Koperasi  ───▶ Review Awal Pinjaman, Monitoring Ledger, Review Pengunduran Diri
        ▲
        │
Admin Koperasi    ───▶ Import Anggota (Batch CSV), Verifikasi Berkas Anggota, Setup POS, Tagihan Iuran
        │
Kasir Koperasi    ───▶ Transaksi Penjualan Kasir POS, Pembayaran Tagihan, Store Credit Kasir
        │
Anggota           ───▶ Self-service Portal Anggota (Simpanan, Pengajuan Pinjaman, Cek Poin, Belanja)
```

### 10.1. Rincian Otoritas Fungsional

| Peran Koperasi | Izin Kunci Terkait Pendaftaran Anggota | Izin Kunci Terkait Pinjaman & Keuangan | Izin Kunci Terkait POS & Toko | Tanggung Jawab Pengujian Phase 4 |
| :--- | :--- | :--- | :--- | :--- |
| **Pengurus Koperasi** | `validate_cooperative_member`<br>`approve_cooperative_member`<br>`view_cooperative_member_pii` | `approve_cooperative_loan`<br>`manage_cooperative_shu`<br>`approve_cooperative_opening_balance` | `approve_pos_void`<br>`approve_store_credit_transfer` | Otoritas tertinggi koperasi. Menguji approval final calon anggota dan persetujuan pencairan pinjaman. |
| **Manajer Koperasi** | `view_cooperative_member` (Tanpa izin approval anggota) | `review_cooperative_loan` (Tanpa izin approval akhir pinjaman) | `approve_pos_void`<br>`manage_store_credit_limit` | Lapisan review pertama (*checker*) sebelum diteruskan ke Pengurus. |
| **Admin Koperasi** | `import_cooperative_member_batch`<br>`validate_cooperative_member`<br>`verify_cooperative_member` | `manage_cooperative_dues`<br>`manage_cooperative_payment` | `manage_pos_categories`<br>`manage_pos_products` | Administrator harian. Menguji validasi dan verifikasi dokumen calon anggota serta penerbitan tagihan iuran. |
| **Kasir Koperasi** | `view_cooperative_member` | `manage_cooperative_payment` | `access_cooperative_pos`<br>`cashier_store_credit` | Kasir fisik. Menguji alur belanja POS menggunakan saldo kredit toko anggota. |
| **Anggota** | `member_portal_access` | Hak akses dibatasi strictly oleh ownership model (`cooperativeMember`) | Transaksi belanja mandiri | Pengguna akhir aplikasi Kojayaku Mobile dan portal web anggota. |

---

## 11. Dataset Organisasi & Batasan Multi-Tenant (Tenant Isolation)

Untuk menguji isolasi organisasi (*multi-tenancy scoping*) secara ketat, disyaratkan minimal 3 organisasi:

| Kode Organisasi | Nama Organisasi | Level | Tipe | Induk (Parent) | Tujuan Pengujian Phase 4 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`KOP-001`** | **Koperasi Jaya Bersama** | `L0` | `HEAD_OFFICE` | `null` | **Entitas Legal Koperasi (Tenant Utama).** Satu-satunya organisasi yang berhak memiliki `CooperativeMember`. Menampung persona baseline valid (P01–P10, P12, P13), persona edge-case P11, dan seluruh transaksi finansial anggota. |
| **`KBU-001`** | **PT Koperasi Berkah Usaha** | `L1` | `BRANCH` | `KOP-001` | **PT Anak Usaha (Subsidiary Company).** Entitas komersial di bawah kepemilikan/kendali koperasi untuk operasi bisnis. Menampung relasi tenaga kerja (`Employee`), aset, dan payroll. **DILARANG MEMILIKI ANGGOTA KOPERASI (`CooperativeMember` count = 0).** P14 dan P15 di-reserve untuk pengujian tenaga kerja anak usaha masa depan. |
| **`ISO-999`** | **Koperasi Mandiri Sejahtera** | `L0` | `HEAD_OFFICE` | `null` | **Entitas Pihak Ketiga Terisolasi (Third-party Synthetic).** Membuktikan isolasi otorisasi multi-tenant antar badan hukum independen (HTTP 403 / ModelNotFoundException). Tidak menerima persona anggota default pada SEED-03. |

---

## 12. Matriks Data Master / Referensi Produksi (Master Reference Data)

Master data berikut dihasilkan oleh seeder berstatus `PRODUCTION_SAFE_REFERENCE`:

| Nama Komponen Referensi | Seeder Pemilik | Status di Phase 3 Koperasi | Keterangan & Dampak pada `migrate:fresh --seed` |
| :--- | :--- | :--- | :--- |
| **Peran & Hak Akses (Roles & Permissions)** | `RolePermissionSeeder` | **REQUIRED** | Wajib ada; menjadi fondasi seluruh otorisasi sistem. |
| **Organisasi Utama (`KOP-001`)** | `CooperativeReferenceSeeder` | **REQUIRED** | Wajib ada; anchor organisasi koperasi default. |
| **Jenis Iuran / Simpanan (POKOK, WAJIB, SUKARELA, KHUSUS)** | `CooperativeReferenceSeeder` | **REQUIRED** | Wajib ada; tarif simpanan pokok (200.000) dan wajib (100.000). |
| **Kategori POS (Sembako, Minuman, ATK, Kopi)** | `CooperativeReferenceSeeder` | **REQUIRED** | Wajib ada; kategori dasar untuk penempatan produk POS. |
| **Produk Pinjaman (Darurat, Produktif, Konsumtif)** | `LoanTypeSeeder` | **REQUIRED** | Wajib ada; parameter plafon, tenor, dan suku bunga pinjaman. |
| **Tarif Pajak PPh 21 TER 2024** | `TaxRuleSeeder` | **OPTIONAL** | Modul ERP; tidak memblokir uji alur koperasi jika diabaikan, namun tetap di-seed. |
| **Grade Jabatan HR (`PELAKSANA` s.d. `DIREKSI`)** | `JobGradeSeeder` | **OPTIONAL** | Modul ERP HRM; pendukung data karyawan koperasi. |
| **Jenis Cuti Karyawan** | `LeaveTypeSeeder` | **OPTIONAL** | Modul ERP HRM. |
| **Komponen Gaji Karyawan** | `SalaryComponentTypeSeeder` | **OPTIONAL** | Modul ERP Payroll. |
| **Jadwal Kerja (Shift & Non-Shift)** | `WorkShiftSeeder` | **OPTIONAL** | Modul ERP Absensi. |

---

## 13. Kontrak Dataset Finansial Sintetis (Financial Test Data Contract)

Sebagai dasar untuk **SEED-05 (Synthetic Financial Data)**, dataset finansial harus mencakup 5 bentuk status pengujian (*test shapes*):
1. **Empty State:** Anggota baru aktif yang belum memiliki riwayat transaksi atau saldo.
2. **Normal / Happy-Path State:** Anggota dengan riwayat simpanan lancar, saldo kredit toko positif, transaksi POS rutin.
3. **Partial / Unpaid State:** Anggota dengan tagihan iuran berstatus `UNPAID` atau `PARTIAL`, atau angsuran pinjaman yang mendekati jatuh tempo.
4. **Completed / Paid State:** Anggota dengan pinjaman yang sudah lunas (`CLOSED`), tagihan iuran terbayar penuh (`PAID`), dan penerbitan kuitansi (`CooperativeReceipt`).
5. **Boundary / Edge State:** Saldo kredit toko mendekati limit plafon, saldo nol, atau upaya penarikan simpanan melebihi batas yang diizinkan.

### 13.1. Spesifikasi Entitas Finansial

```text
┌─────────────────────────┐       ┌─────────────────────────┐       ┌─────────────────────────┐
│     Iuran / Simpanan    │       │       Kredit Toko       │       │         Pinjaman        │
│   (CooperativeDues)     │       │   (MemberStoreAccount)  │       │          (Loan)         │
├─────────────────────────┤       ├─────────────────────────┤       ├─────────────────────────┤
│ • Simpanan Pokok        │       │ • Plafon Kredit: 500rb  │       │ • Pinjaman Produktif    │
│ • Simpanan Wajib        │       │ • Saldo Normal: 150rb   │       │ • Status: Active        │
│ • Simpanan Sukarela     │       │ • Saldo Nol: 0          │       │ • Angsuran Berjalan     │
│ • Tagihan & Kuitansi    │       │ • Mutasi Kredit/Debit   │       │ • Angsuran Terbayar     │
└─────────────────────────┘       └─────────────────────────┘       └─────────────────────────┘
```

---

## 14. Kebijakan Tanggal Transaksi (Transaction Date Policy)

### 14.1. Masalah pada Seeder Saat Ini
Pada `CooperativeSeeder` dan `AnggotaSeeder` yang ada sekarang:
- Pembuatan tagihan iuran wajib dihitung secara dinamis dari tanggal aktif anggota hingga bulan saat ini:
  ```php
  $start = Carbon::parse($member->tanggal_aktif)->startOfMonth();
  $end = Carbon::now()->startOfMonth();
  ```
- **Dampak Buruk:** Jika seeder dijalankan pada bulan Juni 2026, jumlah invoice yang terbentuk berbeda dengan saat dijalankan pada September 2026. Hal ini menyebabkan pengujian otomatis (assertion jumlah baris, total akumulasi saldo iuran) menjadi rapuh (*flaky*).

### 14.2. Kebijakan Tanggal Phase 3
Phase 3 menetapkan kebijakan penanggalan terstruktur:
1. **Anchor Date Kanonikal:** Ditetapkan anchor date statis:
   ```text
   2026-06-01 (1 Juni 2026)
   ```
2. **FIXED-DATE (Kategori Statis):**
   - Digunakan untuk:
     - Tanggal bergabung dan aktif anggota baseline (`2025-01-01` s.d. `2026-01-01`).
     - Transaksi historis POS tahun 2025 dan awal 2026.
     - Penutupan periode SHU tahun 2025 (`CooperativeShuPeriodStatus::Closed`).
     - Saldo awal (*opening balance*) buku besar koperasi.
3. **RELATIVE-DATE (Kategori Terkendali):**
   - Digunakan untuk:
     - Pengujian jatuh tempo tagihan atau invoice aktif.
     - Wajib menggunakan `Carbon::setTestNow()` di dalam runner pengujian agar pergeseran waktu kalender riil tidak merusak hasil uji.

---

## 15. Kontrak Dataset Kasus Negatif & Edge-Case (Negative Dataset Contract)

Untuk kebutuhan **SEED-06 (Negative & Edge-Case Dataset)**, kasus-kasus batas wajib disiapkan tanpa merusak (*corrupting*) dataset normal (*happy path*):

| Kategori Kasus Negatif | Skenario Uji yang Diwakili | Mekanisme Penyimpanan yang Ditetapkan | Ekspektasi Sistem |
| :--- | :--- | :--- | :--- |
| **Duplikasi Nomor Anggota** | Upaya import atau pendaftaran dengan `no_anggota` yang sudah terdaftar | Dedicated Test / Optional Seeder | Validasi form gagal / `ValidationException` (422) |
| **Duplikasi NIK (Identity Number)** | Calon anggota mencoba mendaftar dengan NIK yang sudah ada di database | Dedicated Test / Optional Seeder | Import preview menandai baris invalid / form menolak NIK ganda |
| **Duplikasi Email Pengguna** | Pendaftaran anggota baru dengan email yang sudah terikat pada User lain | Dedicated Test / Optional Seeder | Ditolak oleh registrasi / matching SSO mendeteksi konflik |
| **Konflik Akun Google SSO** | Akun Google dengan sub tertentu mencoba ditautkan ke member yang sudah memiliki provider lain | Dedicated Test (Unit/Feature) | `MemberGoogleSsoMatchingService` fail-closed |
| **Siklus Hidup Mismatch (`BLOCKED_UNKNOWN`)** | Data anggota dengan kombinasi status tidak sah (misal `ACTIVE` & `PENDING` atau `RESIGNED`) | Persona `P11` (Didefinisikan di SEED-04, dimuat opsional di SEED-06) | Middleware memblokir dengan **HTTP 403 Forbidden** fail-closed |
| **Akses Fitur Aktif oleh Member Belum Aktif** | Anggota `WAITING_VERIFICATION` mencoba memanggil API pengajuan pinjaman | Feature Test via Persona `P06` | Middleware `member.api.active` mengembalikan 403 `MEMBER_NOT_ACTIVE` |
| **Pelanggaran Batas Tenant (Cross-Org)** | Pengguna organisasi satu mencoba mengakses/menyetujui resource milik badan usaha lain (misal KOP-001 vs entitas independen ISO-999) | Feature Test Otorisasi Multi-Tenant | `AuthorizationException` (HTTP 403) / ModelNotFoundException |
| **Transaksi Melebihi Plafon Kredit Toko** | Belanja POS melebihi sisa limit kredit toko anggota | Dedicated Feature Test | Penolakan transaksi oleh `MemberStoreAccountService` |

---

## 16. Pemisahan Baseline DEV Dataset vs Optional Edge Dataset

Untuk memastikan stabilitas lingkungan pengujian:

### A. Baseline DEV Dataset (Default Reseed)
- Dimuat secara otomatis saat developer/tester menjalankan tooling reset dev standar.
- Berisi:
  - Seluruh master referensi produksi (`RolePermissionSeeder`, `CooperativeReferenceSeeder`, `LoanTypeSeeder`, dll.).
  - 3 Organisasi (`KOP-001`, `KBU-001`, `ISO-999`).
  - 12 Persona baseline valid (`P01` s.d. `P10`, `P12`, `P13`) yang seluruhnya terikat ke `KOP-001`.
  - Persona `P14` dan `P15` berstatus **RESERVED** (tidak dimuat di SEED-03).
  - Organisasi `KBU-001` memiliki **0 `CooperativeMember`**.
  - Transaksi bisnis normal yang valid (iuran lunas/berjalan, katalog POS aktif, saldo kredit toko normal, pinjaman berjalan).
- **Pengecualian Tegas:** Persona `P11 (BLOCKED_UNKNOWN)` **dikecualikan** dari baseline DEV dataset karena merepresentasikan data siklus hidup tidak sah / korup yang disengaja.
- **Karakteristik:** Bersih dari data korup, siap digunakan untuk demo fungsional dan pengujian end-to-end happy-path.

### B. Optional Edge Dataset (On-Demand / Testing Only)
- Hanya dimuat saat parameter atau flag khusus diberikan (misal: `--with-edge-cases`) atau di dalam runner pengujian terisolasi.
- Berisi:
  - Persona `P11 (BLOCKED_UNKNOWN)` untuk memverifikasi proteksi fail-closed HTTP 403.
  - Baris CSV import yang sengaja dibuat cacat (email salah format, NIK duplikat, kolom kosong).
  - Rekening toko dengan status `Suspended` atau saldo minus melewati batas.
  - Pinjaman menunggak / *defaulted*.
- **Karakteristik:** Terisolasi ketat agar tidak mengotori alur pengujian normal.

---

## 17. Klasifikasi Keamanan Data (Production Safety Classification)

Phase 3 mengelompokkan seluruh data ke dalam 3 tier keamanan:

```text
┌────────────────────────────────────────────────────────────────────────────┐
│ 1. PRODUCTION_SAFE_REFERENCE                                               │
│    • Aman dijalankan di production saat deployment.                        │
│    • ZERO user credentials, ZERO fake members, ZERO fake transactions.     │
├────────────────────────────────────────────────────────────────────────────┤
│ 2. NON_PRODUCTION_DETERMINISTIC_FIXTURE                                    │
│    • Hanya untuk local, testing, dan playwright.                           │
│    • Memuat 12 persona valid P01-P10 & P12-P13, sandi 'password', data POS.│
│    • P14 & P15 reserved untuk tenaga kerja PT anak usaha masa depan.       │
├────────────────────────────────────────────────────────────────────────────┤
│ 3. TEST_ONLY_INVALID_FIXTURE                                               │
│    • Hanya untuk automated test suites spesifik / isolated sandbox.        │
│    • Memuat persona P11 (BLOCKED_UNKNOWN), anomali, duplikasi, data korup. │
└────────────────────────────────────────────────────────────────────────────┘
```

### 17.1. Tabel Klasifikasi Entitas

| Klasifikasi | Komponen / Entitas yang Masuk | Lingkungan Diizinkan | Aturan Penegakan |
| :--- | :--- | :--- | :--- |
| **`PRODUCTION_SAFE_REFERENCE`** | Roles, Permissions, Organization `KOP-001`, Contribution Types, Pos Categories, Loan Types, Tax Rules, Job Grades, Leave Types, Salary Component Types, Work Shifts | `production`, `staging`, `local`, `testing`, `playwright` | Wajib idempoten via `firstOrCreate`; tidak boleh ada `Hash::make` atau user. |
| **`NON_PRODUCTION_DETERMINISTIC_FIXTURE`** | 12 Persona baseline valid (`P01`–`P10`, `P12`, `P13`), Akun login Fortify, SocialAccount mock, Produk POS demo, Transaksi POS historis, Iuran dan kuitansi demo, Rekening toko demo (P14/P15 reserved) | `local`, `testing`, `playwright` | Dilindungi oleh guard `LogicException` jika `app.env` di luar whitelist. KBU-001 zero members. |
| **`TEST_ONLY_INVALID_FIXTURE`** | Persona `P11 (BLOCKED_UNKNOWN)`, File CSV malformed, duplikasi NIK/email, data anggota korup, data transaksi over-limit | `testing` (In-memory / isolated test runner / optional flag) | Dikecualikan dari default reseed dev; tidak boleh masuk ke default `DatabaseSeeder::run()`. |

---

## 18. Matriks Kepemilikan Tugas Phase 3 (Future Seeder Ownership Matrix)

Setiap kategori dataset dan infrastruktur data uji diberikan satu pemilik tugas utama (*primary owner*) yang jelas pada Phase 3:

| Kode Tugas | Nama Tugas Phase 3 | Kategori Dataset & Komponen yang Dimiliki | Deliverable Utama | Dependensi |
| :--- | :--- | :--- | :--- | :--- |
| **SEED-01** | **Test Data Contract & Dataset Matrix** | Kontrak spesifikasi, matriks persona, kebijakan identitas & kredensial, gap analysis | Dokumen `docs/phase-3/SEED-01-test-data-contract.md` | PR #64 (ONB-09) |
| **SEED-02** | **Master / Reference Data Seeder** | Standarisasi seeder referensi produksi, organisasi kanonikal, penyempurnaan `CooperativeReferenceSeeder` | `CooperativeReferenceSeeder.php` yang terstandarisasi dan teruji | SEED-01 |
| **SEED-03** | **User & Member Persona Seeder** | Pembuatan 12 persona valid sintetis deterministik (`P01`–`P10`, `P12`, `P13`), akun User Fortify, relasi role, kredensial dev (P14 & P15 reserved) | `CooperativePersonaSeeder.php` | SEED-01, SEED-02 |
| **SEED-04** | **Member Lifecycle Dataset** | Dataset siklus hidup anggota valid (`WAITING_VERIFICATION`, `UNDER_REVIEW`, `REVISION_REQUIRED`, `REJECTED`, `ACTIVE`) serta kapabilitas state factory `blockedUnknown()` | State factory baru & fixture anggota per siklus hidup | SEED-01, SEED-03 |
| **SEED-05** | **Synthetic Transaction / Financial Test Data** | Dataset simpanan, tagihan iuran, kuitansi, mutasi buku besar, kredit toko, pinjaman, dan transaksi POS | `CooperativeFinancialFixtureSeeder.php` | SEED-01, SEED-04 |
| **SEED-06** | **Negative & Edge-Case Dataset** | Pemuatan opsional fixture persona `P11 (BLOCKED_UNKNOWN)`, kasus batas, duplikasi NIK, over-limit, data CSV import cacat | `CooperativeEdgeCaseFixtureSeeder.php` / test factories | SEED-01, SEED-04, SEED-05 |
| **SEED-07** | **Deterministic Reset / Reseed Tooling** | Command Artisan reset/reseed deterministik terorkestrasi untuk developer dan QA | Command `php artisan cooperative:reset-test-data` | SEED-02 s.d. SEED-06 |
| **SEED-08** | **Environment Safety Guard** | Pengerasan proteksi lingkungan, pencegahan eksekusi seeder dev di production/staging | Middleware / Seeder Guard Service | SEED-01, SEED-07 |
| **SEED-09** | **Seed Integrity & Readiness Gate** | Suite verifikasi integritas, assertion konsistensi saldo, kesiapan data untuk Phase 4 | Suite pengujian `SeedIntegrityGateTest.php` | Seluruh SEED-01 s.d. 08 |

---

## 19. Analisis Kesenjangan Mendalam (Deep Gap Analysis)

Berikut adalah kesenjangan konkret yang ditemukan antara kebutuhan pengujian Phase 4 dengan implementasi repositori saat ini:

| No | Kebutuhan Data Uji | Implementasi Saat Ini di Repositori | Kesenjangan (Gap) Konkret | Tingkat Keparahan (Severity) | Pemilik Tugas Penyelesaian |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **G-01** | Persona anggota per siklus hidup pendaftaran | `CooperativeSeeder` dan `AnggotaSeeder` hanya membuat anggota berstatus `ACTIVE / ACTIVE` | Tidak ada fixture persistent dev untuk calon anggota `WAITING_VERIFICATION`, `UNDER_REVIEW`, `REVISION_REQUIRED`, dan `REJECTED`. | **CRITICAL** | **SEED-04** |
| **G-02** | Akun User untuk login anggota (Web & Mobile) | `AnggotaSeeder` membuat 10 member tanpa relasi `User`. `CooperativeSeeder` membuat 10 member tanpa relasi `User`. | Anggota demo di dev environment tidak bisa login ke portal web anggota (`/member`) maupun aplikasi mobile Kojayaku. | **CRITICAL** | **SEED-03** |
| **G-03** | Akun persona Pengurus & Manajer Koperasi yang konsisten | `CooperativeSeeder` mencari user `admin@erp.com` yang tidak di-create oleh seeder tersebut. `CooperativeManagerRoleSeeder` terpisah dan tidak dipanggil di `DatabaseSeeder`. | Alur approval pinjaman dan pendaftaran anggota tidak memiliki akun login pengurus & manajer yang terhubung langsung pada default dev seed. | **HIGH** | **SEED-03** |
| **G-04** | Ketergantungan tanggal dinamis pada tagihan iuran | `CooperativeSeeder` dan `AnggotaSeeder` mengkalkulasi periode tagihan bulanan dari tanggal aktif hingga `Carbon::now()`. | Jumlah invoice dan total mutasi kas berubah setiap bulan, merusak stabilitas assertion tes otomatis. | **HIGH** | **SEED-05** |
| **G-05** | Fixture pinjaman dan angsuran koperasi | `LoanTypeSeeder` tersedia, namun data pinjaman berjalan hanya ada di `UiAuditSeeder` (terisolasi untuk visual test). | Tidak ada fixture pinjaman aktif, angsuran berjalan, dan riwayat pelunasan untuk pengujian fungsional simpan pinjam di dev environment. | **HIGH** | **SEED-05** |
| **G-06** | Fixture rekening dan mutasi kredit toko (*member store credit*) | Hanya ada di `UiAuditSeeder` dengan kode `AUD-*`. `CooperativeSeeder` belum menginisialisasi `MemberStoreAccount`. | Transaksi POS dengan metode `MEMBER_CREDIT` di `CooperativeSeeder` tidak terhubung ke ledger rekening toko yang valid. | **HIGH** | **SEED-05** |
| **G-07** | Persona Google SSO terhubung deterministik | Ada di test fixture ONB-07/09, namun belum ada di persistent dev seed. | QA/developer tidak memiliki akun uji siap pakai yang terikat ke Google mock provider di dev environment. | **MEDIUM** | **SEED-03** |
| **G-08** | Named state factory untuk kombinasi `BLOCKED_UNKNOWN` | `CooperativeMemberFactory` memiliki state `active`, `pendingReview`, `pending`, `revision`, `rejected`, namun belum ada `blockedUnknown()`. | Developer pengujian unit/feature harus merakit atribut status korup secara manual. | **LOW** | **SEED-04** |
| **G-09** | Command orkestrasi reseed data koperasi terpadu | Reset data dev saat ini memerlukan kombinasi command manual yang berisiko menyentuh tabel ERP non-koperasi. | Belum ada satu perintah aman (`php artisan cooperative:...`) untuk mereset hanya data koperasi tanpa merusak tabel lain. | **MEDIUM** | **SEED-07** |

---

## 20. Kesimpulan & Kesiapan Menuju SEED-02

Dokumen **SEED-01: Test Data Contract & Dataset Matrix** telah menuntaskan seluruh pemetaan, klasifikasi, kebijakan keamanan, konvensi penamaan, dan matriks persona yang diperlukan bagi Phase 3. 

Seluruh tim pengembang dan AI agents kini memiliki acuan spesifikasi tunggal yang terikat kuat pada kode otoritatif repositori, memungkinkan pengerjaan tugas **SEED-02 (Master / Reference Data Seeder)** dimulai secara mulus tanpa asumsi yang ambigu.
