# Spesifikasi & Implementasi Backend Import Validator (ONB-04)

Dokumen ini adalah **spesifikasi teknis, arsitektur, dan dokumentasi implementasi resmi untuk Backend Import Validator (ONB-04)** pada ekosistem **Kojaya** (`johnd-creator/kojaya`). Validator ini bertindak sebagai gerbang kanonikal (*canonical gate*) yang bersifat **read-only, deterministik, dan fail-closed** untuk memvalidasi berkas/data impor anggota sebelum tahap simulasi (*dry-run* ONB-05) dan eksekusi persistensi (*staging/DEV import* ONB-06).

---

## 1. Ruang Lingkup & Sumber Kebenaran (Scope & Source of Truth)

### 1.1 Tujuan & Filosofi Desain
1. **Canonical Gate**: Memastikan seluruh data asupan anggota baru mematuhi secara ketat kontrak 12 kolom kanonikal yang telah dibekukan pada [ONB-01 Member Data Contract — FROZEN](./member-data-contract.md).
2. **Deterministic Normalization**: Menyeragamkan format masukan (spasi, huruf besar/kecil, nomor telepon E.164/nasional, struktur NIK 16 digit, dan tanggal ISO 8601) tanpa mengubah esensi nilai.
3. **Fail-Closed Security**: Menolak setiap data yang tidak valid, bertabrakan, atau ambigu. Tidak ada *silent merge*, *silent overwrite*, *auto-link*, maupun asumsi permisif.
4. **Strictly Read-Only (No Persistence)**: Validator **DILARANG KERAS** melakukan operasi mutasi basis data (`INSERT`, `UPDATE`, `DELETE`), pembuatan log audit, penugasan role, penautan akun sosial, maupun modifikasi status anggota apa pun.
5. **Machine-Readable Structured Result**: Mengembalikan objek hasil validasi berstruktur rapi yang siap dikonsumsi langsung oleh modul simulasi *dry-run* (ONB-05) dan eksekusi impor (ONB-06).

### 1.2 Dokumen Sumber Otoritatif
- [`docs/onboarding/member-data-contract.md`](./member-data-contract.md) — Kontrak data anggota kanonikal Fase 2 (ONB-01 FROZEN).
- [`docs/onboarding/member-import-template.csv`](./member-import-template.csv) — Template CSV impor 12 kolom inisial.
- [`docs/onboarding/google-form-alignment.md`](./google-form-alignment.md) — Penyelarasan asupan pelamar Google Form (ONB-02).
- [`docs/onboarding/admin-verification-workflow.md`](./admin-verification-workflow.md) — Alur kerja verifikasi administratif & Maker-Checker (ONB-03).

### 1.3 Artefak Kode Terkait
- Layanan Utama: [`app/Services/Cooperative/MemberImportValidator.php`](../../app/Services/Cooperative/MemberImportValidator.php)
- DTO Hasil Batch: [`app/Services/Cooperative/ImportValidationResult.php`](../../app/Services/Cooperative/ImportValidationResult.php)
- DTO Hasil Baris: [`app/Services/Cooperative/ImportRowResult.php`](../../app/Services/Cooperative/ImportRowResult.php)
- DTO Error Terstruktur: [`app/Services/Cooperative/ImportValidationError.php`](../../app/Services/Cooperative/ImportValidationError.php)
- Test Suite: [`tests/Feature/Cooperative/MemberImportValidatorTest.php`](../../tests/Feature/Cooperative/MemberImportValidatorTest.php)

---

## 2. Kontrak Header 12 Kolom Kanonikal (Canonical Header Contract)

### 2.1 Susunan & Urutan Pasti Header
Berkas impor batch **wajib menyajikan tepat 12 kolom** dalam urutan kanonikal yang persis:

```csv
member_number,full_name,email,phone_number,identity_number,gender,company_code,employee_number,address,membership_type,join_date,notes
```

