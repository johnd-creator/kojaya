# Spesifikasi Google Form Pendaftaran Anggota Kojaya (ONB-02 Google Form Alignment)

Dokumen ini adalah **spesifikasi implementasi resmi Google Form Pendaftaran Anggota Kojaya (ONB-02)** yang diselaraskan secara presisi 1:1 dengan kontrak data anggota kanonikal yang telah dibekukan pada [ONB-01 Member Data Contract — FROZEN](./member-data-contract.md) dan [member-import-template.csv](./member-import-template.csv).

Dokumen ini berfungsi sebagai panduan otoritatif bagi tim operasional dan administrator dalam menyusun, memvalidasi, dan mengelola formulir pendaftaran daring (Google Form) calon anggota Koperasi Kojaya pada alur Onboarding Fase 2.

---

## 1. Tujuan & Ruang Lingkup (Purpose & Scope)

### 1.1 Tujuan
1. Menetapkan antarmuka masukan (*intake interface*) pendaftaran mandiri yang ramah pengguna, minim friksi, dan taat hukum bagi seluruh calon anggota Koperasi Kojaya.
2. Membatasi pengumpulan data hanya pada atribut identitas primer yang sah (*data minimization* sesuai UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi).
3. Menjamin keselarasan deterministik antara respons Google Form dengan skema impor batch 12 kolom kanonikal Fase 2.
4. Mencegah kebocoran wewenang administratif dan data sensitif finansial pada tahap awal pendaftaran.

### 1.2 Batasan Ruang Lingkup (Scope Boundaries)
- **Cakupan Tugas Ini (ONB-02)**: Spesifikasi pertanyaan, teks panduan, tipe input, validasi masukan formulir, pemetaan nilai terkontrol, kebijakan privasi, serta aturan serah terima (*handoff*) ke verifikasi admin.
- **Di Luar Cakupan (Non-Goals)**:
  - Bukan implementasi backend importer atau parser CSV (dialokasikan ke tugas ONB-04).
  - Bukan perubahan skema atau migrasi database (DB schema tetap 100% tidak berubah).
  - Bukan implementasi alur OAuth SSO atau modifikasi kode `GoogleSsoService` (dialokasikan ke tugas ONB-07).
  - Bukan pembuatan Google Form fisik pada akun eksternal tanpa mandat tertulis.

---

## 2. Sumber Kebenaran (Source of Truth)

Spesifikasi ini disusun secara ketat berdasarkan artefak kanonikal yang telah dibekukan pada branch `main`:
1. **[docs/onboarding/member-data-contract.md](./member-data-contract.md)**: Kontrak data anggota kanonikal (ONB-01 Frozen).
2. **[docs/onboarding/member-import-template.csv](./member-import-template.csv)**: Template CSV impor 12 kolom inisial Fase 2.
3. **Arsitektur Eksisting Aplikasi**:
   - Model Anggota: `app/Models/CooperativeMember.php`
   - Model Pengguna Otentikasi: `app/Models/User.php`
   - Model Akun Sosial (OAuth): `app/Models/SocialAccount.php`
   - Master Pegawai: `app/Models/Employee.php`
   - Layanan Enkripsi PII: `app/Services/Security/PiiCryptoService.php`
   - Layanan Google SSO: `app/Services/Auth/Sso/GoogleSsoService.php`

---

## 3. Struktur Google Form (Google Form Structure)

Untuk memberikan pengalaman pengisian yang teratur tanpa membebani calon anggota, formulir dirancang dalam **4 Bagian (*Sections*)** yang ringkas dan logis:

```text
┌────────────────────────────────────────────────────────────────────────┐
│                        STRUKTUR GOOGLE FORM ONB-02                     │
├───────────┬────────────────────────────┬───────────────────────────────┤
│ BAGIAN    │ NAMA BAGIAN                │ KONTEN / FOKUS                │
├───────────┼────────────────────────────┼───────────────────────────────┤
│ Bagian 1  │ Informasi & Persetujuan    │ Judul formulir, pengantar,    │
│           │ Data Pribadi (PDP)         │ dasar hukum pendaftaran,      │
│           │                            │ pernyataan persetujuan (wajib)│
├───────────┼────────────────────────────┼───────────────────────────────┤
│ Bagian 2  │ Identitas Diri Anggota     │ Nama Lengkap, NIK 16 Digit,   │
│           │                            │ Jenis Kelamin, Alamat         │
├───────────┼────────────────────────────┼───────────────────────────────┤
│ Bagian 3  │ Kontak & Akun Login Google │ Email Akun Google Aktif,      │
│           │                            │ Nomor WhatsApp Aktif          │
├───────────┼────────────────────────────┼───────────────────────────────┤
│ Bagian 4  │ Kepegawaian & Unit Kerja   │ Perusahaan/Unit Kerja,        │
│           │                            │ NIP (Opsional, jika ada)      │
└───────────┴────────────────────────────┴───────────────────────────────┘
```

