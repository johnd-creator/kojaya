# Kojaya Member Data Contract — Canonical Onboarding Specification (ONB-01 FROZEN)

Dokumen ini adalah **kontrak data anggota kanonikal (ONB-01 FROZEN)** untuk seluruh ekosistem **Kojaya** (`johnd-creator/kojaya`), yang berfungsi sebagai **Single Source of Truth** bagi seluruh alur Onboarding Anggota (Fase 2):

```text
Google Form (ONB-02)
        ↓
Admin Verification (ONB-03)
        ↓
Canonical Import Template CSV/Excel (ONB-01 Frozen)
        ↓
Backend Import Validator (ONB-04)
        ↓
Dry Run Simulation (ONB-05)
        ↓
DEV Member Import (ONB-06)
        ↓
Google SSO Matching (ONB-07)
        ↓
Member Login & First Activation (ONB-08)
        ↓
Production E2E Verification (ONB-09)
```

---

## 1. Inventarisasi Skema Anggota Saat Ini (Current Member Schema Inventory)

Pemeriksaan repositori membuktikan bahwa data anggota di Kojaya terdistribusi pada beberapa tabel terkait dengan entitas inti `cooperative_members`.

### 1.1 Tabel Utama dan Relasi

| Tabel | Model Eloquent | Peran dalam Domain Anggota |
| :--- | :--- | :--- |
| `cooperative_members` | [`CooperativeMember`](../../app/Models/CooperativeMember.php) | **Tabel utama profil keanggotaan koperasi**, menyimpan status, kategori keanggotaan, histori approval, data demografi, data rekening, limit kredit POS, dan metadata enkripsi PII. |
| `users` | [`User`](../../app/Models/User.php) | **Akun otentikasi login**, menyimpan email, password hash, role Spatie (`Anggota`), dan status verifikasi email. Terhubung 1-ke-1 secara unik (`cooperative_members.user_id` UNIQUE). |
| `social_accounts` | [`SocialAccount`](../../app/Models/SocialAccount.php) | **Identitas OAuth pihak ketiga (Google SSO)**, menyimpan `provider`, `provider_id` (Google Subject ID / `sub`), `provider_email`, token, dan jejak waktu login. Terhubung ke `users.id`. |
| `employees` | [`Employee`](../../app/Models/Employee.php) | **Data kepegawaian perusahaan induk**, menyimpan `employee_code` (NIP), departemen, jabatan, dan shift. Terhubung opsional ke `cooperative_members.employee_id`. |
| `organizations` | [`Organization`](../../app/Models/Organization.php) | **Organisasi/cabang koperasi**, menjaga multi-tenant / organization isolation. Setiap anggota wajib terikat ke `organization_id` (default Head Office `KOP-001`). |
| `member_onboarding_progress` | `MemberOnboardingProgress` | **Pelacak checklist onboarding di portal anggota**, mencatat milestone pengisian profil, KYC, simpanan pertama, dsb. |
| `cooperative_member_documents` | `CooperativeMemberDocument` | **Lampiran dokumen fisik/digital** (foto KTP, KK, formulir bertanda tangan). |
| `member_store_accounts` | `MemberStoreAccount` | **Akun kredit toko/POS anggota**, mencatat limit belanja dan saldo tertunggak. |

---

### 1.2 Inventarisasi Lengkap Kolom `cooperative_members`

Berikut adalah inventarisasi seluruh 63 kolom pada tabel `cooperative_members` berdasarkan migrasi database aktif (`2026_03_07_000001` s/d `2026_07_22_005558`) dan runtime PostgreSQL/SQLite:

