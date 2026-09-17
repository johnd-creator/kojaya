# Spesifikasi & Implementasi Member Import Dry-Run / Preview (ONB-05)

Dokumen ini adalah **spesifikasi teknis, arsitektur, dan dokumentasi implementasi resmi untuk Member Import Dry-Run / Preview (ONB-05)** pada ekosistem **Kojaya** (`johnd-creator/kojaya`). Modul ini mengorkestrasi alur pratinjau (simulasi *dry-run*) berkas impor anggota berformat CSV kanonikal 12 kolom menggunakan validator resmi [ONB-04 Backend Import Validator](./backend-import-validator.md) tanpa melakukan persistensi basis data maupun penyimpanan berkas permanen.

---

## 1. Arsitektur Pratinjau (Preview Architecture)

Alur pratinjau dibangun dengan pemisahan tanggung jawab yang ketat (*separation of concerns*) melalui rantai komponen berikut:

```
[Operator Browser (Inertia Vue 3)]
        │
        ▼ (POST /cooperative/members/import/preview - multipart/form-data)
[PreviewMemberImportRequest] ─── Validasi berkas upload (max 10MB, mime csv/txt), import_date, & otorisasi
        │
        ▼
[MemberImportPreviewController@preview]
        │
        ├── 1. Resolusi Target Organisasi via OrganizationScopeService::resolveTargetOrganization()
        │
        ├── 2. Ekstraksi berkas temporer (UploadedFile::getRealPath())
        │
        ▼
[MemberImportValidator::validateFile()] ─── Read-only canonical check & normalization
        │
        ▼
[ImportValidationResult::toArray()] ─── Serialisasi aman (PII Redaction: NIK -> [REDACTED])
        │
        ▼
[Inertia::render('Cooperative/Members/ImportPreview')]
        │
        ▼
[Tampilan Antarmuka Operator (Summary KPIs, Error Badges, Filter Tabs, Tabel Baris)]
```