---

## 4. Daftar Pasti Field Pelamar (Exact Applicant Fields)

Google Form mengumpulkan tepat **8 field data** dari pelamar:

```text
1. full_name        (Nama Lengkap sesuai KTP)
2. email            (Alamat Email yang Terhubung ke Akun Google)
3. phone_number     (Nomor WhatsApp / Handphone Aktif)
4. identity_number  (Nomor Induk Kependudukan 16 Digit)
5. gender           (Jenis Kelamin: Laki-laki / Perempuan)
6. company_code     (Perusahaan / Unit Kerja: IP / CDB / KOP)
7. address          (Alamat Domisili Lengkap)
8. employee_number  (Nomor Induk Pegawai jika memiliki NIP)
```

Komposisi ketat: **7 Wajib (`CORE_ONBOARDING`) + 1 Opsional (`ADMIN_ENRICHMENT` - Applicant-Supplied Reference Input)**.

---

## 5. Spesifikasi Rinci Per Field (Field-by-Field Specification)

Berikut adalah spesifikasi implementasi detail untuk setiap pertanyaan pada Google Form:

### 5.1 Pertanyaan 1 — Nama Lengkap
- **Canonical Field**: `full_name`
- **User-facing Question**: `Nama Lengkap (Sesuai KTP)`
- **Description / Help Text**: `Masukkan nama lengkap Anda persis sesuai dengan yang tertera pada Kartu Tanda Penduduk (KTP) elektronik Anda, tanpa gelar akademik, gelar keagamaan, atau singkatan nama yang tidak resmi.`
- **Input Type**: Short answer (Jawaban singkat)
- **Required?**: **YA (Wajib)**
- **Validation**:
  - Text length: Minimal 3 karakter, maksimal 100 karakter.
  - Reject empty / whitespace-only string.
- **Allowed Values**: Teks alfabetik bebas, spasi, tanda petik satu (`'`) untuk nama tertentu.
- **Normalization Expectation**: `trim()` spasi di awal dan akhir; normalisasi spasi ganda menjadi spasi tunggal. Tidak boleh diubah otomatis menjadi huruf kapital semua (*uppercase*) agar mempertahankan penulisan nama asli yang sah.
- **Privacy Sensitivity**: Personal Data (Umum).
- **Destination / Downstream Mapping**: Dipetakan ke kolom `cooperative_members.nama_anggota` dan `cooperative_members.name`.
- **Error / Help Wording**: `"Mohon masukkan nama lengkap Anda sesuai KTP (minimal 3 karakter)."`

### 5.2 Pertanyaan 2 — Alamat Email yang Terhubung ke Akun Google
- **Canonical Field**: `email`
- **User-facing Question**: `Alamat Email yang Terhubung ke Akun Google`
- **Description / Help Text**: `Gunakan alamat email yang akan digunakan untuk masuk ke aplikasi Kojaya melalui Google. Dapat berupa akun Google pribadi maupun akun Google Workspace organisasi. Pastikan Anda memiliki akses aktif ke akun Google ini.`
- **Input Type**: Short answer (Jawaban singkat)
- **Required?**: **YA (Wajib)**
- **Validation**:
  - Text &rarr; Email address regex validation bawaan Google Form.
- **Allowed Values**: Sintaksis email valid RFC 5322.
- **Normalization Expectation**: `LOWER(TRIM(email))`.
- **Status Identitas Kanonikal**: **`NORMALIZED BOOTSTRAP EMAIL CANDIDATE`**.
  > **PENTING**: Pengisian email di Google Form **BELUM** berstatus *Google-Verified*. Email ini berkedudukan sebagai kandidat kunci bootstrap. Verifikasi kepemilikan resmi terjadi HANYA ketika anggota pertama kali melakukan autentikasi login via Google OAuth di Kojayaku dan sistem menerima `email_verified = true`.
- **Privacy Sensitivity**: SENSITIVE PII (Kunci otentikasi login).
- **Destination / Downstream Mapping**: Dipetakan ke `users.email` dan `cooperative_members.email`.
- **Error / Help Wording**: `"Mohon masukkan format alamat email yang valid dan terhubung ke akun Google Anda."`

### 5.3 Pertanyaan 3 — Nomor WhatsApp / Handphone Aktif
- **Canonical Field**: `phone_number`
- **User-facing Question**: `Nomor WhatsApp / Handphone Aktif`
- **Description / Help Text**: `Nomor ponsel aktif yang terhubung ke WhatsApp untuk menerima notifikasi status verifikasi pendaftaran, informasi transaksi, dan pengumuman koperasi. Format yang disarankan: 081234567890 atau +6281234567890.`
- **Input Type**: Short answer (Jawaban singkat)
- **Required?**: **YA (Wajib)**
- **Validation**:
  - Regular Expression (Matches): `^(\+62|62|0)8[1-9][0-9]{7,11}$`