| DB Column | DB Type | Nullable | Default | Unique / Index | Model Cast / Accessor | Validasi Eksisting | Tujuan & Penggunaan Saat Ini | Tampil di UI/API | Sensitif (PII)? |
| :--- | :--- | :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| `id` | `bigint/int` | NO | autoincrement | PK | `int` | - | Primary Key internal anggota | UI/API | NO |
| `organization_id` | `uuid/varchar` | NO | - | FK `organizations(id)` | `string` | prohibited di store form | Isolasi tenant/koperasi cabang | API | NO |
| `employee_id` | `bigint/int` | YES | `null` | FK `employees(id)` | `int` | `nullable, exists:employees,id` | Menghubungkan anggota ke modul HR/Pegawai | Form/Detail | NO |
| `user_id` | `bigint/int` | YES | `null` | UNIQUE, FK `users(id)` | `int` | prohibited di store form | Menghubungkan profil anggota ke akun login web/app | Internal | NO |
| `member_no` | `varchar(40)` | NO | - | UNIQUE | `string` (synced) | prohibited di store form | Nomor anggota legacy / mirror dari `no_anggota` | UI/API | NO |
| `no_anggota` | `varchar(20)` | YES | `null` | UNIQUE | `string` (synced) | `nullable, string, max:20, unique` | **Nomor anggota kanonikal resmi** (`KOP-XXX`) | UI/API/Card | NO |
| `name` | `varchar(255)` | NO | - | - | `string` | `required, string, max:255` | Nama lengkap anggota (synced dari `nama_anggota`) | UI/API | NO |
| `nama_anggota` | `varchar(100)` | YES | `null` | - | `string` | `required, string, max:100` | Nama tampilan anggota (dapat berakhiran `*` jika ALB) | Table/Export | NO |
| `email` | `varchar(255)` | YES | `null` | - (Index via users) | `string` | `nullable, email, max:255` | **Email kontak & bootstrap candidate key Google SSO** | UI/API | YES |
| `phone` | `varchar(40)` | YES | `null` | - | `string` | `nullable, string, max:40` | Nomor telepon (synced dari `no_telp`) | UI/API | YES |
| `no_telp` | `varchar(20)` | YES | `null` | - | `string` | `nullable, string, max:20` | Nomor HP/WhatsApp operasional | Table/Export | YES |
| `identity_number` | `varchar(40)` | YES | `null` | Index legacy | Virtual getter/setter (PII) | `string, max:64, unique` (onboarding) | **NIK / Nomor KTP anggota** (disimpan terenkripsi) | Masked | **HIGH PII** |
| `identity_number_enc` | `text` | YES | `null` | - | Raw encrypted text | - | Nilai NIK terenkripsi AES-256-CBC | NO | **HIGH PII** |
| `identity_number_bidx`| `char(64)` | YES | `null` | INDEX | Hash HMAC-SHA256 | - | Blind index NIK untuk exact matching & pencarian | NO | SENSITIVE |
| `identity_number_key_version` | `varchar(16)` | YES | `null` | - | `string` | - | Versi kunci enkripsi PII | NO | NO |
| `identity_number_bidx_version` | `varchar(16)` | YES | `null` | - | `string` | - | Versi kunci blind index PII | NO | NO |
| `identity_number_migrated_at` | `datetime` | YES | `null` | - | `datetime` | - | Jejak waktu migrasi enkripsi | NO | NO |
| `address` | `text` | YES | `null` | - | `string` | `nullable, string, max:1000` | Alamat domisili lengkap | Detail/Onb | YES |
| `joined_at` | `date` | YES | `null` | - | `date` | prohibited di store form | Tanggal bergabung (synced dari `tanggal_aktif`) | Detail/API | NO |
| `tanggal_aktif` | `date` | YES | `null` | - | `date` | `required, date` | **Tanggal resmi keaktifan anggota** | UI/Export | NO |
| `resigned_at` | `date` | YES | `null` | - | `date` | prohibited di store form | Tanggal pengunduran diri / keluar koperasi | Detail | NO |
| `status` | `varchar(30)` | NO | `'PENDING'` | INDEX (`org, status`) | `string` | `in:PENDING,ACTIVE,INACTIVE,RESIGNED` | Status operasional anggota koperasi | Badge/Filter | NO |
| `validation_status` | `varchar(32)` | NO | `'PENDING'` | INDEX | `string` | `in:PENDING,PENDING_VALIDATION,ACTIVE...`| **Status verifikasi onboarding berjenjang** | Badge/Filter | NO |
| `validated_at` | `datetime` | YES | `null` | - | `datetime` | prohibited di store form | Waktu persetujuan final Pengurus Koperasi | Detail | NO |
| `validated_by` | `bigint/int` | YES | `null` | FK `users(id)` | `int` | prohibited di store form | User Pengurus yang melakukan approval final | Detail | NO |
| `validation_notes` | `text` | YES | `null` | - | `string` | prohibited di store form | Catatan persetujuan / penolakan Pengurus | Detail | NO |
| `admin_validated_at`| `datetime` | YES | `null` | - | `datetime` | prohibited di store form | Waktu verifikasi awal oleh Admin Koperasi | Detail | NO |
| `admin_validated_by`| `bigint/int` | YES | `null` | FK `users(id)` | `int` | prohibited di store form | User Admin Koperasi yang memvalidasi data awal | Detail | NO |
| `admin_validation_notes` | `text` | YES | `null` | - | `string` | prohibited di store form | Catatan verifikasi awal Admin Koperasi | Detail | NO |
| `profile_completed_at` | `datetime` | YES | `null` | - | `datetime` | prohibited di store form | Timestamp saat data profil dinyatakan lengkap | Detail | NO |
| `onboarding_submitted_at` | `datetime` | YES | `null` | - | `datetime` | prohibited di store form | Timestamp saat anggota men-submit onboarding form | Detail | NO |
| `sso_provider` | `varchar(32)` | YES | `null` | - | `string` | - | Provider login terhubung (contoh: `'google'`) | Profile | NO |
| `last_sso_login_at` | `datetime` | YES | `null` | - | `datetime` | - | Waktu login Google SSO terakhir | Profile | NO |
| `npwp` | `varchar(30)` | YES | `null` | - | Virtual getter/setter (PII) | `nullable, string, max:32` | NPWP anggota (disimpan terenkripsi) | Masked | **HIGH PII** |
| `npwp_enc` | `text` | YES | `null` | - | Raw encrypted text | - | Nilai NPWP terenkripsi | NO | **HIGH PII** |
| `npwp_bidx` | `char(64)` | YES | `null` | INDEX | Hash HMAC-SHA256 | - | Blind index NPWP untuk pencarian | NO | SENSITIVE |
| `npwp_key_version` | `varchar(16)` | YES | `null` | - | `string` | - | Versi kunci enkripsi NPWP | NO | NO |
| `npwp_bidx_version` | `varchar(16)` | YES | `null` | - | `string` | - | Versi kunci blind index NPWP | NO | NO |
| `npwp_migrated_at` | `datetime` | YES | `null` | - | `datetime` | - | Jejak waktu migrasi enkripsi NPWP | NO | NO |
| `jenis_anggota` | `varchar(3)` | NO | `'AB'` | INDEX | `string` | `required, in:AB,ALB` | **Jenis keanggotaan**: `AB` (Biasa) / `ALB` (Luar Biasa) | Table/Export | NO |
| `jenis_kelamin` | `varchar(1)` | YES | `null` | - | `string` | `required, in:L,P` | **Jenis Kelamin**: `L` (Laki-laki) / `P` (Perempuan) | Detail/Export | NO |
| `kategori` | `varchar(3)` | YES | `null` | INDEX | `string` | `required, in:IP,CDB,KOP` | **Kode Perusahaan**: `IP` (Indonesia Power), `CDB` (Cogindo), `KOP` (Koperasi) | Table/Filter | NO |
| `autodebet` | `varchar(10)` | NO | `'MANUAL'` | - | `string` | `required, in:BNI,BRI,MANUAL` | Metode pemotongan iuran simpanan | Detail/Export | NO |
| `no_rekening` | `varchar(30)` | YES | `null` | - | Virtual getter/setter (PII) | `nullable, string, max:30` | Nomor rekening bank (terenkripsi) | Masked | **HIGH PII** |
| `no_rekening_enc` | `text` | YES | `null` | - | Raw encrypted text | - | Nilai nomor rekening terenkripsi | NO | **HIGH PII** |
| `no_rekening_bidx` | `char(64)` | YES | `null` | INDEX | Hash HMAC-SHA256 | - | Blind index nomor rekening | NO | SENSITIVE |
| `no_rekening_key_version` | `varchar(16)` | YES | `null` | - | `string` | - | Versi kunci enkripsi rekening | NO | NO |
| `no_rekening_bidx_version` | `varchar(16)` | YES | `null` | - | `string` | - | Versi kunci blind index rekening | NO | NO |
| `no_rekening_migrated_at` | `datetime` | YES | `null` | - | `datetime` | - | Jejak waktu migrasi enkripsi rekening | NO | NO |
| `nama_bank` | `varchar(60)` | YES | `null` | - | `string` | `nullable, string, max:100` | Nama bank pemilik rekening | Detail/Export | NO |
| `nama_pemilik_rekening` | `varchar(160)` | YES | `null` | - | `string` | `nullable, string, max:255` | Nama atas rekening bank | Detail/Export | NO |
| `tanggal_lahir` | `date` | YES | `null` | - | `date` | `nullable, date` | Tanggal lahir anggota | Detail/Onb | YES |
| `tempat_lahir` | `varchar(120)` | YES | `null` | - | `string` | `nullable, string, max:120` | Kota/tempat kelahiran anggota | Detail/Onb | NO |
| `pekerjaan` | `varchar(120)` | YES | `null` | - | `string` | `nullable, string, max:120` | Jabatan / pekerjaan anggota di perusahaan | Detail/Onb | NO |
| `perusahaan` | `varchar(160)` | YES | `null` | - | `string` | `nullable, string, max:160` | Kolom DB legacy (pada onboarding kanonikal, nama perusahaan adalah DERIVED_REFERENCE dari company_code, bukan teks input manual) | Detail | NO |
| `credit_limit` | `numeric(15,2)`| NO | `0.00` | - | `decimal:2` | `numeric, min:0` | Plafon kredit belanja POS anggota | POS/Member | NO |
| `outstanding_balance` | `numeric(15,2)`| NO | `0.00` | - | `decimal:2` | - | Saldo hutang kredit belanja POS saat ini | POS/Member | NO |
| `credit_term_days` | `smallint` | NO | `30` | - | `int` | `integer, min:1` | Tenor jatuh tempo tagihan kredit POS (hari) | POS | NO |
| `credit_tier` | `varchar(30)` | NO | `'REGULAR'` | - | `string` | `in:REGULAR,SILVER,GOLD,PLATINUM` | Kategori profil limit kredit | POS | NO |
| `notes` | `text` | YES | `null` | - | `string` | `nullable, string` | Catatan internal pengurus/admin | Internal | NO |
| `deleted_at` | `datetime` | YES | `null` | - | SoftDeletes | - | Waktu soft delete | - | NO |
| `created_at` | `datetime` | YES | `null` | - | `datetime` | - | Waktu pembuatan record | System | NO |
| `updated_at` | `datetime` | YES | `null` | - | `datetime` | - | Waktu pembaruan record terakhir | System | NO |