### 2.2 Kebijakan Penolakan Header (*Batch Failure*)
Sistem langsung membatalkan proses batch (`header_valid = false`, `valid = false`, `severity = FATAL`) jika ditemukan salah satu kondisi berikut:
- **Jumlah Kolom Berbeda**: Kurang dari 12 kolom atau lebih dari 12 kolom.
- **Urutan Berubah**: Kolom tertukar letaknya (misal: `full_name` diletakkan sebelum `member_number`).
- **Nama Kolom Duplikat**: Terdapat lebih dari satu kolom dengan nama yang sama.
- **Kolom Tanpa Nama**: Terdapat kolom header kosong (`""`).
- **Variasi Tidak Resmi / Fuzzy Variants**: Teks seperti `Member Number`, `full_Name`, `nik`, `No KTP`, atau variasi spasi.
- **Injeksi Field Sistem**: Penyertaan kolom sistem internal seperti `organization_id`, `employee_id`, `user_id`, `status`, `validation_status`, `roles`, `password`, `OAuth/provider`, dsb.

---

## 3. Matriks Normalisasi & Validasi Field (Field Normalization & Validation Matrix)

Berikut adalah matriks lengkap untuk seluruh 12 field:

| # | Nama Kolom CSV | Status Wajib | Klasifikasi Lifecycle | Aturan Normalisasi | Aturan Validasi | Kode Error Utama |
| -: | :--- | :---: | :--- | :--- | :--- | :--- |
| 1 | `member_number` | Opsional | `ADMIN_ENRICHMENT` | `trim()`, `strtoupper()`. Jika kosong &rarr; `null` & flag `member_number_generation_required = true`. | Jika diisi: wajib format `^KOP-\d{3,}$` (maks 20 karakter). Deteksi duplikasi batch dan benturan DB (`no_anggota`, `member_no`). Validator **tidak pernah** meng-generate nomor. | `INVALID_CONTROLLED_VALUE`, `DUPLICATE_MEMBER_NUMBER_BATCH`, `MEMBER_NUMBER_ALREADY_EXISTS` |
| 2 | `full_name` | **WAJIB** | `CORE_ONBOARDING` | `trim()` saja, mempertahankan spasi internal dan teks apa adanya (tanpa *whitespace collapsing*). | Tidak boleh kosong / spasi saja. Panjang minimal 3 karakter, maksimal 100 karakter (alasan: `full_name` kanonikal mengisi kolom `cooperative_members.name` dan `cooperative_members.nama_anggota`; batas persistensi otoritatif tersempit adalah `nama_anggota` yang berbatas 100 karakter). | `MISSING_REQUIRED_FIELD`, `INVALID_CONTROLLED_VALUE` |
| 3 | `email` | **WAJIB** | `CORE_ONBOARDING` | `strtolower(trim())`. | Sintaksis email sah (RFC 5322), maks 255 karakter. Deteksi duplikasi batch dan benturan DB terhadap `users.email` dan `cooperative_members.email`. Fail closed: tidak ada penggabungan (*merge*) akun. | `MISSING_REQUIRED_FIELD`, `INVALID_CONTROLLED_VALUE`, `DUPLICATE_EMAIL_BATCH`, `EMAIL_ALREADY_EXISTS` |
| 4 | `phone_number` | **WAJIB** | `CORE_ONBOARDING` | Hapus spasi, tanda hubung (`-`), titik (`.`), dan kurung. Awalan `+628` dan `628` diubah menjadi `08`. | Format nomor seluler Indonesia valid (`^08[1-9][0-9]{7,11}$`), total panjang 10–14 digit. *Bukan identity uniqueness* (tidak memblokir nomor bersama). | `MISSING_REQUIRED_FIELD`, `INVALID_CONTROLLED_VALUE` |
| 5 | `identity_number` | **WAJIB** | `CORE_ONBOARDING` | **String murni** (preservasi angka nol di depan). `trim()`. Dilarang konversi ke tipe float/int. Tidak ada perbaikan permisif (*no permissive repair*). | Tepat 16 digit angka numerik (`^\d{16}$`). Deteksi duplikasi batch. Deteksi benturan DB via blind index `PiiCryptoService` (`identity_number_bidx`). Pesan error aman tanpa membocorkan NIK mentah. | `MISSING_REQUIRED_FIELD`, `INVALID_CONTROLLED_VALUE`, `DUPLICATE_IDENTITY_NUMBER_BATCH`, `IDENTITY_NUMBER_ALREADY_EXISTS` |
| 6 | `gender` | **WAJIB** | `CORE_ONBOARDING` | `strtoupper(trim())`. | Nilai terkontrol: hanya `L` (Laki-laki) atau `P` (Perempuan). | `MISSING_REQUIRED_FIELD`, `INVALID_CONTROLLED_VALUE` |
| 7 | `company_code` | **WAJIB** | `CORE_ONBOARDING` | `strtoupper(trim())`. | Nilai terkontrol: hanya `IP`, `CDB`, atau `KOP`. Menolak teks nama perusahaan (`company_name`) seperti `"PT Indonesia Power"`. Dilarang menyamakan `company_code` dengan `organization_id`. | `MISSING_REQUIRED_FIELD`, `INVALID_CONTROLLED_VALUE` |
| 8 | `employee_number` | Opsional | `ADMIN_ENRICHMENT` (Applicant-Supplied Ref) | `trim()`. Jika kosong &rarr; `null`, status `NOT_PROVIDED`, `employee_id = null`. | Jika diisi: format `^[A-Za-z0-9\-]{3,30}$`. Wajib di-resolve terhadap `employees.employee_code` pada konteks organisasi yang sah (`organization_id`). Jika tidak cocok, tidak ada konteks org, benturan, atau ambigu &rarr; fail closed `persistable = false`, `manual_review_required = true`. | `INVALID_CONTROLLED_VALUE`, `REFERENCE_CONTEXT_UNRESOLVED`, `UNRESOLVED_EMPLOYEE_REFERENCE`, `EMPLOYEE_REFERENCE_CONFLICT` |
| 9 | `address` | **WAJIB** | `CORE_ONBOARDING` | `trim()`, mempertahankan spasi internal dan enter (*newlines*). | Tidak boleh kosong. Maksimal 1000 karakter. | `MISSING_REQUIRED_FIELD`, `INVALID_CONTROLLED_VALUE` |
| 10 | `membership_type` | Opsional | `ADMIN_ENRICHMENT` | `strtoupper(trim())`. Jika kosong &rarr; default `'AB'`. | Nilai terkontrol: hanya `AB` (Anggota Biasa) atau `ALB` (Anggota Luar Biasa). | `INVALID_CONTROLLED_VALUE` |
| 11 | `join_date` | Opsional | `ADMIN_ENRICHMENT` | `trim()`. Jika kosong &rarr; default ke konteks eksplisit `import_date`. | Format kalender riil ISO 8601 (`YYYY-MM-DD`). Wajib lolos evaluasi kalender riil (`checkdate()`). Jika kolom kosong dan konteks `import_date` tidak disediakan, validasi gagal (dilarang memanggil `now()` secara tersembunyi). | `MISSING_REQUIRED_FIELD`, `INVALID_CONTROLLED_VALUE` |
| 12 | `notes` | Opsional | `ADMIN_ENRICHMENT` | `trim()`. Jika kosong atau spasi saja &rarr; `null`. | Teks bebas catatan verifikasi. **DILARANG** digunakan untuk menyembunyikan NIP mentah, NIK, kode perusahaan, atau payload yang gagal divalidasi. | - |