- **Allowed Values**: Varian nomor seluler Indonesia berawalan `08...`, `+628...`, atau `628...` dengan panjang 10–14 digit.
- **Normalization Expectation**: Hapus karakter spasi, tanda hubung (`-`), kurung, dan titik; ubah awalan `+628` atau `628` menjadi format kanonikal nasional `08...` (misal: `+62 812-3456-0001` &rarr; `081234560001`).
- **Privacy Sensitivity**: SENSITIVE PII (Kontak personal langsung).
- **Destination / Downstream Mapping**: Dipetakan ke `cooperative_members.no_telp` dan `cooperative_members.phone`.
- **Error / Help Wording**: `"Nomor handphone tidak valid. Masukkan nomor seluler Indonesia yang aktif diawali 08 atau +628 (contoh: 081234567890)."`

### 5.4 Pertanyaan 4 — Nomor Induk Kependudukan (NIK)
- **Canonical Field**: `identity_number`
- **User-facing Question**: `Nomor Induk Kependudukan (NIK 16 Digit)`
- **Description / Help Text**: `Masukkan 16 digit Nomor Induk Kependudukan (NIK) sesuai KTP elektronik Anda. Data NIK Anda dilindungi kerahasiaannya dengan enkripsi standar industri dan digunakan secara sah untuk verifikasi identitas anggota koperasi sesuai ketentuan perundang-undangan.`
- **Input Type**: Short answer (Jawaban singkat)
- **Required?**: **YA (Wajib)**
- **Validation**:
  - Regular Expression (Matches): `^[0-9]{16}$`
- **Allowed Values**: Tepat 16 digit numerik tanpa spasi, tanda minus, atau huruf.
- **Normalization Expectation**:
  - **Wajib diperlakukan sebagai STRING murni**. Dilarang dikonversi ke tipe numerik (integer/float) saat diekspor ke spreadsheet/Excel guna menghindari kehilangan presisi digit akibat notasi ilmiah (*scientific notation*).
  - `trim()` spasi dan hapus karakter non-angka.
- **Privacy Sensitivity**: **HIGH PII** (Data kependudukan berisiko tinggi).
- **Destination / Downstream Mapping**: Dipetakan ke `cooperative_members.identity_number` yang disimpan secara terenkripsi via `PiiCryptoService` (AES-256-CBC pada `identity_number_enc` dan HMAC-SHA256 pada blind index `identity_number_bidx`).
- **Error / Help Wording**: `"NIK wajib terdiri dari tepat 16 digit angka sesuai KTP."`

### 5.5 Pertanyaan 5 — Jenis Kelamin
- **Canonical Field**: `gender`
- **User-facing Question**: `Jenis Kelamin`
- **Description / Help Text**: `Pilih jenis kelamin sesuai yang tertera pada KTP elektronik Anda.`
- **Input Type**: Dropdown (Daftar pilihan) atau Multiple choice (Pilihan ganda)
- **Required?**: **YA (Wajib)**
- **Pilihan Tampilan (User-facing Options)**:
  1. `Laki-laki`
  2. `Perempuan`
- **Allowed Values & Mapping**:
  - `Laki-laki` &rarr; canonical `L`
  - `Perempuan` &rarr; canonical `P`
- **Normalization Expectation**: Nilai teks opsi pengguna wajib dipetakan secara deterministik ke single character `'L'` atau `'P'`. Dilarang mengirimkan string bebas ke pipeline hilir.
- **Privacy Sensitivity**: Personal Data (Umum).
- **Destination / Downstream Mapping**: Dipetakan ke kolom `cooperative_members.jenis_kelamin`.
- **Error / Help Wording**: `"Silakan pilih jenis kelamin Anda."`

### 5.6 Pertanyaan 6 — Perusahaan / Unit Kerja Asal
- **Canonical Field**: `company_code`
- **User-facing Question**: `Perusahaan / Unit Kerja Asal`
- **Description / Help Text**: `Pilih perusahaan tempat Anda bekerja, atau pilih Koperasi Kojaya jika Anda adalah mitra kerja atau calon Anggota Luar Biasa (ALB).`
- **Input Type**: Dropdown (Daftar pilihan)
- **Required?**: **YA (Wajib)**
- **Pilihan Tampilan (User-facing Options)**:
  1. `PT Indonesia Power (IP)`
  2. `PT Cogindo DayaBersama (CDB)`
  3. `Koperasi Kojaya (KOP) — Mitra / Anggota Luar Biasa`