---

## 2. Klasifikasi Siklus Hidup Field (Field Lifecycle Classification)

Berdasarkan pembekuan kontrak kanonikal (ONB-01 Frozen), setiap field dalam domain anggota dibagi secara tegas ke dalam 6 kelompok fungsional tanpa tumpang-tindih:

```text
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        KLASIFIKASI FIELD DALAM SIKLUS ONBOARDING                       │
├─────────────────────────┬──────────────────────────────────────────────────────────────┤
│ KELOMPOK               │ DEFINISI & CAKUPAN FIELD                                     │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 1. CORE_ONBOARDING      │ 7 field esensial untuk mendirikan identitas dan verifikasi   │
│                         │ hukum anggota pada Fase 2:                                   │
│                         │ full_name, email, phone_number, identity_number, gender,     │
│                         │ company_code, address.                                       │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 2. ADMIN_ENRICHMENT     │ Field yang disediakan, disahkan, atau diverifikasi oleh staf │
│                         │ koperasi/sistem: member_number, membership_type, join_date,  │
│                         │ notes, serta input referensi pelamar: employee_number (NIP;  │
│                         │ Applicant-supplied reference input, admin/system-verified).  │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 3. SYSTEM_GENERATED     │ Field yang dikendalikan murni oleh sistem/aplikasi:          │
│                         │ organization_id, status, validation_status, role, timestamps.│
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 4. DERIVED_REFERENCE    │ Field turunan dari master referensi authoritative:           │
│                         │ - employee_id (tautan FK dari employee_number ter-resolve)   │
│                         │ - department (diturunkan dari employees.department_id)       │
│                         │ - company_name (diturunkan secara deterministik dari         │
│                         │   company_code).                                             │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 5. POST_ONBOARDING_     │ Data pelengkap profil yang diisi mandiri oleh anggota di     │
│    PROFILE              │ portal Kojayaku setelah aktif (DITUNDA DARI IMPOR AWAL F2):  │
│                         │ birth_place, birth_date, job_title.                          │
├─────────────────────────┼──────────────────────────────────────────────────────────────┤
│ 6. FINANCIAL_MIGRATION  │ Data perbankan & pajak yang dialihkan ke Fase 9              │
│    (DIKELUARKAN DARI F2)│ (npwp, bank_name, bank_account_number, bank_account_holder,  │
│                         │ autodebit_method).                                           │
└─────────────────────────┴──────────────────────────────────────────────────────────────┘
```