---

## 4. Batasan & Resolusi Referensi Pegawai & Organisasi (Employee & Organization Boundary)

### 4.1 Semantik Otoritatif NIP (`employee_number`)
- NIP yang dimasukkan pada berkas impor diklasifikasikan sebagai **`ADMIN_ENRICHMENT (Applicant-Supplied Reference Input)`**.
- Input pelamar **BUKAN** data kepegawaian otoritatif dan tidak dapat diasumsikan benar sebelum diverifikasi terhadap tabel master `employees`.
- Hubungan formal anggota koperasi ke data pegawai terwujud **HANYA** melalui foreign key `cooperative_members.employee_id` yang ter-resolve secara sah.

### 4.2 Alur Resolusi Deterministik

```text
               Input employee_number
                         │
         ┌───────────────┴───────────────┐
         ▼                               ▼
    Blank / Kosong                  Terisi Teks
         │                               │
  Non-Karyawan Sah                       ▼
  employee_id = NULL            Validasi Format NIP (3-30 alfanumerik)
  status: NOT_PROVIDED                   │
  persistable: TRUE                      ▼
  manual_review: FALSE          Periksa Konteks Organisasi ($organizationId)
                                         │
                         ┌───────────────┴───────────────┐
                         ▼                               ▼
                   Konteks Nihil / Invalid       Konteks Valid Ada
                         │                               │
                   FAIL CLOSED                           ▼
                   REFERENCE_CONTEXT_UNRESOLVED  Cari di employees:
                   persistable: FALSE            employee_code + organization_id
                   manual_review: TRUE                   │
                                         ┌───────────────┼───────────────┐
                                         ▼               ▼               ▼
                                    0 Ditemukan     >1 Ditemukan    Tepat 1 Sah
                                         │               │               │
                                    FAIL CLOSED     FAIL CLOSED          ▼
                                    UNRESOLVED_     EMPLOYEE_       Cek Benturan:
                                    EMPLOYEE_REF    CONFLICT        - Linked to active member?
                                    persistable: F  persistable: F  - Duplicate in batch?
                                    manual_review:T manual_review:T      │
                                                                 ┌───────┴───────┐
                                                                 ▼               ▼
                                                             Benturan Ada    Lolos Bersih
                                                                 │               │
                                                             FAIL CLOSED         ▼
                                                             EMPLOYEE_      employee_id = $emp->id
                                                             CONFLICT       status: RESOLVED
                                                             persist: F     persistable: TRUE
                                                             review: T      manual_review: FALSE
```

