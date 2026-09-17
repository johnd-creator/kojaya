# Spesifikasi & Implementasi DEV Member Import (ONB-06)

Dokumen ini adalah **spesifikasi teknis, arsitektur, dan dokumentasi implementasi resmi untuk DEV Member Import (ONB-06)** pada ekosistem **Kojaya** (`johnd-creator/kojaya`). Modul ini merupakan tahap pertama dalam peta jalan onboarding yang diizinkan untuk **mempersistensikan data anggota kanonikal** ke tabel `cooperative_members` secara transaksional (*all-or-nothing*).

---

## 1. Tujuan & Filosofi Desain (Purpose & Philosophy)

1. **Transactional All-or-Nothing Persistence**: Mengimpor batch data anggota dari berkas CSV kanonikal 12 kolom langsung ke basis data DEV. Jika terdapat 1 baris gagal atau terjadi kendala sistem, seluruh batch dibatalkan seketika (*zero partial persistence*).
2. **Revalidasi Ganda (Double Revalidation)**: Data tidak pernah dipercaya begitu saja dari browser pratinjau ONB-05. Berkas CSV divalidasi ulang di backend sebelum transaksi (*preflight*), dan divalidasi ulang sekali lagi secara otoritatif di dalam transaksi database setelah memperoleh kunci konkurensi.
3. **Gerbang Eksekusi DEV (DEV Execution Gate)**: Fitur eksekusi impor dilindungi konfigurasi eksplisit `cooperative.member_import_execution_enabled` dengan default `false`. Penggabungan kode ke `main` tidak akan mengaktifkan eksekusi secara tidak sengaja di production.
4. **Bukti Pratinjau Tahan Manipulasi (Tamper-Resistant Preview Proof)**: Eksekusi wajib menyertakan token bukti pratinjau bertanda tangan/terenkripsi dari server yang mengikat hash SHA-256 berkas, organisasi target, tanggal impor, dan masa berlaku (TTL).
5. **Pemisahan Akun & Finansial (No Users & No Finance)**: Tahap ini HANYA membuat rekaman `cooperative_members` berstatus `PENDING`. Tidak ada pembuatan akun `users`, akun media sosial, kredensial password, simpanan pokok/wajib, saldo awal, maupun mutasi buku besar.

---

## 2. Alur Orkestrasi Eksekusi (Execution Workflow Architecture)

```
[Operator (Admin Koperasi)]
        │
        ▼ (Upload CSV pada Halaman Pratinjau ONB-05)
[MemberImportPreviewController@preview] ─── Validasi berkas & periksa kesiapan
        │
        ├─ Jika 100% VALID & Persistable:
        ▼
[PreviewProofService::generate()] ─── Terbitkan token preview_proof (AES-256-CBC)
        │
        ▼
[Tampilan Antarmuka (ImportPreview.vue)] ─── Tombol "Import ke DEV" aktif (jika DEV gate on)
        │
        ▼ (Klik "Import ke DEV" & Konfirmasi Dialog Modal)
[POST /cooperative/members/import/execute] (ExecuteMemberImportRequest)
        │
        ├── 1. Periksa Gerbang Fitur: config('cooperative.member_import_execution_enabled') === true
        ├── 2. Otorisasi Pengguna: can:manage_cooperative_member & policy import (Opsi C)
        ├── 3. Resolusi Batas Tenant: OrganizationScopeService::resolveTargetOrganization()
        ├── 4. Verifikasi Bukti: PreviewProofService::verify(proof, file_sha256, org_id, date)
        │
        ▼
[MemberImportExecutionService::execute()]
        │
        ├── 5. Validasi A (Preflight Revalidation di luar transaksi)
        │      MemberImportValidator::validateFile() -> Gagal? Throw Exception (Zero DB writes)
        │
        ▼
   [DB::transaction()]
        │
        ├── 6. Concurrency Guard: PostgreSQL Advisory Lock (pg_advisory_xact_lock)
        │
        ├── 7. Validasi B (Commit-Time Revalidation di dalam transaksi)
        │      MemberImportValidator::validateFile() -> Terjadi konflik baru? Rollback
        │
        ├── 8. Alokasi & Reservasi Nomor Anggota:
        │      - Nomor disediakan (supplied): Pertahankan nilai kanonikal
        │      - Nomor kosong: Generate KOP-### sekuensial unik tanpa benturan
        │
        ├── 9. Persistensi Anggota:
        │      Membuat rekaman CooperativeMember dengan status PENDING / PENDING
        │      Field PII (identity_number) dienkripsi otomatis via mutator model
        │
        ├── 10. Audit Wajib Atomik:
        │       AuditLogService::log('member.import.completed', 'cooperative', ...)
        │       (Gagal catat audit? Rollback seluruh batch)
        │
        ▼
   [COMMIT TRANSAKSI]
        │
        ▼
[Response: Ringkasan Impor Aman (Non-PII) -> Redirect dengan Flash / JSON]
```