---

## 3. Resolusi Siklus Hidup Field Khusus (Special Fields Resolution)

### 3.1 Kebijakan Semantik `employee_number` (NIP)
- **Klasifikasi Kanonikal**: **`ADMIN_ENRICHMENT`** (Applicant-supplied reference input, admin/system-verified).
- **Sumber Nilai (Source)**: **`APPLICANT-SUPPLIED`** (Calon anggota memasukkan NIP saat pendaftaran karena mengetahui NIP sendiri; namun nilai ini **BUKAN** data otoritatif semata-mata karena diketik oleh pelamar dan **BUKAN** nilai yang di-generate oleh admin).
- **Verifikasi (Verification)**: **`ADMIN/SYSTEM VERIFIED AGAINST employees.employee_code`**.
- **Hasil Resolusi (Result)**:
  ```text
  employee_number (Applicant-supplied Reference Input)
        ↓
  Validasi & Verifikasi Sistem/Admin vs employees.employee_code
        ↓
  employees.id
        ↓
  cooperative_members.employee_id (Foreign Key)
  ```
- **Anggota Non-Karyawan (Mitra / Umum)**:
  `employee_number = null` dan `cooperative_members.employee_id = null`. Tidak ada teks bebas yang disimpan.
- **Unresolved Employee Reference**:
  Jika calon anggota memasukkan NIP tetapi tidak cocok / tidak ditemukan pada master `employees`:
  - Record ditandai sebagai `UNRESOLVED EMPLOYEE REFERENCE` dan masuk ke antrean peninjauan manual admin (**manual review**) pada alur verifikasi (ONB-03/ONB-04).
  - Nilai tidak dibuang diam-diam, namun **DILARANG KERAS disimpan di kolom `notes`** dan dilarang diserialisasi ke teks bebas apa pun.

### 3.2 Sumber Kebenaran Tunggal `company_name`
- **Klasifikasi Kanonikal Tunggal**: **`DERIVED_REFERENCE`** (Tepat satu klasifikasi siklus hidup; **BUKAN** `POST_ONBOARDING_PROFILE`).
- **Single Source of Truth**: Nama perusahaan diturunkan secara deterministik dari pemetaan master referensi `company_code`:
  ```text
  company_code (Canonical Company Reference Input)
        ↓
  Authoritative Mapping / Reference
        ↓
  company_name (DERIVED_REFERENCE)
  ```
  - `IP`  → `"PT Indonesia Power"`
  - `CDB` → `"PT Cogindo DayaBersama"`
  - `KOP` → `"Koperasi Kojaya"`
- **Konsekuensi Batasan**:
  - **TIDAK DIMINTA** pada Google Form pendaftaran pelamar (`company_name in Google Form: NO`).
  - **TIDAK DICANTUMKAN** pada template CSV impor inisial Fase 2 (`company_name in CSV: NO`).
  - **BUKAN INPUT PENGGUNA** (`user-entered company name: NO`) dan sistem tidak memelihara teks manual nama perusahaan pada profil.

### 3.3 Departemen (`department`)
- **Klasifikasi**: **`DERIVED_REFERENCE`** (jika terhubung ke pegawai) atau **`NOT CURRENTLY PERSISTED`** (jika non-karyawan).
- **Aturan**: Diturunkan secara runtime dari `employees.department_id → departments.name`. Dilarang keras disimpan di kolom `notes`. Jika di masa depan bisnis menghendaki departemen disimpan mandiri untuk seluruh anggota, statusnya adalah **`FUTURE SCHEMA DECISION REQUIRED`** (memerlukan migrasi skema database terpisah).

---

## 4. Google Form User-Supplied Payload (Boundary ONB-02)

Daftar pasti field yang dikumpulkan secara langsung dari pelamar/calon anggota melalui Google Form:

| # | Google Form Question | Canonical Field | Input Type | Required? | Lifecycle Classification |
| -: | :--- | :--- | :--- | :---: | :--- |
| 1 | Nama Lengkap (Sesuai KTP) | `full_name` | Short Text | **YA** | `CORE_ONBOARDING` |
| 2 | Alamat Email Google / Gmail Aktif | `email` | Short Text (Email) | **YA** | `CORE_ONBOARDING` |
| 3 | Nomor WhatsApp / Handphone Aktif | `phone_number` | Short Text | **YA** | `CORE_ONBOARDING` |
| 4 | Nomor Induk Kependudukan (NIK 16 Digit) | `identity_number` | Short Text (16 Digits) | **YA** | `CORE_ONBOARDING` |
| 5 | Jenis Kelamin | `gender` | Dropdown (`L` / `P`) | **YA** | `CORE_ONBOARDING` |
| 6 | Perusahaan / Unit Kerja | `company_code` | Dropdown (`IP` / `CDB` / `KOP`) | **YA** | `CORE_ONBOARDING` |
| 7 | Alamat Domisili Lengkap | `address` | Paragraph / Long Text | **YA** | `CORE_ONBOARDING` |
| 8 | Nomor Induk Pegawai (NIP) jika Karyawan | `employee_number` | Short Text | TIDAK | `ADMIN_ENRICHMENT` (Applicant-Supplied Ref Input) |

*Field admin (`member_number`, `membership_type`, `join_date`, `notes`), field turunan (`company_name`), dan data perbankan/pajak TIDAK DIMUNCULKAN pada Google Form pendaftaran pelamar.*

---

## 5. Phase-2 Initial Import Payload (Boundary CSV Template)