### 4.3 Penegakan Batasan Organisasi (*Multi-Tenant Isolation*)
- Master pegawai terikat pada kolom `employees.organization_id`.
- Pencarian NIP wajib menyertakan filter organisasi di level query SQL: `WHERE organization_id = ? AND employee_code IN (...)`, bukan lookup global yang difilter di memori.
- Jika pegawai dengan NIP tersebut ada pada organisasi lain namun tidak ada pada organisasi target, statusnya adalah **`UNRESOLVED_EMPLOYEE_REFERENCE`** (bukan *matched* lintas unit).

### 4.4 Larangan Menyimpan NIP Tak Ter-resolve ke Kolom Notes
Jika NIP pelamar tidak ditemukan pada master `employees`:
- Sistem menandai baris sebagai `UNRESOLVED_EMPLOYEE_REFERENCE`, `persistable = false`, dan `manual_review_required = true`.
- Nilai NIP yang tak ter-resolve **TIDAK BOLEH** disalin atau diserialisasi ke dalam kolom `notes`. Kolom `notes` tetap murni berisi catatan administratif yang diinput admin.

---

## 5. Deteksi Duplikasi & Benturan Data (Duplicates & Conflicts Detection)

### 5.1 Matriks Benturan & Kode Error Standar

| Kategori Benturan | Lingkup Pemeriksaan | Kode Error Standar | Severity | Pesan Error Kanonikal |
| :--- | :--- | :--- | :---: | :--- |
| **Email Duplikat Batch** | Baris data dalam satu berkas CSV | `DUPLICATE_EMAIL_BATCH` | `ERROR` | `Email '{email}' duplikat di dalam batch impor.` |
| **Email Terdaftar di DB** | Tabel `users.email` & `cooperative_members.email` | `EMAIL_ALREADY_EXISTS` | `ERROR` | `Email '{email}' sudah terdaftar di sistem.` |
| **NIK Duplikat Batch** | Baris data dalam satu berkas CSV | `DUPLICATE_IDENTITY_NUMBER_BATCH` | `ERROR` | `Nomor identitas (NIK) duplikat di dalam batch impor.` |
| **NIK Terdaftar di DB** | Blind index `cooperative_members.identity_number_bidx` | `IDENTITY_NUMBER_ALREADY_EXISTS` | `ERROR` | `Nomor identitas (NIK) sudah terdaftar di sistem.` |
| **Nomor Anggota Duplikat Batch** | Baris data dalam satu berkas CSV | `DUPLICATE_MEMBER_NUMBER_BATCH` | `ERROR` | `Nomor anggota '{member_number}' duplikat di dalam batch impor.` |
| **Nomor Anggota Terdaftar di DB** | Kolom `cooperative_members.no_anggota` & `member_no` | `MEMBER_NUMBER_ALREADY_EXISTS` | `ERROR` | `Nomor anggota '{member_number}' sudah terdaftar di sistem.` |
| **NIP Tanpa Konteks Organisasi** | Validasi masukan NIP tanpa context `organization_id` | `REFERENCE_CONTEXT_UNRESOLVED` | `ERROR` | `Konteks organisasi tidak disediakan untuk resolusi NIP '{employee_number}'.` |
| **NIP Tidak Terdaftar di HR** | Master `employees.employee_code` pada unit organisasi | `UNRESOLVED_EMPLOYEE_REFERENCE` | `ERROR` | `Pegawai dengan NIP '{employee_number}' tidak ditemukan pada organisasi yang dipilih.` |
| **Pegawai Sudah Terikat Anggota** | Relasi `cooperative_members.employee_id` aktif | `EMPLOYEE_REFERENCE_CONFLICT` | `ERROR` | `Pegawai dengan NIP '{employee_number}' sudah terhubung ke anggota koperasi lain.` |
| **Pegawai Duplikat Batch** | Beberapa baris me-resolve ke satu `employees.id` | `EMPLOYEE_REFERENCE_CONFLICT` | `ERROR` | `Pegawai dengan NIP '{employee_number}' ditautkan ke lebih dari satu baris dalam batch impor.` |
| **Header Berkas Tidak Valid** | Baris 1 CSV | `INVALID_HEADER` | `FATAL` | `Header kolom tidak sesuai kontrak. Diharapkan...` |

