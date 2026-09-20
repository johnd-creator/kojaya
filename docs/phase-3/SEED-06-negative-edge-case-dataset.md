# SEED-06: Negative & Edge-Case Dataset

## 1. Ringkasan Eksekutif

Dokumen ini menetapkan spesifikasi, arsitektur isolasi, dan implementasi teknis untuk dataset kasus uji negatif dan kondisi batas (*edge cases*) pada Phase 3 KojayaPro dan Kojayaku. Implementasi ini merealisasikan klausul pengujian negatif dari [SEED-01 Test Data Contract](./SEED-01-test-data-contract.md), menjaga semantik organisasi [SEED-02R1 Organization Semantics](./SEED-02-master-reference-data.md), serta memastikan data baseline lokal yang telah dibentuk oleh [SEED-03](./SEED-03-user-member-personas.md), [SEED-04](./SEED-04-member-lifecycle-dataset.md), dan [SEED-05](./SEED-05-synthetic-financial-data.md) tetap steril dari data korup atau tidak valid.

Deliverable utama dari tugas ini meliputi:
1. Seeder uji terisolasi [`CooperativeEdgeCaseFixtureSeeder`](../../database/seeders/CooperativeEdgeCaseFixtureSeeder.php) yang diklasifikasikan secara ketat sebagai `TEST_ONLY_INVALID_FIXTURE`.
2. Persona uji anomali **P11 (`DEV-KOP-011` / `BLOCKED_UNKNOWN`)** dengan perilaku keamanan *fail-closed* di seluruh antarmuka web dan API.
3. Kumpulan berkas CSV fixture negatif di `tests/Fixtures/SEED-06/` untuk pengujian komprehensif `MemberImportValidator`.
4. State factory pembantu untuk pengujian boundary: `LoanFactory::defaulted()` dan `MemberStoreAccountFactory::suspended()`.
5. Suite pengujian verifikasi mendalam di [`CooperativeEdgeCaseFixtureSeederTest`](../../tests/Feature/SEED06/CooperativeEdgeCaseFixtureSeederTest.php) serta pembaruan uji keselamatan statis dan dinamis pada [`SeederSafetyStaticAnalysisTest`](../../tests/Feature/SeederSafetyStaticAnalysisTest.php) dan [`DatabaseSeederSafetyTest`](../../tests/Feature/DatabaseSeederSafetyTest.php).

---

## 2. Klasifikasi Seeder & Perlindungan Lingkungan (Environment Isolation)

Sesuai kontrak keselamatan SEED-01 dan review SEED-01R1, data pengujian yang mengandung status anomali atau tidak valid **dilarang keras** mencemari database pengembangan lokal default (`local`/`development`) maupun lingkungan publik (`production`, `staging`, `qa`).

### A. Klasifikasi Seeder
`CooperativeEdgeCaseFixtureSeeder` diklasifikasikan sebagai:
```text
TEST_ONLY_INVALID_FIXTURE
```

### B. Aturan Guard Lingkungan
Seeder ini dilengkapi guard mutlak pada method `run()`:
```php
if (! in_array(config('app.env'), ['testing', 'playwright'], true) && ! app()->environment(['testing', 'playwright'])) {
    throw new \LogicException('CooperativeEdgeCaseFixtureSeeder is only available in testing or playwright environments to prevent polluting dev/production databases with corrupt states.');
}
```
Jika dieksekusi di luar lingkungan `testing` atau `playwright` (seperti `production`, `staging`, `qa`, `local`, `development`), seeder ini langsung melempar `\LogicException`.

### C. Pemisahan dari `DatabaseSeeder`
`CooperativeEdgeCaseFixtureSeeder` **TIDAK DIDATARKAN** di dalam [`DatabaseSeeder`](../../database/seeders/DatabaseSeeder.php). Database lokal default yang dibentuk melalui `php artisan db:seed` hanya memuat data referensi kanonikal dan persona sehat (P01-P10, P12-P13), menjamin lingkungan dev tetap bersih dan dapat dioperasikan secara wajar.

---

## 3. Spesifikasi Persona P11: `BLOCKED_UNKNOWN`

Persona P11 memodelkan kondisi anomali di mana data keanggotaan terblokir atau berada pada status inaktif yang tidak dikenal, dengan profil deterministik tanpa dependensi Faker:

| Atribut | Nilai Kanonikal |
| :--- | :--- |
| **No. Anggota** | `DEV-KOP-011` (tersinkronisasi pada `member_no` dan `no_anggota`) |
| **Nama** | `Seed Member Blocked` |
| **Email** | `seed.member.blocked@kojaya.test` |
| **Password** | `password` (terenkripsi `Hash::make('password')`) |
| **Peran (Role)** | `Anggota` (organisasi `KOP-001`) |
| **Status Keanggotaan** | `status = INACTIVE` |
| **Status Validasi** | `validation_status = INACTIVE` |
| **Lifecycle State** | `BLOCKED_UNKNOWN` |
| **NIK** | `3171012301900011` |
| **Tanggal Jangkar** | `2026-06-01 08:00:00` |

### A. Perilaku Keamanan Fail-Closed
1. **Web Entrypoint:**
   - Permintaan ke `/dashboard` atau `/member/onboarding` oleh P11 dicegat oleh middleware otorisasi dan mengembalikan respons **HTTP 403 Forbidden**.
2. **Mobile / API Login:**
   - Permintaan autentikasi `POST /api/auth/login` dengan kredensial P11 ditolak dengan status **HTTP 403 Forbidden**.
   - Respons terstruktur mengembalikan:
     ```json
     {
       "success": false,
       "message": "Akun anggota Anda tidak aktif. Silakan hubungi pengurus koperasi.",
       "data": {
         "lifecycle_experience": "BLOCKED_UNKNOWN",
         "status": "INACTIVE"
       }
     }
     ```
   - **Zero Token:** Dipastikan tidak ada Sanctum token yang diterbitkan untuk P11.

### B. Isolasi Finansial Mutlak
P11 terisolasi secara mutlak dari modul transaksi finansial:
- **0** tagihan simpanan (`cooperative_dues_invoices`)
- **0** pembayaran simpanan (`cooperative_payments`)
- **0** kwitansi (`cooperative_receipts`)
- **0** entri buku besar simpanan/pinjaman (`cooperative_ledger_entries`)
- **0** akun toko anggota (`member_store_accounts`)
- **0** pinjaman koperasi (`loans`)
- **0** transaksi kasir POS (`pos_transactions`)

### C. Idempotensi & Batasan Multi-Tenant
- Eksekusi ganda `CooperativeEdgeCaseFixtureSeeder` bersifat idempoten: tepat menghasilkan 1 User dan 1 Anggota P11 tanpa duplikasi.
- Persona P14 dan P15 tetap **nihil** di seluruh sistem.
- Entitas anak usaha KBU-001 tetap memiliki **0 anggota koperasi**, menjaga integritas semantik SEED-02R1.

---

## 4. Fixture Berkas CSV Negatif (Member Import Validation)

Tiga berkas CSV uji negatif ditempatkan di direktori `tests/Fixtures/SEED-06/` untuk menguji ketahanan `MemberImportValidator`:

### A. `member-import-duplicate-batch.csv`
Menguji deteksi duplikasi intra-batch (di dalam berkas itu sendiri) dan ekstra-batch (terhadap database):
- Baris 2 & 3: Duplikasi email dalam berkas yang sama (`DUPLICATE_EMAIL_BATCH`).
- Baris 4 & 5: Duplikasi NIK dalam berkas yang sama (`DUPLICATE_IDENTITY_NUMBER_BATCH`).
- Baris 6 & 7: Duplikasi Nomor Anggota dalam berkas yang sama (`DUPLICATE_MEMBER_NUMBER_BATCH`).
- Baris 8: Email menabrak anggota yang sudah ada di database (`EMAIL_ALREADY_EXISTS`).
- Baris 9: NIK menabrak anggota yang sudah ada di database (`IDENTITY_NUMBER_ALREADY_EXISTS`).
- Baris 10: Nomor Anggota menabrak anggota yang sudah ada di database (`MEMBER_NUMBER_ALREADY_EXISTS`).

### B. `member-import-malformed.csv`
Menguji penolakan data baris yang rusak atau tidak sesuai format:
- Field wajib kosong: Nama kosong, email kosong, NIK kosong (`MISSING_REQUIRED_FIELD`).
- Format email salah: `not-an-email` (`INVALID_EMAIL_FORMAT`).
- Format NIK salah: 12 digit (bukan 16 digit) atau mengandung karakter non-numerik (`INVALID_IDENTITY_NUMBER_FORMAT`).
- Nilai enum di luar kontrol: Agama tidak dikenal, status kawin tidak sah (`INVALID_CONTROLLED_VALUE`).
- Format tanggal salah: Format tidak sesuai standar ISO/Y-m-d (`INVALID_DATE_FORMAT`).