Daftar pasti **12 kolom kanonikal** yang menyusun template CSV impor inisial Fase 2 (`member-import-template.csv`):

| # | CSV Header | Canonical Field | Lifecycle Classification | Req? | Null? | Source / Penanggung Jawab | Target DB / Resolusi |
| -: | :--- | :--- | :--- | :---: | :---: | :--- | :--- |
| 1 | `member_number` | `member_number` | `ADMIN_ENRICHMENT` | NO | YES | Admin Koperasi (Auto-gen if empty) | `cooperative_members.no_anggota` & `member_no` |
| 2 | `full_name` | `full_name` | `CORE_ONBOARDING` | **YES** | NO | Pelamar (Google Form) | `cooperative_members.nama_anggota` & `name` |
| 3 | `email` | `email` | `CORE_ONBOARDING` | **YES** | NO | Pelamar (Google Form) | `users.email` & `cooperative_members.email` |
| 4 | `phone_number` | `phone_number` | `CORE_ONBOARDING` | **YES** | NO | Pelamar (Google Form) | `cooperative_members.no_telp` & `phone` |
| 5 | `identity_number`| `identity_number`| `CORE_ONBOARDING` | **YES** | NO | Pelamar (Google Form) | `cooperative_members.identity_number` (+enc/bidx) |
| 6 | `gender` | `gender` | `CORE_ONBOARDING` | **YES** | NO | Pelamar (Google Form) | `cooperative_members.jenis_kelamin` (`L`/`P`) |
| 7 | `company_code` | `company_code` | `CORE_ONBOARDING` | **YES** | NO | Pelamar (Google Form) | `cooperative_members.kategori` (`IP`/`CDB`/`KOP`) |
| 8 | `employee_number`| `employee_number`| `ADMIN_ENRICHMENT` | NO | YES | Pelamar (Applicant-Supplied, Admin/System-Verified) | Resolusi ke `cooperative_members.employee_id` |
| 9 | `address` | `address` | `CORE_ONBOARDING` | **YES** | NO | Pelamar (Google Form) | `cooperative_members.address` |
| 10 | `membership_type`| `membership_type`| `ADMIN_ENRICHMENT` | NO | NO | Admin Koperasi (Default `'AB'`) | `cooperative_members.jenis_anggota` (`AB`/`ALB`) |
| 11 | `join_date` | `join_date` | `ADMIN_ENRICHMENT` | NO | NO | Admin Koperasi (Default tgl impor) | `cooperative_members.tanggal_aktif` & `joined_at` |
| 12 | `notes` | `notes` | `ADMIN_ENRICHMENT` | NO | YES | Admin Koperasi (Catatan verifikasi)| `cooperative_members.notes` |

---

## 6. Field yang Ditunda (Deferred Fields)

Field-field berikut secara eksplisit **dikeluarkan dari payload impor awal Fase 2**:

### 6.1 `POST_ONBOARDING_PROFILE` (Dilengkapi Anggota Setelah Akun Aktif)
- `birth_place` (`tempat_lahir`)
- `birth_date` (`tanggal_lahir`)
- `job_title` (`pekerjaan`)

*Alasan*: Field ini tidak memblokir pembentukan identitas autentikasi maupun keabsahan pendaftaran anggota. Anggota dapat melengkapinya secara mandiri di portal Kojayaku ([`/member/profile`](../../resources/js/pages/Kojayaku/Profile.vue)) setelah status aktif.

### 6.2 `FINANCIAL_MIGRATION` (Dialihkan ke Fase 9)
- `npwp` (Nomor Pokok Wajib Pajak)
- `bank_name` (Nama Bank)
- `bank_account_number` (`no_rekening`)
- `bank_account_holder` (`nama_pemilik_rekening`)
- `autodebit_method` (`autodebet`)

*Alasan*: Mengumpulkan nomor rekening dan NPWP pada formulir pendaftaran awal melanggar prinsip minimisasi data (UU PDP) dan menimbulkan friksi onboarding. Data rekening baru diperlukan pada **Fase 9 (Financial Migration)** saat aktivasi autodebet simpanan, pencairan pinjaman, atau pembagian dividen SHU.

---

## 7. Aturan Minimisasi Data Sensitif (Data Minimization Policy)

Tabel evaluasi kepatuhan data minimisasi untuk seluruh atribut identitas sensitif:

| Field Sensitif | Dikumpulkan di Fase 2? | Rationale Kebutuhan | Sumber Nilai | Pihak yang Memasukkan | Kapan Mulai Dibutuhkan? |
| :--- | :---: | :--- | :--- | :--- | :--- |
| **`identity_number` (NIK)** | **YA** | Kewajiban hukum koperasi (UU Perkoperasian), identifikasi orang-seorang warga negara, pencegahan akun ganda. | Calon Anggota (KTP) | Calon Anggota | Saat pendaftaran awal (syarat mutlak keanggotaan sah). |
| **`email`** | **YA** | Bootstrap candidate key untuk Google SSO matching, kanal pengiriman bukti verifikasi dan notifikasi. | Akun Google | Calon Anggota | Saat pendaftaran awal. |
| **`phone_number` (No HP)** | **YA** | Kanal komunikasi langsung, notifikasi WhatsApp/SMS, verifikasi darurat. | Kontak Pribadi | Calon Anggota | Saat pendaftaran awal. |
| **`address` (Alamat)** | **YA** | Kebutuhan verifikasi domisili hukum anggota untuk korespondensi legal koperasi. | Domisili KTP | Calon Anggota | Saat verifikasi berkas oleh Admin Koperasi. |
| **`birth_date` (Tgl Lahir)** | **TIDAK (Diimpor)** | Profiling usia produktif dan asuransi pinjaman (Opsional di profil pos-onboarding). | KTP | Anggota (Mandiri) | Saat melengkapi profil lanjutan / pengajuan pinjaman. |
| **`npwp`** | **TIDAK** | Pemotongan PPh Pasal 23 atas SHU dan bunga simpanan. | Kartu Pajak | Anggota / Staf Pajak | **Fase 9 (Financial Migration)** / Saat tutup buku SHU. |
| **`bank_account_number`** | **TIDAK** | Autodebet simpanan dan pencairan dana pinjaman. | Buku Tabungan | Anggota / Staf Keuangan | **Fase 9 (Financial Migration)** / Saat aktivasi autodebet simpanan. |