- **Allowed Values & Mapping**:
  - `PT Indonesia Power (IP)` &rarr; canonical `IP`
  - `PT Cogindo DayaBersama (CDB)` &rarr; canonical `CDB`
  - `Koperasi Kojaya (KOP) — Mitra / Anggota Luar Biasa` &rarr; canonical `KOP`
- **Aturan Tegas Nama Perusahaan (`company_name`)**:
  - `company_name` **TIDAK DIMINTA** dan **DILARANG** dibuatkan pertanyaan terpisah pada formulir.
  - Sesuai keputusan kontrak kanonikal yang telah dibekukan, `company_name` adalah **`DERIVED_REFERENCE` tunggal** yang diturunkan secara deterministik dari `company_code`:
    - `IP` &rarr; `"PT Indonesia Power"`
    - `CDB` &rarr; `"PT Cogindo DayaBersama"`
    - `KOP` &rarr; `"Koperasi Kojaya"`
- **Privacy Sensitivity**: Organizational Reference.
- **Destination / Downstream Mapping**: Dipetakan ke `cooperative_members.kategori` (`IP`, `CDB`, atau `KOP`).
- **Error / Help Wording**: `"Silakan pilih perusahaan/unit kerja asal Anda."`

### 5.7 Pertanyaan 7 — Alamat Domisili Lengkap
- **Canonical Field**: `address`
- **User-facing Question**: `Alamat Domisili Lengkap`
- **Description / Help Text**: `Tuliskan alamat tempat tinggal / domisili lengkap Anda saat ini (mencakup nama jalan, nomor rumah, RT/RW, kelurahan/desa, kecamatan, kota/kabupaten, dan kode pos).`
- **Input Type**: Paragraph (Jawaban panjang)
- **Required?**: **YA (Wajib)**
- **Validation**: Minimal 10 karakter, maksimal 500 karakter.
- **Allowed Values**: Teks bebas alamat.
- **Normalization Expectation**: `trim()`, normalisasi spasi berlebih dan enter (*newlines*).
- **Prinsip Alamat Tunggal**: Sistem **tidak memecah** alamat menjadi pertanyaan terpisah (seperti "Alamat KTP" vs "Alamat Domisili" vs "Alamat Kantor") demi meminimalkan friksi pendaftaran dan menjaga keselarasan dengan kolom tunggal `cooperative_members.address`.
- **Privacy Sensitivity**: SENSITIVE PII (Lokasi tempat tinggal).
- **Destination / Downstream Mapping**: Dipetakan ke `cooperative_members.address`.
- **Error / Help Wording**: `"Mohon tuliskan alamat tempat tinggal lengkap Anda (minimal 10 karakter)."`

### 5.8 Pertanyaan 8 — Nomor Induk Pegawai (NIP)
- **Canonical Field**: `employee_number`
- **User-facing Question**: `Nomor Induk Pegawai (NIP)`
- **Description / Help Text**: `Isi jika Anda memiliki NIP/nomor pegawai yang terdaftar. Kosongkan jika tidak memiliki.`
- **Input Type**: Short answer (Jawaban singkat)
- **Required?**: **TIDAK (OPSIONAL)**
- **Validation**:
  - Opsional (boleh kosong).
  - Jika diisi: Regex karakter alfanumerik dan tanda hubung `^[A-Za-z0-9\-]{3,30}$`.
- **Klasifikasi & Semantik Otoritatif**:
  - **Klasifikasi**: **`ADMIN_ENRICHMENT` (Applicant-Supplied Reference Input)**.
  - **Semantik**: Input pelamar pada kolom ini **BUKAN** master data kepegawaian otoritatif dan tidak dibatasi ke entitas korporat tertentu pada level formulir. Nilai ini merupakan masukan referensi calon anggota yang wajib divalidasi dan dicocokkan oleh sistem/admin terhadap `employees.employee_code`.
- **Hasil Resolusi Hilir**:
  ```text
  Input NIP Pelamar
        ↓
  Validasi Admin/Sistem vs employees.employee_code
        ↓
  employees.id teridentifikasi
        ↓
  cooperative_members.employee_id (Foreign Key ditautkan)
  ```
- **Penanganan Ketidakcocokan (Unresolved NIP)**:
  - Jika NIP yang dimasukkan pelamar tidak ditemukan di tabel `employees`, sistem menandai record sebagai antrean peninjauan manual (**`UNRESOLVED EMPLOYEE REFERENCE`**).
  - **DILARANG KERAS menyimpan NIP yang tidak ter-resolve ke dalam kolom `notes`** atau menyimpannya sebagai teks bebas.
  - Jika pelamar tidak memiliki NIP atau mendaftar sebagai anggota non-karyawan, nilai diset `null` dan `employee_id = null`.