### 1.1 Komponen & File Terkait
- **Form Request:** [`app/Http/Requests/Cooperative/PreviewMemberImportRequest.php`](file:///home/john-d/Pictures/kojaya/app/Http/Requests/Cooperative/PreviewMemberImportRequest.php)
- **Controller:** [`app/Http/Controllers/Cooperative/MemberImportPreviewController.php`](file:///home/john-d/Pictures/kojaya/app/Http/Controllers/Cooperative/MemberImportPreviewController.php)
- **Validator Otoritatif:** [`app/Services/Cooperative/MemberImportValidator.php`](file:///home/john-d/Pictures/kojaya/app/Services/Cooperative/MemberImportValidator.php)
- **Halaman Antarmuka Vue:** [`resources/js/pages/Cooperative/Members/ImportPreview.vue`](file:///home/john-d/Pictures/kojaya/resources/js/pages/Cooperative/Members/ImportPreview.vue)
- **Indeks Anggota (Navigasi):** [`resources/js/pages/Cooperative/Members/Index.vue`](file:///home/john-d/Pictures/kojaya/resources/js/pages/Cooperative/Members/Index.vue)
- **Test Suite:** [`tests/Feature/Cooperative/MemberImportPreviewTest.php`](file:///home/john-d/Pictures/kojaya/tests/Feature/Cooperative/MemberImportPreviewTest.php)

---

## 2. Jaminan Nol Persistensi (Zero-Persistence Guarantee)

Pratinjau impor anggota dirancang sebagai operasi **baca-saja (*read-only*)** yang deterministik. Modul ini menjamin secara mutlak bahwa:
1. **Tidak Ada Mutasi Basis Data**: Tidak ada panggilan terhadap `CooperativeMember::create()`, `User::create()`, `MemberSocialAccount::create()`, `DB::table()->insert()`, ataupun mutasi model lainnya.
2. **Tidak Ada Penugasan Role / Izin**: Pengguna tidak dibuatkan akun pengguna (`User`), peran (`Role`), maupun hak akses (`Permission`).
3. **Tidak Ada Pembuatan Jurnal / Transaksi**: Tidak ada side effect akuntansi, simpanan pokok, simpanan wajib, maupun ledger saldo yang diinisialisasi.
4. **Hanya Kueri Baca (SELECT)**: Seluruh interaksi dengan basis data dalam `MemberImportValidator` terbatas pada pengecekan benturan nilai unik yang ada (`email`, `identity_number`, `member_number`) dan resolusi nomor induk karyawan (`employee_number`).

Persistensi sesungguhnya didelegasikan sepenuhnya ke tahap **ONB-06 (DEV Member Import)**.

---

## 3. Jaminan Tanpa Penyimpanan Berkas Permanen (No-File-Storage Guarantee)

Berkas CSV yang diunggah oleh operator untuk keperluan pratinjau diperlakukan sebagai berkas *in-memory / ephemeral*:
1. **Tidak Menggunakan Storage Disk**: Dilarang menggunakan `Storage::put()`, `Storage::disk('local')->put()`, `$file->store()`, atau `$file->storeAs()`.
2. **Stream Langsung dari Temp Directory**: Berkas diproses langsung dari *temporary upload path* sistem operasi (`$file->getRealPath()`, biasanya di `/tmp`).
3. **Pembersihan Otomatis**: Berkas temporer dihapus secara otomatis oleh siklus hidup proses PHP/FPM segera setelah siklus *request-response* selesai. Tidak ada jejak berkas CSV yang tertinggal di server.

---

## 4. Perlindungan Data Pribadi (PII Protection)

Nomor Induk Kependudukan (NIK) 16-digit merupakan data sensitif yang diatur oleh undang-undang perlindungan data pribadi (UU PDP):
1. **Redaksi pada Lapisan Serialisasi**: `ImportRowResult::toArray()` secara otomatis mereduksi `identity_number` menjadi string statis `[REDACTED]` baik pada `raw_data` maupun `normalized_data`.
2. **Jaminan Keamanan Browser**: NIK asli 16 digit **TIDAK PERNAH** dikirimkan ke browser client melalui Inertia page props, JSON payload, maupun elemen DOM HTML.
3. **Audit Assertions**: Rangkaian pengujian fitur dalam `MemberImportPreviewTest` memverifikasi bahwa respons Inertia tidak memuat digit NIK asli dan hanya memuat label `[REDACTED]`.

---

## 5. Resolusi Organisasi & Batas Keamanan (Organization Resolution & Security)

Pengaturan konteks organisasi menggunakan layanan kanonikal [`OrganizationScopeService::resolveTargetOrganization()`](file:///home/john-d/Pictures/kojaya/app/Services/OrganizationScopeService.php):

### 5.1 Operator Bertaraf Unit (Unit-Scoped Operator)
- Target organisasi secara otomatis dikunci (*pinned*) ke organisasi asal operator (`$user->cooperative_member->organization_id` atau `$user->organization_id`).
- Operator unit tidak dapat memanipulasi parameter `organization_id`.
- Upaya menentukan organisasi lain yang bukan haknya (*cross-tenant spoofing*) akan langsung ditolak dengan **HTTP 403 Forbidden** (*fail-closed*).

### 5.2 Operator Global (Global Operator)
- Pengguna dengan izin `PermissionEnum::COOPERATIVE_VIEW_ALL` (seperti *System Admin* atau *Super Admin*) wajib memilih organisasi target yang sah melalui parameter `organization_id`.
- Jika `organization_id` tidak disediakan atau merujuk pada UUID yang tidak valid/tidak ditemukan, sistem akan menolak dengan **HTTP 422 Unprocessable Entity**.

---

## 6. Parameter Amplop Permintaan (Envelope Parameters)

Endpoint POST `/cooperative/members/import/preview` menerima *multipart/form-data* dengan spesifikasi parameter:

| Parameter | Tipe | Status | Deskripsi & Validasi |
| :--- | :---: | :---: | :--- |
| `file` | Berkas | **Wajib** | Berkas CSV berukuran maksimal 10.240 KB (10 MB). Mime-type: `text/csv`, `text/plain`, atau ekstensi `.csv`, `.txt`. |
| `import_date` | String | Opsional | Tanggal pendaftaran rujukan berformat `Y-m-d`. Jika dikosongkan, default ke tanggal hari ini (`today()->toDateString()`). Digunakan sebagai nilai fallback `join_date` apabila baris CSV tidak mencantumkan tanggal bergabung. |
| `organization_id` | UUID | Bersyarat | Wajib bagi operator global (`COOPERATIVE_VIEW_ALL`). Diabaikan dan divalidasi ketat untuk operator unit. |

---

## 7. Penanganan Mode Kegagalan (Failure Modes)

### 7.1 Validasi Berkas HTTP 422 (File Validation Failures)
Terjadi jika berkas tidak disertakan, format tidak sesuai (bukan CSV), berkas korup, atau ukuran melampaui 10MB. Laravel Form Request mengembalikan error validasi standar pada key `file`.

### 7.2 Kegagalan Struktur Header Kanonikal (Header Validation Failures)
Jika berkas memiliki kolom kurang dari 12, lebih dari 12, nama kolom tidak sesuai kontrak, urutan tertukar, terdapat kolom duplikat, atau terdapat injeksi kolom sistem:
- Validator mengembalikan `header_valid = false` dan daftar error fatal pada `header_errors`.
- Controller menangani ini dengan mengembalikan HTTP 422 terstruktur atau me-render halaman pratinjau dengan banner peringatan error header fatal, menampilkan kolom yang hilang atau tidak dikenali secara gamblang.

### 7.3 Batch Campuran (Mixed-Validity Batches)
Jika berkas memiliki header valid namun sebagian baris mengandung kesalahan data:
- Batch diberi status `valid = false`.
- Dihitung total baris, jumlah baris valid, dan jumlah baris invalid.
- Setiap baris memiliki status `VALID`, `INVALID`, atau `MANUAL_REVIEW` beserta rincian error per field.

### 7.4 Deteksi Duplikasi Dalam Berkas (In-File Duplicate Detection)
`MemberImportValidator` mendeteksi baris dalam berkas CSV yang sama yang memiliki `email`, `identity_number`, atau `member_number` yang kembar, menandainya dengan kode error:
- `DUPLICATE_EMAIL_BATCH`
- `DUPLICATE_IDENTITY_NUMBER_BATCH`
- `DUPLICATE_MEMBER_NUMBER_BATCH`

### 7.5 Deteksi Benturan Basis Data (Database Duplicate Detection)
Nilai unik yang bertabrakan dengan data yang sudah ada di basis data ditandai dengan error:
- `EMAIL_ALREADY_EXISTS`
- `IDENTITY_NUMBER_ALREADY_EXISTS`
- `MEMBER_NUMBER_ALREADY_EXISTS`

---

## 8. Pemicu Peninjauan Manual (Manual Review Triggers)

Apabila baris CSV mencantumkan `employee_number` (Nomor Induk Karyawan perusahaan sponsor), sistem melakukan pengecekan:
1. Jika nomor karyawan valid dan ditemukan pada tabel karyawan mitra perusahaan (`company_employees`), baris disetujui sebagai `VALID` dengan status penautan `RESOLVED`.
2. Jika nomor karyawan tidak ditemukan (*unresolved employee*), baris **TIDAK DITANDAI SEBAGAI INVALID**, melainkan diberi status **`MANUAL_REVIEW`**.
3. Status ini mengindikasikan bahwa data anggota valid secara administratif, namun hubungan kepegawaiannya memerlukan konfirmasi manual oleh operator sebelum dapat dipersistensikan.

---

## 9. Komponen Antarmuka Operator & Alur Kerja (Operator UI & Workflow)

Halaman [`ImportPreview.vue`](file:///home/john-d/Pictures/kojaya/resources/js/pages/Cooperative/Members/ImportPreview.vue) menyediakan alur kerja terpadu:
1. **Banner Keamanan Simulasi**: Peringatan amber mencolok di bagian atas yang menegaskan bahwa halaman ini hanya melakukan simulasi *dry-run* dan tidak menyimpan data apa pun ke basis data.
2. **Formulir Parameter Amplop**:
   - Pemilihan organisasi target (khusus operator global).
   - Pemilihan tanggal impor rujukan.
   - Area unggah berkas (*dropzone*) interaktif dengan informasi nama berkas dan ukuran.
3. **Panduan Template Kanonikal**: Tombol unduh langsung template CSV resmi 12 kolom (`/cooperative/members/import/template`).
4. **Kartu KPI Ringkasan Batch**:
   - Total Baris (*Total Rows*)
   - Baris Valid (*Valid Rows*) - Hijau
   - Baris Invalid (*Invalid Rows*) - Merah
   - Perlu Peninjauan Manual (*Manual Review Rows*) - Kuning
5. **Banner Kesiapan Batch (Readiness Banner)**:
   - Evaluasi visual instan terhadap kelayakan impor berkas.
6. **Tab Filter Baris**: Memungkinkan operator menyaring tampilan tabel berdasarkan:
   - *Semua Baris*
   - *Hanya Valid*
   - *Hanya Invalid*
   - *Perlu Peninjauan Manual*
7. **Tabel Pratinjau Interaktif**:
   - Nomor baris CSV asli.
   - Status baris dengan badge warna.
   - Kolom Nama, Email, NIK (`[REDACTED]`), No. Anggota, No. Karyawan.
   - Daftar error terstruktur yang menjelaskan field mana yang salah beserta penjelasannya dalam bahasa yang mudah dipahami.

---

## 10. Logika Banner Kesiapan Batch (Readiness Banner Logic)

Terdapat 3 keadaan utama pada Banner Kesiapan:
1. **Siap Diimpor (Ready for Import)**:
   - Kondisi: `valid_rows === total_rows && invalid_rows === 0 && manual_review_rows === 0`.
   - Visual: Kotak hijau (*emerald*) dengan ikon centang sukses.
   - Pesan: "Semua baris valid dan memenuhi kontrak kanonikal. Berkas ini siap untuk dipersistensikan pada tahap eksekusi impor (ONB-06)."
2. **Perlu Peninjauan Manual (Requires Review)**:
   - Kondisi: `invalid_rows === 0 && manual_review_rows > 0`.
   - Visual: Kotak kuning (*amber*) dengan ikon peringatan.
   - Pesan: "Terdapat data yang memerlukan konfirmasi manual (misal nomor karyawan mitra yang belum terdaftar)."
3. **Batch Tidak Valid (Invalid Batch)**:
   - Kondisi: `invalid_rows > 0`.
   - Visual: Kotak merah (*rose*) dengan ikon silang peringatan.
   - Pesan: "Ditemukan kesalahan validasi pada data. Harap perbaiki berkas CSV Anda berdasarkan rincian error di bawah sebelum melakukan impor."

---

## 11. Mekanisme Unduh Template Aman (Safe Template Download)

Endpoint `GET /cooperative/members/import/template` menyediakan akses langsung ke berkas template kanonikal:
- Berkas sumber: `docs/onboarding/member-import-template.csv`
- Header HTTP yang dikembalikan:
  - `Content-Type: text/csv; charset=UTF-8`
  - `Content-Disposition: attachment; filename="template-import-anggota-kojaya.csv"`
- Menggunakan `response()->download()` secara aman tanpa eksposur path internal server.

---

## 12. Mengapa Pratinjau Tidak Mempersistensikan Anggota (Pointer ke ONB-06)

Pemisahan antara simulasi (*preview*) dan eksekusi (*commit/persist*) merupakan prinsip operasional penting pada sistem perbankan/koperasi:
- **Verifikasi Sebelum Eksekusi**: Operator harus dapat memeriksa kualitas data, mendeteksi benturan, dan memverifikasi kesalahan tanpa risiko mengotori basis data produksi.
- **Transaksional & Idempoten**: Eksekusi persistensi pada **ONB-06 (DEV Member Import)** akan membutuhkan manajemen transaksi basis data (`DB::transaction()`), pembuatan akun pengguna, pembuatan nomor anggota otomatis (*fallback auto-generation*), penautan karyawan mitra, dan pencatatan log audit impor yang tidak boleh dijalankan separuh jalan.

---

## 13. Inventaris Rute Lengkap (Route Inventory)

Seluruh rute dilindungi oleh middleware autentikasi dan otorisasi `can:manage_cooperative_member`:

| Metode | URI | Nama Rute | Controller Tindakan | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/cooperative/members/import` | `cooperative.members.import` | `MemberImportPreviewController@index` | Menampilkan formulir unggah berkas pratinjau |
| `POST` | `/cooperative/members/import/preview` | `cooperative.members.import.preview` | `MemberImportPreviewController@preview` | Memproses validasi berkas dan merender tabel pratinjau |
| `GET` | `/cooperative/members/import/template` | `cooperative.members.import.template` | `MemberImportPreviewController@downloadTemplate` | Mengunduh berkas template CSV kanonikal 12 kolom |

*Catatan: Rute di atas didefinisikan sebelum `Route::resource('members', ...)` untuk mencegah konflik penangkapan URI dinamis `{member}`.*

---

## 14. Ringkasan Pengujian Fitur (Test Suite Summary)

Berkas pengujian: [`tests/Feature/Cooperative/MemberImportPreviewTest.php`](file:///home/john-d/Pictures/kojaya/tests/Feature/Cooperative/MemberImportPreviewTest.php)

Cakupan pengujian (23 skenario, 241 asersi):
1. **Otorisasi**: Penolakan akses tamu (*guest redirect to login*) dan pengguna tanpa izin `manage_cooperative_member` (HTTP 403).
2. **Konteks Unit**: Pengguna unit otomatis terikat pada organisasinya dan dilarang mengubah target organisasi lain (*spoofing prevention* HTTP 403).
3. **Konteks Global**: Pengguna dengan `COOPERATIVE_VIEW_ALL` dapat memilih organisasi target secara eksplisit, dan gagal (HTTP 422) jika organisasi tidak valid.
4. **Validasi Berkas**: Penolakan berkas kosong, berkas non-CSV, atau berkas melebihi batas 10MB (HTTP 422).
5. **Validasi Header**: Penolakan jika berkas memiliki jumlah kolom bukan 12, urutan kolom salah, atau nama kolom tidak sesuai kontrak.
6. **Perhitungan Ringkasan (Summary Counts)**: Verifikasi keakuratan penghitungan total baris, baris valid, dan baris invalid pada batch campuran.
7. **Deteksi Duplikasi**: Pengecekan duplikasi NIK/Email di dalam batch maupun terhadap database yang ada.
8. **Peninjauan Manual**: Verifikasi penandaan status `MANUAL_REVIEW` untuk karyawan yang nomornya tidak terdaftar di sistem.
9. **Redaksi PII Mutlak**: Memverifikasi bahwa digit NIK asli tidak pernah bocor ke props Inertia dan selalu bernilai `[REDACTED]`.
10. **Jaminan Nol Persistensi**: Memastikan tabel `cooperative_members`, `users`, dan `member_social_accounts` tetap bersih/kosong setelah pratinjau dijalankan.
11. **Jaminan Nol Storage**: Memverifikasi tidak ada berkas yang disimpan di storage lokal maupun publik.
12. **Unduhan Template**: Memverifikasi ketersediaan dan keabsahan berkas template CSV yang diunduh.

---

## 15. Perintah Verifikasi (Verification Commands)

Perintah-perintah berikut digunakan untuk memverifikasi fungsionalitas dan kepatuhan kode:

```bash
# 1. Jalankan pengujian fitur pratinjau impor anggota
php artisan test --compact tests/Feature/Cooperative/MemberImportPreviewTest.php

# 2. Jalankan pengujian unit/fitur backend validator
php artisan test --compact tests/Feature/Cooperative/MemberImportValidatorTest.php

# 3. Format kode PHP sesuai standar proyek
vendor/bin/pint --dirty --format agent

# 4. Regenerasi deklarasi rute TypeScript Wayfinder
php artisan wayfinder:generate --no-interaction

# 5. Linting & Type-checking antarmuka Vue
npx eslint resources/js/pages/Cooperative/Members/ImportPreview.vue resources/js/pages/Cooperative/Members/Index.vue
npx prettier --check resources/js/pages/Cooperative/Members/ImportPreview.vue resources/js/pages/Cooperative/Members/Index.vue

# 6. Kompilasi bundle frontend Vite
npm run build
```