---

## 8. Arsitektur Otentikasi & Kontrak Google SSO (Authentication Contract)

Berdasarkan pembekuan kontrak kanonikal (ONB-01 Frozen), terminologi dan alur otentikasi dibekukan (*frozen*) sebagai berikut:

### 8.1 Terminologi Kunci Identitas

```text
BOOTSTRAP MATCH KEY (Saat Impor):
NORMALIZED BOOTSTRAP EMAIL CANDIDATE: LOWER(TRIM(email))
(PENTING: Email pada CSV impor BELUM diverifikasi oleh Google. Statusnya adalah kandidat bootstrap).

GOOGLE VERIFICATION EVENT (Saat Login OAuth):
Verifikasi kepemilikan email terjadi HANYA ketika pengguna login via Google dan Google mengembalikan:
email_verified = true

PERMANENT AUTHENTICATION IDENTITY (Pasca-Login Pertama):
social_accounts.provider = 'google'
social_accounts.provider_id = Google Subject ID / sub
```

---

### 8.2 Alur Otentikasi Terpadu

```text
PROSES IMPOR (ONB-04/06)
  1. Baca email dari CSV: $email = strtolower(trim($csvEmail))
  2. Simpan record anggota di 'cooperative_members'
  3. Buat/temukan record di 'users' dengan email = $email
  4. Tautkan cooperative_members.user_id = users.id
  5. Password Requirement: Akun yang dibuat TIDAK MEMILIKI kredensial plaintext default/bersama.
     Jika skema database mengharuskan users.password non-null, implementasi wajib mengisinya
     dengan hash acak kriptografis yang tidak diketahui pengguna (cryptographically random,
     non-user-known credential/hash) sesuai model otentikasi Laravel, sehingga akun HANYA BISA
     diakses melalui Google OAuth SSO terverifikasi.

LOGIN GOOGLE PERTAMA KALI (ONB-07/08)
  1. Pengguna membuka halaman login dan klik "Masuk dengan Google"
  2. Google OAuth callback mengembalikan Google User:
     - $googleEmail = strtolower(trim($googleUser->getEmail()))
     - $googleId = (string) $googleUser->getId() (sub)
     - $isVerified = (bool) data_get($googleUser->user, 'email_verified')
  3. SYARAT MUTLAK: Sistem memeriksa $isVerified === true.
     Jika false, proses login DITOLAK dengan status 'email_unverified'.
  4. Sistem mencocokkan $googleEmail dengan LOWER(users.email) bootstrap user.
  5. Jika cocok: Buat entri permanen di tabel 'social_accounts':
     - user_id = $user->id
     - provider = 'google'
     - provider_id = $googleId
     - provider_email = $googleEmail
     - linked_at = now()
  6. Login berhasil! Sesi login dimulai.

LOGIN GOOGLE BERIKUTNYA
  1. Sistem mengidentifikasi pengguna langsung melalui pasangan 'provider' + 'provider_id' di tabel 'social_accounts'.
  2. Perubahan email di akun Google tidak memutuskan login (sistem memperbarui provider_email via touchSocial()).
```

---

### 8.3 Kebijakan Resolusi Konflik Identitas (Fail-Closed Policy)

Setiap inkonsistensi identitas wajib berprinsip **FAIL-CLOSED (Tolak, Catat Audit, & Masuk Peninjauan Manual)**:
1. **Email Duplikat**: Jika email pada CSV sudah terdaftar pada `users.email` lain → **REJECT ROW**. Dilarang melakukan *silent merge*.
2. **NIK Duplikat**: Jika NIK pada CSV sudah terdaftar pada anggota lain → **REJECT ROW**.
3. **Nomor Anggota Duplikat**: Jika `member_number` sudah dipakai anggota aktif lain → **REJECT ROW**.
4. **Google Provider Conflict**: Jika identitas akun Google (`provider_id`) bertabrakan atau sudah terikat ke akun pengguna lain:
   ```text
   IDENTITY CONFLICT
         ↓
     FAIL-CLOSED
         ↓
   no automatic link
   no silent merge
   no overwrite
         ↓
    audit event
         ↓
   manual review
   ```
5. **Unverified Google Email**: Jika Google mengembalikan `email_verified = false` → **REJECT LOGIN**.

---

## 9. Aturan Normalisasi Data (Normalization Rules)

Sebelum validator impor (ONB-04) menerima data mentah, normalisasi deterministik berikut wajib diterapkan:

### 9.1 Normalisasi Email
1. `trim()` spasi di awal dan akhir.
2. `strtolower()` ubah seluruh karakter menjadi huruf kecil.
3. Validasi sintaksis RFC 5322 (`filter_var($email, FILTER_VALIDATE_EMAIL)`).
4. *Hasil*: ` Ahmad.Pratama@GMAIL.com ` → `ahmad.pratama@gmail.com`.

### 9.2 Normalisasi Nomor Telepon / HP
1. Hapus spasi, tanda hubung (`-`), titik (`.`), dan kurung.
2. Jika berawalan `+628`, ubah menjadi format standar `08`.
3. Jika berawalan `628`, ubah menjadi `08`.
4. *Hasil*: `+62 812-3456-0001` → `081234560001`.