- **Privacy Sensitivity**: Corporate Identifier.
- **Destination / Downstream Mapping**: Masuk ke pipeline verifikasi untuk meresolusi `cooperative_members.employee_id`.
- **Error / Help Wording**: `"Format NIP hanya boleh mengandung huruf, angka, dan tanda hubung (-)."`

---

## 6. Matriks Status Wajib vs Opsional (Required vs Optional Matrix)

Tabel rangkuman seluruh field pendaftaran yang dikumpulkan melalui Google Form:

| # | Canonical Field | User-facing Label | Tipe Input Form | Status | Alasan / Rationale Kontrak |
| -: | :--- | :--- | :--- | :---: | :--- |
| 1 | `full_name` | Nama Lengkap (Sesuai KTP) | Short Answer | **WAJIB** | Identitas subjek hukum anggota koperasi. |
| 2 | `email` | Alamat Email yang Terhubung ke Akun Google | Short Answer | **WAJIB** | Kunci kandidat bootstrap untuk Google SSO matching. |
| 3 | `phone_number` | Nomor WhatsApp / Handphone Aktif | Short Answer | **WAJIB** | Kanal komunikasi dan notifikasi resmi operasional. |
| 4 | `identity_number` | Nomor Induk Kependudukan (NIK 16 Digit) | Short Answer | **WAJIB** | Syarat mutlak verifikasi keabsahan warga negara & anti-duplikasi akun. |
| 5 | `gender` | Jenis Kelamin | Dropdown / Multiple Choice | **WAJIB** | Atribut demografi resmi profil anggota koperasi. |
| 6 | `company_code` | Perusahaan / Unit Kerja Asal | Dropdown | **WAJIB** | Penentu kategori keanggotaan (`IP`, `CDB`, `KOP`) dan pemetaan hak suara. |
| 7 | `address` | Alamat Domisili Lengkap | Paragraph | **WAJIB** | Kebutuhan hukum domisili korespondensi perkoperasian. |
| 8 | `employee_number`| Nomor Induk Pegawai (NIP) | Short Answer | *OPSIONAL* | Masukan referensi untuk pencocokan ke master `employees` (bagi yang memiliki NIP terdaftar). |

---

## 7. Pemetaan Nilai Terkontrol (Controlled Value Mapping)

Untuk menghindari nilai string arbitrer pada respons formulir, seluruh pilihan ganda/dropdown dipetakan secara kaku:

### 7.1 Jenis Kelamin (`gender`)
```text
┌──────────────────────────────────────┬────────────────────────────┐
│ Label Tampilan di Google Form        │ Nilai Kanonikal Database   │
├──────────────────────────────────────┼────────────────────────────┤
│ Laki-laki                            │ L                          │
│ Perempuan                            │ P                          │
└──────────────────────────────────────┴────────────────────────────┘
```

### 7.2 Perusahaan / Unit Kerja (`company_code`)
```text
┌───────────────────────────────────────────────────┬────────────────────────────┐
│ Label Tampilan di Google Form                     │ Nilai Kanonikal Database   │
├───────────────────────────────────────────────────┼────────────────────────────┤
│ PT Indonesia Power (IP)                           │ IP                         │
│ PT Cogindo DayaBersama (CDB)                      │ CDB                        │
│ Koperasi Kojaya (KOP) — Mitra / Luar Biasa        │ KOP                        │
└───────────────────────────────────────────────────┴────────────────────────────┘
```

---

## 8. Panduan Normalisasi Masukan (Normalization Guidance)

Data mentah respons Google Form wajib melewati proses normalisasi otomatis pada skrip ekspor/tahap verifikasi sebelum dimasukkan ke template impor kanonikal:

```text
┌───────────────────┬───────────────────────────────┬───────────────────────────────┐
│ Canonical Field   │ Format Mentah Pelamar         │ Format Kanonikal Ternormalisasi│
├───────────────────┼───────────────────────────────┼───────────────────────────────┤
│ full_name         │ "  Ahmad   Pratama, S.T. "    │ "Ahmad Pratama, S.T."         │
│ email             │ " Ahmad.Pratama@GMAIL.COM  "  │ "ahmad.pratama@gmail.com"     │
│ phone_number      │ "+62 812-3456-0001"           │ "081234560001"                │
│ identity_number   │ " 3171012301900001 "          │ "3171012301900001" (String)   │
│ gender            │ "Laki-laki"                   │ "L"                           │
│ company_code      │ "PT Indonesia Power (IP)"     │ "IP"                          │
│ address           │ " Jl. Merdeka No. 10 \n\n "   │ "Jl. Merdeka No. 10"          │
│ employee_number   │ " ip-102938 "                 │ "IP-102938"                   │
└───────────────────┴───────────────────────────────┴───────────────────────────────┘
```

---

## 9. Pertimbangan Data Sensitif & Privasi (Sensitive Data & Privacy Policy)