---

## 6. Perlindungan Privasi & Keamanan PII (PII Safety & Blind Index)

### 6.1 Protokol Blind Index Kriptografis
- Data NIK (`identity_number`) adalah data berisiko tinggi (*High PII*).
- Pemeriksaan konflik NIK pada basis data **DILARANG** melakukan *table scan* atau mendeskripsi data terenkripsi.
- Validator memanfaatkan layanan [`PiiCryptoService::blindIndexesForActiveVersions('identity_number', $nik)`](../../app/Services/Security/PiiCryptoService.php) untuk menghitung hash HMAC-SHA256 ber-kunci (*peppered*).
- Pencocokan ke database dieksekusi secara aman via:
  ```php
  CooperativeMember::withTrashed()
      ->whereIn('identity_number_bidx', $activeBlindIndexes)
      ->exists();
  ```

### 6.2 Pencegahan Kebocoran PII pada Pesan Error & Serialisasi
- Pesan error untuk `DUPLICATE_IDENTITY_NUMBER_BATCH` dan `IDENTITY_NUMBER_ALREADY_EXISTS` bersifat **aman (safe error messages)**.
- Nilai NIK mentah 16 digit **DILARANG KERAS** dicantumkan pada teks pesan error atau log output validator guna mencegah kebocoran informasi melalui laporan UI atau antarmuka debugging.
- **Model Privasi Dua Representasi (Internal Runtime vs Serialisasi)**:
  - **Internal Runtime Representation**: Validator mempertahankan NIK 16 digit ter-normalisasi yang valid pada properti DTO internal (`$rowResult->normalizedData['identity_number']`) untuk kebutuhan persistensi downstream oleh importer ONB-06. Properti ini tidak boleh dihilangkan dari objek memori.
  - **Serialized / Presentation Representation**: Setiap jalur serialisasi umum (`toArray()`, `jsonSerialize()`, `json_encode()`) untuk integrasi JSON/API/Inertia/ONB-05 maupun log debug **wajib** menyamarkan `identity_number` menjadi `[REDACTED]` (baik pada `raw_data` maupun `normalized_data`).
  - **Immutabilitas DTO**: Proses sanitasi serialisasi dilakukan pada salinan data (`sanitized copy`), tanpa memutasi properti array internal objek DTO.