### 9.3 Normalisasi NIK (Identity Number)
1. **Wajib diproses sebagai STRING**, bukan angka float/integer.
2. Hapus spasi atau karakter non-angka.
3. Panjang wajib tepat 16 digit numerik.
4. **Proteksi Excel**: Di Excel, angka 16 digit akan otomatis dikonversi menjadi notasi ilmiah (`3.17101E+15`) dan 3 digit terakhir menjadi `000`. Template CSV/Excel wajib memformat sel NIK sebagai teks eksplisit (atau diberi awalan tanda petik tunggal `'` saat input manual di spreadsheet).

### 9.4 Normalisasi Nomor Anggota (`no_anggota`)
1. `trim()` dan `strtoupper()`.
2. Format standar: `KOP-` diikuti digit dengan *zero-padding* 3 digit atau lebih (misal `KOP-001`, `KOP-042`).
3. Jika kosong saat proses impor, sistem men-generate otomatis dari urutan tertinggi berikutnya via [`MemberNumberGenerator`](../../app/Services/Cooperative/MemberNumberGenerator.php).

### 9.5 Normalisasi Tanggal (`YYYY-MM-DD`)
1. Format kanonikal tunggal: **`YYYY-MM-DD`** (ISO 8601).
2. Jika sumber data berupa serial date Excel (misal nilai numerik `45292`), konversi via parser Excel date ([`PhpSpreadsheet Date`](../../app/Http/Requests/Cooperative/StoreCooperativeMemberRequest.php#L35-L42)).
3. Hindari ambiguitas format `DD/MM/YYYY` vs `MM/DD/YYYY`.
4. *Hasil*: `15/01/1990` → `1990-01-15`.

### 9.6 Normalisasi Jenis Kelamin, Kategori, dan Jenis Anggota
- Jenis Kelamin: `'Laki-laki'`, `'Pria'`, `'L'`, `'M'` → `'L'`; `'Perempuan'`, `'Wanita'`, `'P'`, `'F'` → `'P'`.
- Perusahaan: `'Indonesia Power'`, `'PT Indonesia Power'`, `'IP'` → `'IP'`; `'Cogindo'`, `'PT Cogindo DayaBersama'`, `'CDB'` → `'CDB'`; `'Koperasi'`, `'Kojaya'`, `'KOP'` → `'KOP'`.
- Jenis Anggota: Jika nama berakhiran `*` (tanda bintang) atau non-karyawan organik → `'ALB'`, selain itu default `'AB'`.

---

## 10. Pemetaan Siklus Status Keanggotaan (Membership Status Contract)

Repository Kojaya memiliki dua kolom status yang saling melengkapi pada tabel `cooperative_members`:
1. `status`: Status operasional keanggotaan (`PENDING`, `ACTIVE`, `INACTIVE`, `RESIGNED`).
2. `validation_status`: Status tata kelola verifikasi berjenjang (`PENDING`, `PENDING_VALIDATION`, `ACTIVE`, `INACTIVE`, `REVISION`, `REJECTED`, `RESIGNED`).

### 10.1 Alur Transisi Status Onboarding

```text
               Google Form / Raw Import
                          │
                          ▼
            ┌───────────────────────────┐
            │   status: PENDING         │  <-- Menunggu Verifikasi Dokumen & Data
            │   validation: PENDING     │
            └─────────────┬─────────────┘
                          │
         Admin Koperasi Verifikasi (Tahap 1)
                          │
                          ▼
            ┌───────────────────────────┐
            │   status: PENDING         │  <-- Menunggu Approval Final
            │   validation:             │
            │     PENDING_VALIDATION    │
            └───────┬───────────┬───────┘
                    │           │
     Pengurus Approve (Tahap 2) Pengurus Tolak / Minta Revisi
                    │           │
                    ▼           ▼
        ┌───────────────────┐  ┌───────────────────────────────────┐
        │ status: ACTIVE    │  │ status: INACTIVE                  │
        │ validation: ACTIVE│  │ validation: REVISION / REJECTED   │
        └───────────────────┘  └───────────────────────────────────┘
        Role 'Anggota' Aktif   Akses Portal Terkunci / Minta Perbaikan
```

### 10.2 Aturan Maker-Checker (Pemisahan Kewenangan)
Sesuai implementasi [`MemberValidationService::assertApproverIsNotVerifier()`](../../app/Services/Cooperative/MemberValidationService.php#L120):
- **Admin Koperasi** (Verifier): Memiliki izin `verify_cooperative_member`, memeriksa kelengkapan identitas, memverifikasi NIK/KK, dan mengubah status ke `PENDING_VALIDATION`.
- **Pengurus Koperasi / System Admin** (Approver): Memiliki izin `approve_cooperative_member`, memberikan persetujuan final, mengaktifkan status ke `ACTIVE`, dan memberikan role `'Anggota'`.
- **Aturan Tegas**: Pengguna yang bertindak sebagai Admin Verifier **DILARANG MERANGKAP** sebagai Pengurus Approver untuk anggota yang sama (`approved_by != admin_validated_by`).

---

## 11. Spesifikasi Workbook Excel Operasional (Human-Friendly Excel Spec)

Operasional impor batch anggota menggunakan file Excel (`.xlsx`) yang terdiri dari 3 lembar kerja (*sheets*):

### 11.1 Sheet 1: `README`
Berisi petunjuk pengisian bagi Admin Koperasi:
- **Peringatan Format NIK**: Seluruh kolom NIK wajib berformat *Text*. Jangan biarkan Excel mengubahnya menjadi angka *Scientific Notation*.
- **Format Tanggal**: Wajib `YYYY-MM-DD` (contoh: `2026-06-01`).
- **Format Nomor HP**: Wajib diawali `08` atau `+628`.
- **Daftar Kode Perusahaan**: `IP` (Indonesia Power), `CDB` (Cogindo DayaBersama), `KOP` (Koperasi).
- **Jenis Anggota**: `AB` (Anggota Biasa) atau `ALB` (Anggota Luar Biasa).

### 11.2 Sheet 2: `MEMBERS`
Lembar kerja tempat admin memasukkan data calon anggota, dengan baris judul (*header*) persis mengikuti nama kanonikal CSV Fase 2 (12 kolom):
`member_number,full_name,email,phone_number,identity_number,gender,company_code,employee_number,address,membership_type,join_date,notes`

### 11.3 Sheet 3: `REFERENCE`
Lembar kerja referensi nilai terkontrol (*Data Validation Dropdown*):
- `company_code`: `IP`, `CDB`, `KOP`.
- `gender`: `L` (Laki-laki), `P` (Perempuan).
- `membership_type`: `AB` (Anggota Biasa), `ALB` (Anggota Luar Biasa).

---

## 12. Matriks Kesiapan Impor (Import Readiness Matrix)

Evaluasi kesiapan setiap field kanonikal Fase 2 untuk diimplementasikan pada validator impor (ONB-04):

| Canonical Field | Kelompok Lifecycle | Dukungan Kolom DB Eksisting? | Dukungan Validasi Eksisting? | Dukungan UI/API Eksisting? | Perlu Migrasi DB Baru? | Status Kesiapan ONB-04 |
| :--- | :--- | :--- | :--- | :--- | :---: | :--- |
| `member_number` | `ADMIN_ENRICHMENT` | YA (`no_anggota`, `member_no`) | YA (`StoreCooperativeMemberRequest`) | YA | TIDAK | **READY** |
| `full_name` | `CORE_ONBOARDING` | YA (`nama_anggota`, `name`) | YA (`StoreCooperativeMemberRequest`) | YA | TIDAK | **READY** |
| `email` | `CORE_ONBOARDING` | YA (`users.email`, `cooperative_members.email`) | YA (`StoreCooperativeMemberRequest`) | YA | TIDAK | **READY** |
| `phone_number` | `CORE_ONBOARDING` | YA (`no_telp`, `phone`) | YA (`StoreCooperativeMemberRequest`) | YA | TIDAK | **READY** |
| `identity_number` | `CORE_ONBOARDING` | YA (`identity_number` + enc/bidx) | YA ([`PiiCryptoService`](../../app/Services/Security/PiiCryptoService.php)) | YA (Masked) | TIDAK | **READY** |
| `gender` | `CORE_ONBOARDING` | YA (`jenis_kelamin`: `L,P`) | YA (`StoreCooperativeMemberRequest`) | YA | TIDAK | **READY** |
| `company_code` | `CORE_ONBOARDING` | YA (`kategori`: `IP,CDB,KOP`) | YA (`StoreCooperativeMemberRequest`) | YA | TIDAK | **READY** |
| `employee_number` | `ADMIN_ENRICHMENT` | MAPPED (`employees.employee_code`) | YA di modul Employee | YA di Employee | TIDAK (Gunakan relasi) | **READY (Via Relasi)** |
| `address` | `CORE_ONBOARDING` | YA (`address`) | YA (`UpdateCooperativeMemberSensitiveDataRequest`)| YA | TIDAK | **READY** |
| `membership_type` | `ADMIN_ENRICHMENT` | YA (`jenis_anggota`: `AB,ALB`) | YA (`StoreCooperativeMemberRequest`) | YA | TIDAK | **READY** |
| `join_date` | `ADMIN_ENRICHMENT` | YA (`tanggal_aktif`, `joined_at`) | YA (`StoreCooperativeMemberRequest`) | YA | TIDAK | **READY** |
| `notes` | `ADMIN_ENRICHMENT` | YA (`notes`) | YA (`UpdateCooperativeMemberSensitiveDataRequest`)| YA | TIDAK | **READY** |

**Kesimpulan Kesiapan**: Seluruh 12 field impor Fase 2 berstatus **100% READY** tanpa memerlukan perubahan skema migrasi database apa pun!

---

## 13. Kontrak Konsumsi untuk Tugas Onboarding Lanjutan (Deliverable 3)

Tugas-tugas berikutnya dalam roadmap Onboarding Anggota (Fase 2) **wajib mengonsumsi kontrak yang telah dibekukan ini**:

- **ONB-02 (Google Form)**: Wajib membatasi formulir hanya pada 8 field `GOOGLE FORM USER-SUPPLIED PAYLOAD` (Bagian 4) dan mengecualikan seluruh data finansial serta field admin.
- **ONB-03 (Admin Verification Workflow)**: Wajib menerapkan verifikasi berjenjang Maker-Checker dan penanganan antrean `UNRESOLVED EMPLOYEE REFERENCE`.
- **ONB-04 (Backend Import Validator)**: Wajib mengonsumsi persis 12 field `PHASE-2 INITIAL IMPORT PAYLOAD` (Bagian 5), menerapkan normalisasi deterministik, dan menolak keras duplikasi NIK/Email.
- **ONB-05 (Import Dry Run)**: Wajib menyajikan simulasi konflik berbasis fail-closed rules.
- **ONB-06 (DEV Import Execution)**: Wajib menggunakan template CSV 12 kolom dengan data uji bebas PII.
- **ONB-07 (Google SSO Matching)**: Wajib memvalidasi kecocokan `LOWER(email)` dan penautan `social_accounts.provider_id` secara aman.
- **ONB-08 (Member First Login & Profile Completeness)**: Mengarahkan anggota ke dashboard / kelengkapan profil pos-onboarding.
- **ONB-09 (E2E Test)**: Menguji seluruh alur pendaftaran dari Google Form hingga anggota aktif.