### 9.1 Kebijakan Perlindungan Data Pribadi
Formulir mengumpulkan data berstatus **HIGH PII** (`identity_number` / NIK) dan **SENSITIVE PII** (`email`, `phone_number`, `address`).

Oleh karena itu, formulir wajib menyertakan klausul persetujuan eksplisit (*Consent Agreement*) pada Bagian 1 sebelum calon anggota mengisi data.

### 9.2 Redaksi Pernyataan & Persetujuan Pelamar (Consent Copy)
Teks persetujuan yang dicantumkan pada Bagian 1 Google Form:

> **Pemberitahuan & Persetujuan Pemrosesan Data Pribadi Calon Anggota**
>
> Dengan melanjutkan pengisian formulir ini, Anda menyatakan bahwa:
> 1. Seluruh data yang Anda berikan adalah benar, akurat, dan merupakan data milik Anda sendiri yang sah.
> 2. Anda memberikan persetujuan kepada Koperasi Kojaya untuk mengumpulkan, memverifikasi, mengenkripsi, dan memproses data pribadi Anda (termasuk NIK, nomor kontak, dan alamat) semata-mata untuk keperluan:
>    - Pendaftaran dan penerbitan nomor anggota resmi koperasi.
>    - Pembentukan akun otentikasi portal anggota Kojayaku via Google SSO.
>    - Penyaluran komunikasi, pemberitahuan resmi, dan layanan perkoperasian.
> 3. Koperasi Kojaya berkomitmen menjaga kerahasiaan data Anda dengan menerapkan enkripsi standar industri dan tata kelola pembatasan akses data berjenjang.
>
> *[Pertanyaan Konfirmasi]*:
> **"Apakah Anda menyetujui pemrosesan data pribadi Anda sesuai ketentuan di atas?"**
> Pilihan Wajib: `[X] Ya, Saya Menyetujui`

---

## 10. Keputusan Akun Google & Pengumpulan Email (Google Account Decision)

### 10.1 Analisis Fitur Google Form "Collect Email Addresses"
Pada Google Form terdapat opsi bawaan:
- *Verified*: Mengumpulkan email akun Google yang saat itu aktif login pada peramban secara otomatis.
- *Responder input*: Membuka kolom isian manual alamat email.

### 10.2 Keputusan Arsitektur: Rekomendasi Isian Manual Terverifikasi
Spesifikasi ONB-02 menetapkan bahwa:
1. **Identitas Google Form dan OAuth Kojaya Adalah Dua Sistem Terpisah**: Identitas sesi pengguna saat merespons Google Form **TIDAK DAPAT** diasumsikan secara otomatis menjadi Google OAuth `provider_id` di database Kojaya. Pengisian form dilakukan di domain Google Forms pihak ketiga tanpa keterikatan handshake OAuth langsung ke backend Kojaya.
2. **Kolom Isian Eksplisit Wajib Digunakan**: Alamat email wajib dikumpulkan melalui pertanyaan eksplisit bertipe Short Answer dengan validasi format email (`email`). Hal ini memastikan calon anggota secara sadar memasukkan alamat email Google yang memang diniatkan untuk login portal Kojayaku (menghindari kasus peramban bersama di kantor/keluarga yang sedang login akun Google orang lain).
3. **Dukungan Domain Google SSO**: Berdasarkan inspeksi kode `GoogleSsoService::isHostedDomainAllowed()`, Kojaya mendukung **seluruh akun Google valid** (baik domain `@gmail.com` pribadi maupun Google Workspace korporat berbasis Google Identity). Teks formulir tidak membatasi pelamar hanya pada `@gmail.com`.
4. **Status Saat Impor**: Email ini tetap didefinisikan sebagai **`NORMALIZED BOOTSTRAP EMAIL CANDIDATE`**. Verifikasi kepemilikan definitif terjadi saat callback OAuth login pertama kali (`email_verified = true`).

---

## 11. Daftar Field yang Wajib Dikecualikan (Strictly Excluded Fields)

Untuk menjaga integritas tata kelola data dan mencegah pengisian data prematur/liar, field-field berikut **DILARANG KERAS** dijadikan pertanyaan pada Google Form pendaftaran:

```text
┌─────────────────────────────────────┬──────────────────────────────────────────┐
│ KELOMPOK SIKLUS HIDUP              │ FIELD YANG DILARANG DI GOOGLE FORM       │
├─────────────────────────────────────┼──────────────────────────────────────────┤
│ 1. ADMIN_ENRICHMENT                 │ - member_number (Nomor Anggota KOP-XXX)  │
│    (Wewenang Eksklusif Admin)       │ - membership_type (Jenis AB / ALB)       │
│                                     │ - join_date (Tanggal Resmi Aktif)        │
│                                     │ - notes (Catatan Verifikasi Admin)       │
├─────────────────────────────────────┼──────────────────────────────────────────┤
│ 2. DERIVED_REFERENCE                │ - company_name (Nama Panjang PT)         │
│    (Diturunkan Sistem)              │ - department (Nama Departemen)           │
│                                     │ - employee_id (ID Database Pegawai)      │
├─────────────────────────────────────┼──────────────────────────────────────────┤
│ 3. SYSTEM_GENERATED                 │ - organization_id (Tenant Cabang)        │
│    (Kendali Sistem Aplikasi)        │ - status & validation_status             │
│                                     │ - role Spatie ('Anggota')                │
│                                     │ - timestamps & metadata enkripsi PII     │
├─────────────────────────────────────┼──────────────────────────────────────────┤
│ 4. POST_ONBOARDING_PROFILE          │ - birth_place (Tempat Lahir)             │
│    (Diisi Anggota Setelah Aktif)    │ - birth_date (Tanggal Lahir)             │
│                                     │ - job_title (Pekerjaan / Jabatan)        │
├─────────────────────────────────────┼──────────────────────────────────────────┤
│ 5. FINANCIAL_MIGRATION              │ - npwp (Nomor Pokok Wajib Pajak)         │
│    (Dialihkan ke Fase 9)            │ - bank_name (Nama Bank)                  │
│                                     │ - bank_account_number (No Rekening)      │
│                                     │ - bank_account_holder (Nama Pemilik Rek) │
│                                     │ - autodebit_method (Metode Autodebet)    │
└─────────────────────────────────────┴──────────────────────────────────────────┘
```

---

## 12. Alur Integrasi Respons Form ke Verifikasi Admin (Form &rarr; ONB-03 Mapping)

Alur penyerahan data respons pendaftaran pelamar ke meja verifikasi admin koperasi:

```text
Google Form Submission (8 Field Pelamar)
                  ↓
Google Sheets Respons (Respons Mentah Pendaftaran)
                  ↓
Tinjauan Administratif Staf Koperasi (ONB-03 Verification):
  [1] Admin memeriksa NIK & kelengkapan identitas
  [2] Admin memverifikasi NIP vs master employees (employees.employee_code)
  [3] Admin menentukan jenis_anggota (AB untuk karyawan / ALB untuk mitra)
  [4] Admin menetapkan tanggal_aktif (join_date)
  [5] Admin mengisi member_number (atau dibiarkan kosong untuk auto-generate)
  [6] Admin menambahkan catatan administratif (notes) jika relevan
                  ↓
Ekspor ke File Impor Kanonikal (CSV 12 Kolom Fase 2)
```

---

## 13. Pemetaan Respons Form ke Payload Impor 12 Kolom (12-Column Import Mapping)

Tabel berikut menunjukkan korespondensi presisi antara respons Google Form dengan 12 kolom kanonikal template CSV impor Fase 2 (`docs/onboarding/member-import-template.csv`):

| # | CSV Header Kanonikal | Sumber Data | Asal Nilai pada Google Form | Tanggung Jawab / Aturan Pengisian |
| -: | :--- | :--- | :--- | :--- |
| 1 | `member_number` | Admin Enrichment | *(Tidak ada di Form)* | Diisi Admin Koperasi (atau kosong &rarr; auto-gen sistem). |
| 2 | `full_name` | **Google Form** | Pertanyaan 1 (`Nama Lengkap`) | Nilai input pelamar (setelah `trim()`). |
| 3 | `email` | **Google Form** | Pertanyaan 2 (`Alamat Email Akun Google`) | Nilai input pelamar (`LOWER(TRIM(email))`). |
| 4 | `phone_number` | **Google Form** | Pertanyaan 3 (`Nomor WhatsApp`) | Nilai input pelamar (format standar `08...`). |
| 5 | `identity_number` | **Google Form** | Pertanyaan 4 (`NIK 16 Digit`) | Nilai input pelamar (wajib string 16 digit). |
| 6 | `gender` | **Google Form** | Pertanyaan 5 (`Jenis Kelamin`) | Hasil mapping controlled choice (`L` / `P`). |
| 7 | `company_code` | **Google Form** | Pertanyaan 6 (`Perusahaan Asal`) | Hasil mapping controlled choice (`IP` / `CDB` / `KOP`). |
| 8 | `employee_number` | **Google Form** | Pertanyaan 8 (`Nomor Induk Pegawai`)| Nilai input pelamar (Opsional; kosong jika tidak memiliki NIP). |
| 9 | `address` | **Google Form** | Pertanyaan 7 (`Alamat Domisili`) | Nilai input pelamar. |
| 10 | `membership_type` | Admin Enrichment | *(Tidak ada di Form)* | Diisi Admin Koperasi (Default `'AB'`, `'ALB'` untuk mitra). |
| 11 | `join_date` | Admin Enrichment | *(Tidak ada di Form)* | Diisi Admin Koperasi (Format `YYYY-MM-DD`, default tgl impor). |
| 12 | `notes` | Admin Enrichment | *(Tidak ada di Form)* | Diisi Admin Koperasi (Catatan verifikasi administratif). |