---

## 3. Gerbang Eksekusi DEV (DEV Execution Gate)

Sesuai arsitektur onboarding Kojaya, eksekusi persistensi anggota pada tahap ONB-06 dikhususkan untuk lingkungan DEV/staging. Gerbang ini dikendalikan oleh:

- **File Konfigurasi**: [`config/cooperative.php`](file:///home/john-d/Pictures/kojaya/config/cooperative.php)
- **Kunci Konfigurasi**: `cooperative.member_import_execution_enabled`
- **Variabel Lingkungan**: `COOPERATIVE_MEMBER_IMPORT_EXECUTION_ENABLED`
- **Nilai Bawaan (Default)**: `false` (Aman / Fail-Closed)

### Perilaku Saat Gerbang Nonaktif (`false`):
- Halaman pratinjau `GET /cooperative/members/import` tetap dapat dibuka normal.
- Simulasi `POST /cooperative/members/import/preview` tetap berfungsi penuh.
- Tombol antarmuka pada banner kesiapan menampilkan status *"Eksekusi Dinonaktifkan (DEV Gate Nonaktif)"*.
- Upaya tembak langsung `POST /cooperative/members/import/execute` ditolak seketika dengan **HTTP 403 Forbidden** tanpa menyentuh basis data.

---

## 4. Otorisasi & Keputusan Kebijakan RBAC (Opsi C)

Sesuai kesepakatan perancangan:
- **Feature Owner**: Role `Admin Koperasi` tetap menjadi pemilik alur operasional onboarding anggota.
- **Dedicated Batch Import Permission**: Eksekusi persistensi batch anggota baru mewajibkan izin khusus `import_cooperative_member_batch` (`PermissionEnum::COOPERATIVE_MEMBER_IMPORT`).
- **Pemisahan Hak Akses**: Kepemilikan izin `manage_cooperative_member` saja **TIDAK CUKUP** untuk mengeksekusi impor persistensi ke database. Alur pratinjau/simulasi (dry-run) tetap dapat diakses dengan `manage_cooperative_member`, namun eksekusi persistensi mutlak membutuhkan `import_cooperative_member_batch`.
- **Kebijakan PII & Opsi C**: Role `Admin Koperasi` secara sengaja diberikan izin `import_cooperative_member_batch` tanpa diberikan izin penyuntingan PII umum (`update_cooperative_member_pii`), yang tetap berada di bawah wewenang `Pengurus Koperasi`.
- **Batch Onboarding Gate**: Otorisasi eksekusi persistensi batch anggota baru berstatus `PENDING` diatur melalui method policy [`CooperativeMemberPolicy::executeImport()`](file:///home/john-d/Pictures/kojaya/app/Policies/CooperativeMemberPolicy.php) dan alias [`import()`](file:///home/john-d/Pictures/kojaya/app/Policies/CooperativeMemberPolicy.php):
  ```php
  public function executeImport(User $user): bool
  {
      if (! $this->can($user, PermissionEnum::COOPERATIVE_MEMBER_IMPORT->value)) {
          return false;
      }

      try {
          app(OrganizationScopeService::class)->visibilityFor($user, PermissionEnum::COOPERATIVE_VIEW_ALL->value);
          return true;
      } catch (AuthorizationException) {
          return false;
      }
  }

  public function import(User $user): bool
  {
      return $this->executeImport($user);
  }
  ```
- **Organization Scope Enforcement**: Izin `import_cooperative_member_batch` adalah syarat perlu namun belum cukup (necessary but not sufficient); pengguna harus memiliki otorisasi cakupan organisasi yang sah (`OrganizationScopeService`). Upaya eksekusi untuk organisasi di luar wewenang pengguna ditolak seketika dengan **HTTP 403 Forbidden** (fail-closed).
- Kebijakan ini memungkinkan `Admin Koperasi` mendaftarkan calon anggota massal ke tahap `PENDING` secara mandiri tanpa membuka izin mutasi PII anggota aktif secara umum.

---

## 5. Bukti Pratinjau Tahan Manipulasi (Preview Proof Token)

Untuk memastikan operator tidak dapat mem-bypass langkah validasi pratinjau:
1. **Penerbitan Token**: Saat simulasi pratinjau ONB-05 menghasilkan status **READY FOR IMPORT**, [`PreviewProofService`](file:///home/john-d/Pictures/kojaya/app/Services/Cooperative/PreviewProofService.php) menerbitkan token terenkripsi menggunakan `Crypt::encryptString()`.
2. **Kandungan Metadata Non-PII**:
   - `file_sha256`: Hash SHA-256 berkas CSV yang dipratinjau.
   - `organization_id`: UUID organisasi sasaran yang sah.
   - `import_date`: Tanggal pendaftaran rujukan.
   - `issued_at` & `expires_at`: Masa berlaku bukti (TTL default 1.800 detik / 30 menit).
   - **TIDAK ADA DATA PII**: NIK, nama, email, nomor HP, maupun isi baris CSV tidak pernah dimasukkan ke dalam token.
3. **Verifikasi Eksekusi**:
   - Token didekripsi dan diverifikasi masa berlakunya.
   - Berkas CSV yang dikirimkan pada permintaan eksekusi di-hash ulang (`hash_file('sha256')`).
   - Jika hash berkas, organisasi, atau tanggal berbeda dari token pratinjau, permintaan ditolak dengan kode `FILE_CHANGED_SINCE_PREVIEW` atau `PREVIEW_PROOF_INVALID`.

---

## 6. Pemetaan Persistensi Kanonikal (Canonical Persistence Mapping)

Tepat 12 kolom kanonikal dipetakan ke atribut model [`CooperativeMember`](file:///home/john-d/Pictures/kojaya/app/Models/CooperativeMember.php):

| Kolom Kanonikal CSV | Atribut Database | Catatan Pemetaan |
| :--- | :--- | :--- |
| `member_number` | `no_anggota`, `member_no` | Nilai input dipertahankan persis; jika kosong, di-generate otomatis (`KOP-###`). Disinkronkan ke kedua kolom. |
| `full_name` | `name`, `nama_anggota` | Maksimal 100 karakter kanonikal. Disimpan identik di kedua kolom. |
| `email` | `email` | Alamat email ter-normalisasi huruf kecil. |
| `phone_number` | `phone`, `no_telp` | Nomor telepon format nasional / E.164. Disinkronkan ke kedua kolom. |
| `identity_number` | `identity_number` | NIK 16 digit. Mutator model otomatis mengenkripsi ke `identity_number_enc` dan membuat blind index `identity_number_bidx`. |
| `gender` | `jenis_kelamin` | Karakter `L` atau `P`. |
| `company_code` | `kategori` | Kode perusahaan sponsor (`IP`, `CDB`, atau `KOP`). |
| `employee_number` | `employee_id` | **HANYA ID PEGAWAI TER-RESOLVE**. NIP mentah tidak pernah disimpan langsung; jika kosong disimpan `null`. NIP tak ter-resolve menolak seluruh batch. |
| `address` | `address` | Alamat domisili lengkap. |
| `membership_type` | `jenis_anggota` | Tipe keanggotaan (`AB` atau `ALB`). |
| `join_date` | `tanggal_aktif`, `joined_at` | Tanggal ISO `YYYY-MM-DD`. Disinkronkan ke kedua kolom. |
| `notes` | `notes` | Catatan administratif operasional. |
| *(Konteks Organisasi)* | `organization_id` | UUID organisasi resmi hasil resolusi `OrganizationScopeService`. |
| *(Status Awal)* | `status` | `CooperativeMember::VALIDATION_PENDING` (`'PENDING'`). |
| *(Status Validasi)* | `validation_status` | `CooperativeMember::VALIDATION_PENDING` (`'PENDING'`). |
| *(Akun Pengguna)* | `user_id` | `null` (Dilarang membuat akun pengguna pada ONB-06). |

---

## 7. Penomoran Anggota & Strategi Konkurensi (Member Number Concurrency)

1. **Nomor Terisi (Supplied)**: Format kanonikal dinormalisasi dan dipertahankan apa adanya.
2. **Nomor Kosong (Auto-Generated)**:
   - Menggunakan pola kanonikal `KOP-###` secara otoritatif via [`MemberNumberGenerator::reserveBatch()`](file:///home/john-d/Pictures/kojaya/app/Services/Cooperative/MemberNumberGenerator.php).
   - Di dalam transaksi basis data, generator memindai nilai maksimum yang ada (termasuk rekaman *soft-deleted*) dan mengalokasikan urutan nomor baru secara sekuensial.
   - Generator otomatis mendeteksi dan melewati nomor anggota yang telah disediakan secara manual dalam batch yang sama untuk mencegah benturan internal.
3. **Penguncian Konkurensi (Concurrency Lock)**:
   - Penomoran anggota mengikuti domain sekuensial global `KOP-###` dengan *unique database constraints*.
   - Pada PostgreSQL, transaksi mengeksekusi *transaction-scoped advisory lock*:
     ```sql
     SELECT pg_advisory_xact_lock(crc32('cooperative_members_import_lock'));
     ```
   - Penguncian ini memastikan dua proses impor yang berjalan bersamaan akan mengantre secara teratur. Transaksi kedua baru akan membaca nomor anggota terakhir setelah transaksi pertama berhasil melakukan commit.
   - Kunci dilepas secara otomatis oleh engine database saat transaksi melakukan commit atau rollback.
   - Teruji secara nyata melalui pengujian multi-proses PostgreSQL pada [`MemberImportConcurrencyTest`](file:///home/john-d/Pictures/kojaya/tests/Feature/Cooperative/MemberImportConcurrencyTest.php).
   - Kolom `no_anggota` dan `member_no` dilindungi oleh *unique index constraint* di level basis data sebagai pengaman lapis terakhir.

---

## 8. Jaminan Atomisitas, Sanitasi Error & Audit Wajib (Atomic Transaction & Mandatory Audit)

1. **All-or-Nothing Policy**: Seluruh baris data diproses dalam satu cakupan `DB::transaction()`.
2. **Rollback Menyeluruh**:
   - Jika terjadi kegagalan validasi pada salah satu baris,
   - Jika terdapat baris berstatus `MANUAL_REVIEW`,
   - Jika terjadi exception/error database pada baris mana pun (misal baris ke-70 dari 100),
   - Atau jika pencatatan audit log gagal,
   maka transaksi dibatalkan sepenuhnya dan **TIDAK ADA SATU PUN ANGGOTA** yang tersimpan di basis data.
3. **Sanitasi Error Pengguna**:
   - Kegagalan infrastruktur tingkat rendah (seperti kegagalan koneksi audit, constraint SQLSTATE, atau driver DB) tidak pernah diekspos ke browser atau respons JSON/session.
   - Pesan error dipetakan secara aman ke pesan statis generik: `"Gagal menyelesaikan impor anggota. Tidak ada data yang disimpan."` dengan tetap mempertahankan exception asli pada parameter `previous` untuk diagnostik log internal server.
3. **Pencatatan Audit Wajib (Mandatory Batch Audit)**:
   - Aksi: `member.import.completed`
   - Modul: `cooperative`
   - Metadata tersimpan:
     - `import_id`: UUID unik korelasi impor.
     - `organization_id`: Organisasi target.
     - `import_date`: Tanggal pendaftaran rujukan.
     - `file_sha256`: Hash SHA-256 berkas CSV.
     - `total_rows`: Total baris data.
     - `imported_count`: Jumlah anggota terbuat.
     - `generated_member_number_count`: Jumlah nomor anggota yang di-generate.
     - `supplied_member_number_count`: Jumlah nomor anggota yang dipertahankan.
   - **Jaminan Privasi Audit**: Metadata audit **TIDAK PERNAH** memuat data PII mentah (tidak ada NIK, daftar email, nomor HP, maupun alamat).

---

## 9. Larangan Eksplisit Efek Samping (Strictly Forbidden Side Effects)

Modul ONB-06 mematuhi batas arsitektur secara mutlak:
- ❌ **TIDAK ADA Akun User**: Tidak memanggil `User::create()`.
- ❌ **TIDAK ADA Akun Sosial**: Tidak memanggil `SocialAccount::create()` atau `MemberSocialAccount::create()`.
- ❌ **TIDAK ADA Penugasan Role**: Tidak memanggil `assignRole()` atau `syncRoles()`.
- ❌ **TIDAK ADA Password**: Tidak membuat password default/sementara apa pun.
- ❌ **TIDAK ADA Mutasi Finansial**: Tidak membuat tagihan iuran pokok/wajib, saldo awal, akun kredit toko (*store credit*), SHU, maupun entri buku besar (*ledger*).
- ❌ **TIDAK ADA Penyimpanan File Permanen**: Tidak menyimpan berkas CSV ke disk storage lokal/publik (`Storage::put`).
- ❌ **TIDAK ADA Modifikasi Skema**: Tidak menambahkan migrasi basis data baru.

---

## 10. Integrasi Lanjutan (Handoff to ONB-03 & ONB-07)

1. **Serah Terima ke ONB-03 (Admin Verification Workflow)**:
   - Anggota hasil impor berada pada status `PENDING` dengan status validasi `PENDING`.
   - Data siap diverifikasi oleh Admin Koperasi melalui antarmuka verifikasi anggota sebelum disetujui final oleh Pengurus Koperasi.
2. **Serah Terima ke ONB-07 (Google SSO Member Matching)**:
   - Data anggota tersimpan lengkap dengan alamat email kanonikal dan NIK terenkripsi.
   - Penautan akun pengguna melalui Google SSO akan dilakukan secara terpisah pada tahap ONB-07 saat anggota melakukan login pertama kali.

---

## 11. Inventaris Rute Lengkap (Route Inventory)

| Metode | URI | Nama Rute | Controller Tindakan | Middleware & Otorisasi |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/cooperative/members/import` | `cooperative.members.import` | `MemberImportPreviewController@index` | `can:manage_cooperative_member` |
| `POST` | `/cooperative/members/import/preview` | `cooperative.members.import.preview` | `MemberImportPreviewController@preview` | `can:manage_cooperative_member` |
| `GET` | `/cooperative/members/import/template` | `cooperative.members.import.template` | `MemberImportPreviewController@downloadTemplate` | `can:manage_cooperative_member` |
| `POST` | `/cooperative/members/import/execute` | `cooperative.members.import.execute` | `MemberImportPreviewController@execute` | `can:manage_cooperative_member`, `can:import,CooperativeMember` |

---

## 12. Panduan Verifikasi Pengujian (Verification Commands)

```bash
# 1. Menjalankan seluruh pengujian fitur eksekusi impor ONB-06 (43 skenario)
php artisan test --compact tests/Feature/Cooperative/MemberImportExecutionTest.php

# 2. Menjalankan pengujian unit generator nomor anggota kanonikal (9 skenario)
php artisan test --compact tests/Unit/MemberNumberGeneratorTest.php

# 3. Menjalankan regresi pengujian pratinjau ONB-05
php artisan test --compact tests/Feature/Cooperative/MemberImportPreviewTest.php

# 4. Menjalankan regresi validator kanonikal ONB-04
php artisan test --compact tests/Feature/Cooperative/MemberImportValidatorTest.php

# 5. Menjalankan pengujian konkurensi multi-proses nyata PostgreSQL
vendor/bin/phpunit --configuration phpunit.pgsql.xml --testsuite PostgreSQLConcurrency

# 6. Memverifikasi kelayakan formatting kode PHP
vendor/bin/pint --dirty --test

# 7. Memeriksa linting & formatting komponen antarmuka Vue
npx eslint resources/js/pages/Cooperative/Members/ImportPreview.vue
npx prettier --check resources/js/pages/Cooperative/Members/ImportPreview.vue

# 8. Membangun bundle frontend Vite
npm run build

# 9. Memverifikasi integritas cakupan UI Audit
php artisan --env=playwright ui-audit:coverage --no-interaction
```