---

## 7. Kontrak Objek Hasil Terstruktur (Structured Result Contract)

Validator mengembalikan objek bertipe kuat yang mengimplementasikan `ArrayAccess` dan `JsonSerializable`:

### 7.1 Objek Tingkat Batch: `ImportValidationResult`
- `valid`: `bool` — bernilai `true` jika dan hanya jika seluruh header valid, tidak ada error, dan seluruh baris berstatus valid.
- `header_valid`: `bool` — status keabsahan susunan dan nama 12 header kolom.
- `total_rows`: `int` — total baris data yang dievaluasi (di luar baris header).
- `valid_rows`: `int` — jumlah baris yang memenuhi seluruh kriteria validasi.
- `invalid_rows`: `int` — jumlah baris yang memiliki setidaknya satu kesalahan validasi.
- `errors`: `list<ImportValidationError>` — daftar seluruh error (baik tingkat header maupun baris).
- `rows`: `list<ImportRowResult>` — representasi terstruktur setiap baris data.
- `toArray()`: `array` — representasi array asosiatif lengkap untuk serialisasi JSON / Inertia props (NIK pada `raw_data` dan `normalized_data` tersanitasi menjadi `[REDACTED]`).

### 7.2 Objek Tingkat Baris: `ImportRowResult`
- `row_number`: `int` — nomor urut baris data pada berkas (1-indexed).
- `valid`: `bool` — status keabsahan baris bersangkutan.
- `raw_data`: `array<string, mixed>` — masukan mentah baris (field `identity_number` disanitasi/`[REDACTED]` untuk proteksi PII pada output JSON/toArray).
- `normalized_data`: `array<string, mixed>` — tepat 12 kunci kanonikal hasil normalisasi deterministik. Nilai NIK mentah 16 digit tetap tersedia secara internal pada `$rowResult->normalizedData['identity_number']` untuk kebutuhan persistensi ONB-06, tetapi disanitasi menjadi `[REDACTED]` saat dipanggil via `toArray()`, `jsonSerialize()`, atau `json_encode()`.
- `resolved_employee_id`: `?int` — ID pegawai master `employees.id` jika NIP ter-resolve, atau `null`.
- `employee_resolution_status`: `string` — salah satu dari: `NOT_PROVIDED`, `RESOLVED`, `UNRESOLVED`, `CONFLICT`.
- `member_number_generation_required`: `bool` — `true` jika nomor anggota kosong dan membutuhkan penomoran otomatis saat persistensi.
- `manual_review_required`: `bool` — `true` jika baris memiliki error atau referensi pegawai tak ter-resolve.
- `persistable`: `bool` — bernilai `true` jika dan hanya jika baris valid serta referensi pegawai berstatus `RESOLVED` atau `NOT_PROVIDED`.
- `errors`: `list<ImportValidationError>` — rincian error pada baris bersangkutan.

---

## 8. Jaminan Tanpa Mutasi Basis Data (No-Persistence Guarantee)