### C. `member-import-invalid-header.csv`
Menguji kegagalan validasi berkas pada tingkat struktur:
- Header wajib seperti `nik`, `nama_lengkap`, atau `email` diubah namanya atau dihilangkan (`INVALID_HEADER`).
- Validator menolak seluruh berkas secara atomik tanpa memproses baris data.

### D. Redaksi Data Sensitif (PII Redaction)
`MemberImportValidator` memastikan bahwa dalam kegagalan validasi, representasi mentah NIK pada payload error diredaksi menjadi string `[REDACTED]`. Hal ini mencegah paparan data kependudukan sensitif dalam log aplikasi atau respons API publik.

---

## 5. Matriks Kondisi Batas & Pengujian Negatif Lintas Modul

Selain persona P11 dan impor CSV, dataset edge case ini mencakup skenario pengujian kondisi batas berikut:

### A. Kegagalan Aman Konflik Google SSO (Fail-Closed)
- Jika pengguna pihak ketiga mencoba masuk menggunakan `provider_id` milik persona P12 (`seed.member.google@kojaya.test`), `MemberGoogleSsoMatchingService` menolak asosiasi baru dan mempertahankan kepemilikan akun P12.
- Jika pengguna pihak ketiga menggunakan email P12 dengan `provider_id` Google baru yang belum terikat, sistem mengembalikan kode error `MEMBER_USER_CONFLICT` dengan `success = false`, tanpa mengizinkan pengambilalihan akun (*account takeover*).

### B. Penolakan Fitur Aktif untuk Anggota Non-Aktif (P06)
- Persona P06 (`DEV-KOP-006` / `WAITING_VERIFICATION`) yang mencoba mengakses endpoint anggota aktif (misalnya `GET /api/v1/member/dues/invoices`) ditolak dengan status **HTTP 403 Forbidden** dan kode error terstruktur `MEMBER_NOT_ACTIVE`.

### C. Batas Limit Kredit Toko (Store Credit Boundary & Over-limit)
- Persona P12 memiliki plafon kredit Rp 500.000 dengan saldo saat ini **-Rp 450.000** (sisa kredit tersedia Rp 50.000).
- Transaksi belanja toko sebesar Rp 60.000 ditolak dengan `ValidationException` (`INSUFFICIENT_STORE_CREDIT`).
- Transaksi dibatalkan secara atomik: saldo P12 tetap utuh pada angka -Rp 450.000 dan tidak ada entri mutasi baru yang dicatat pada buku besar toko.

### D. Akun Toko Berstatus Ditangguhkan (Suspended Store Account)
- Ditambahkan state `MemberStoreAccountFactory::suspended()` dengan `status = SUSPENDED`.
- Akun toko yang ditangguhkan menolak seluruh transaksi kredit baru.

### E. Pinjaman Koperasi Macet (Defaulted Loan State)
- Ditambahkan state `LoanFactory::defaulted()` dengan atribut deterministik:
  - `status = DEFAULTED`
  - `outstanding_amount > 0`
  - Catatan tunggakan angsuran valid untuk keperluan pengujian algoritma penagihan dan kolektibilitas.

### F. Penegakan Batas Organisasi Lintas Tenant (Cross-Org Isolation)
- Pengguna atau pengurus pada organisasi `KOP-001` yang berupaya mengakses data milik organisasi terisolasi `ISO-999`:
  - Menggunakan `OrganizationScopeService::assertVisible` memicu `AuthorizationException`.
  - Menggunakan `OrganizationScopeService::resolveVisible` memicu `ModelNotFoundException`.

---

## 6. Kesiapan Menuju SEED-07

Dengan tuntasnya dataset negatif dan edge-case ini, seluruh kebutuhan variasi data uji (Master Reference SEED-02, Persona SEED-03, Lifecycle SEED-04, Financial SEED-05, dan Edge Cases SEED-06) telah lengkap. Seluruh dataset siap dikonsolidasikan ke dalam perangkat otomasi reset/reseed deterministik pada **SEED-07 (Deterministic Reset / Reseed Tooling)**.