---

## 14. Batasan Deteksi Konflik & Duplikasi (Conflict & Duplicate Boundary)

Penting dipahami bahwa **Google Form BUKAN instrumen resolusi konflik final**. Google Form tidak memiliki akses basis data langsung untuk memvalidasi duplikasi secara real-time.

Aturan penanganan konflik identitas ditegakkan pada tahap hilir (*downstream enforcement*) sesuai prinsip **FAIL-CLOSED**:

1. **Email Duplikat**:
   - Google Form tidak dapat mendeteksi apakah email sudah pernah terdaftar di database.
   - Penegakan: Validator backend (ONB-04) wajib menolak baris impor (*reject row*) jika email telah terdaftar pada akun `users.email` lain. Dilarang melakukan *silent merge*.
2. **NIK Duplikat**:
   - Google Form hanya memvalidasi format (16 digit angka).
   - Penegakan: Validator backend (ONB-04) wajib menolak baris impor jika blind index NIK (`identity_number_bidx`) telah terdaftar pada anggota lain.
3. **NIP Tidak Cocok (Unresolved Employee Reference)**:
   - Jika pelamar mengisi NIP tetapi tidak ditemukan pada tabel `employees`, record masuk ke antrean verifikasi manual admin pada tugas ONB-03.
   - **DILARANG KERAS mencatat NIP yang gagal ter-resolve ke dalam kolom `notes`**.
4. **Google Identity Conflict**:
   - Jika akun Google (`provider_id`) bertabrakan saat login pertama kali, sistem langsung membatalkan proses login (*fail-closed*), mencatat *audit log*, dan mengarahkan ke peninjauan manual staf.

---

## 15. Kontrak Serah Terima ke Verifikasi Admin (ONB-03 Handoff Contract)

1. **Status Non-Aktif**: Pengisian dan penyerahan formulir (*form submission*) oleh pelamar **SAMA SEKALI TIDAK MENGHASILKAN ANGGOTA AKTIF**.
2. **Status Awal Sistem**: Data yang masuk berstatus `status = 'PENDING'` dan `validation_status = 'PENDING'`.
3. **Tidak Ada Role Otomatis**: Pelamar yang baru mengisi formulir **TIDAK MENDAPATKAN ROLE SPATIE `'Anggota'`**.
4. **Prinsip Maker-Checker**:
   - Verifikasi tahap 1 dilakukan oleh staf pemegang izin `verify_cooperative_member` (Admin Koperasi).
   - Persetujuan final tahap 2 dilakukan oleh pengurus pemegang izin `approve_cooperative_member` (Pengurus Koperasi).
   - Sesuai implementasi `MemberValidationService::assertApproverIsNotVerifier()`, staf yang memverifikasi data awal **dilarang merangkap** sebagai pengurus yang menyetujui final record anggota yang sama.

---

## 16. Keputusan Bisnis & Hukum yang Memerlukan Kebijakan Lanjutan (Open Decisions)

Dokumen ini mengidentifikasi beberapa keputusan operasional dan kebijakan yang memerlukan ketetapan formal dari manajemen Koperasi Kojaya:

1. **Kebijakan Masa Retensi Berkas Pelamar yang Ditolak (`BUSINESS / LEGAL POLICY REQUIRED`)**:
   - Diperlukan ketetapan masa retensi formal untuk penghapusan berkas pendaftaran pelamar yang ditolak atau tidak melengkapi verifikasi sesuai kepatuhan UU PDP (jangka waktu retensi, misalnya 30 atau 90 hari sebagai contoh ilustratif, sepenuhnya merupakan domain kebijakan hukum/bisnis yang belum ditetapkan dan bukan merupakan rekomendasi implementasi teknis).
2. **SOP Pemutakhiran Master Pegawai untuk Karyawan Baru (`OPERATIONAL DECISION REQUIRED`)**:
   - Penentuan alur kerja operasional apabila pelamar memiliki NIP namun belum terdaftar pada master data `employees` Kojaya (apakah admin koperasi meminta sinkronisasi master HR terlebih dahulu atau memproses pendaftaran dengan status `UNRESOLVED EMPLOYEE REFERENCE`).
3. **Kanal Notifikasi Penolakan / Permintaan Revisi (`OPERATIONAL DECISION REQUIRED`)**:
   - Penetapan standar kanal resmi untuk menghubungi calon anggota saat data pendaftaran membutuhkan perbaikan (WhatsApp resmi koperasi atau notifikasi email otomatis).