Validator ini dirancang sebagai fungsi verifikasi murni (*pure verification function*):
- **Tidak ada query mutasi**: Tidak ada eksekusi `insert()`, `update()`, `delete()`, `save()`, `create()`, atau `forceDelete()`.
- **Tidak ada efek samping autentikasi**: Tidak ada penulisan akun `users`, `social_accounts`, maupun penerbitan token sesi.
- **Tidak ada mutasi peran**: Tidak ada penetapan peran Spatie (`Role`) ke pengguna.
- **Tidak ada penulisan audit log**: Tidak memanggil `AuditLogService` selama tahap validasi membaca data.
- **Bukti Empiris Test Suite**: Diuji secara otomatis pada [`MemberImportValidatorTest::test_no_persistence_guarantee()`](../../tests/Feature/Cooperative/MemberImportValidatorTest.php) yang membuktikan jumlah record pada tabel `cooperative_members`, `users`, `employees`, `audit_logs`, dan `social_accounts` bernilai identik sebelum dan sesudah eksekusi validasi.

---

## 9. Revalidasi TOCTOU untuk Eksekusi Impor (TOCTOU Revalidation for ONB-06)

Dalam arsitektur sistem berbasis web, terdapat jeda waktu antara peninjauan simulasi validasi (ONB-04 / ONB-05) dengan eksekusi penulisan data ke basis data (ONB-06). Situasi ini berpotensi memicu kerentanan **Time-of-Check to Time-of-Use (TOCTOU)**:
- Anggota lain mungkin mendaftar dan memvalidasi NIK atau email yang sama saat admin sedang meninjau berkas impor.
- Master pegawai mungkin dihapus atau diubah organisasinya di tengah jalan.

### Mekanisme Perlindungan TOCTOU:
1. Saat ONB-06 mengeksekusi penulisan data, proses impor **WAJIB MENJALANKAN ULANG** `MemberImportValidator` di dalam transaksi database berbalut kunci baris atomik (`DB::transaction`).
2. Jika ditemukan perbedaan status antara hasil peninjauan awal dengan status pada saat commit, transaksi dibatalkan seketika (*fail-closed rollback*).

---

## 10. Serah Terima ke ONB-05 Dry Run Simulation (Handoff to ONB-05)

Struktur hasil yang dihasilkan oleh `MemberImportValidator` dirancang secara presisi untuk langsung dikonsumsi oleh alur kerja simulasi peninjauan (ONB-05):
1. **Header Inspection**: Menampilkan status keabsahan header berkas dan menolak pratinjau jika format kolom rusak.
2. **Ringkasan Validasi**: Menyajikan metrik `total_rows`, `valid_rows`, dan `invalid_rows` pada kartu indikator UI.
3. **Penyaringan Antrean Review Manual**: Menyaring baris-baris dengan `manual_review_required = true` untuk ditampilkan pada tab peninjauan admin.
4. **Indikator Kesiapan Persistensi**: Tombol eksekusi impor hanya aktif jika seluruh baris berstatus `persistable = true` atau setelah baris bermasalah dikecualikan.

---

## 11. Batasan Teknis & Keputusan Terbuka (Limitations & Open Decisions)

- **Frontend Scope**: Tugas ini (ONB-04) berfokus 100% pada backend validator service dan test suite. Pembuatan antarmuka pengguna (UI) impor berada di bawah tugas ONB-05.
- **Auto-Generate Nomor Anggota**: Validator menandai baris yang memerlukan nomor anggota baru dengan flag `member_number_generation_required = true`, namun tidak mengeksekusi penomoran secara fisik. Eksekusi penomoran dilakukan oleh layanan [`MemberNumberGenerator`](../../app/Services/Cooperative/MemberNumberGenerator.php) pada saat persistensi riil di ONB-06.
- **Kepatuhan Skema**: Tidak ada modifikasi skema database maupun pembuatan file migrasi baru; seluruh 12 field kanonikal didukung 100% oleh skema tabel yang ada.
