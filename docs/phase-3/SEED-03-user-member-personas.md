# SEED-03: User & Member Persona Seeder

## 1. Ringkasan Eksekutif

Dokumen ini mendefinisikan implementasi teknis seeder persona pengguna dan anggota non-produksi untuk Phase 3 KojayaPro dan Kojayaku, merealisasikan spesifikasi kontrak data pengujian yang ditetapkan pada [SEED-01 Test Data Contract](./SEED-01-test-data-contract.md) dan topologi organisasi dari [SEED-02 Master Reference Data](./SEED-02-master-reference-data.md).

Implementasi [`CooperativePersonaSeeder`](../../database/seeders/CooperativePersonaSeeder.php) menyediakan kumpulan persona login deterministik non-produksi yang siap digunakan untuk pengujian fungsional modul-modul Phase 4 (auth web Fortify, API mobile Sanctum, siklus onboarding anggota, transaksi simpanan/pinjaman, dan kasir POS) tanpa bergantung pada fixture acak.

---

## 2. Matriks Persona Kanonikal (Canonical Persona Matrix)

Seluruh akun persona menggunakan kata sandi deterministik: `password`.
Anchor waktu kanonikal yang digunakan adalah `2026-06-01 08:00:00` (tanggal bergabung `2026-06-01`).

### A. Persona Staf & Manajemen Koperasi (P01 - P05)

| ID | Nama Persona | Email | Peran Spatie | Profil Anggota (`CooperativeMember`) | Organisasi |
| :--- | :--- | :--- | :--- | :---: | :---: |
| **P01** | Seed System Admin | `seed.system.admin@kojaya.test` | `System Admin` | **TIDAK ADA** | `KOP-001` |
| **P02** | Seed Pengurus Koperasi | `seed.pengurus@kojaya.test` | `Pengurus Koperasi` | **TIDAK ADA** | `KOP-001` |
| **P03** | Seed Manajer Koperasi | `seed.manajer@kojaya.test` | `Manajer Koperasi` | **TIDAK ADA** | `KOP-001` |
| **P04** | Seed Admin Koperasi | `seed.admin.kop@kojaya.test` | `Admin Koperasi` | **TIDAK ADA** | `KOP-001` |
| **P05** | Seed Kasir Koperasi | `seed.kasir@kojaya.test` | `Kasir Koperasi` | **TIDAK ADA** | `KOP-001` |

*Catatan:* Sesuai prinsip *separation of concerns*, akun staf dan manajemen operasional koperasi tidak memiliki profil anggota koperasi (`CooperativeMember`), mencegah konflik otorisasi antara hak akses staf dan hak akses anggota biasa.

### B. Persona Anggota Koperasi (P06 - P10, P12, P13)

| ID | Nama Persona | Email | No. Anggota | NIK Sintetis | Status Model | Status Validasi | Derived Lifecycle Experience | Google SSO |
| :--- | :--- | :--- | :--- | :--- | :---: | :---: | :--- | :---: |
| **P06** | Seed Member Waiting | `seed.member.waiting@kojaya.test` | `DEV-KOP-006` | `3174990000000006` | `PENDING` | `PENDING` | `WAITING_VERIFICATION` | Tidak |
| **P07** | Seed Member Review | `seed.member.review@kojaya.test` | `DEV-KOP-007` | `3174990000000007` | `PENDING` | `PENDING_VALIDATION` | `UNDER_REVIEW` | Tidak |
| **P08** | Seed Member Revision | `seed.member.revision@kojaya.test` | `DEV-KOP-008` | `3174990000000008` | `INACTIVE` | `REVISION` | `REVISION_REQUIRED` | Tidak |
| **P09** | Seed Member Rejected | `seed.member.rejected@kojaya.test` | `DEV-KOP-009` | `3174990000000009` | `INACTIVE` | `REJECTED` | `REJECTED` | Tidak |
| **P10** | Seed Member Active | `seed.member.active@kojaya.test` | `DEV-KOP-010` | `3174990000000010` | `ACTIVE` | `ACTIVE` | `ACTIVE` | Tidak |
| **P12** | Seed Member Google | `seed.member.google@kojaya.test` | `DEV-KOP-012` | `3174990000000012` | `ACTIVE` | `ACTIVE` | `ACTIVE` | **YA** |
| **P13** | Seed Member No Google | `seed.member.no-google@kojaya.test` | `DEV-KOP-013` | `3174990000000013` | `ACTIVE` | `ACTIVE` | `ACTIVE` | Tidak |

---

## 3. Persona Reserved & Ruang Lingkup yang Dikecualikan

Sesuai kontrak SEED-01 dan SEED-02R1, beberapa entitas dan persona sengaja **TIDAK DIBUAT** pada SEED-03:

1. **`P11` (`BLOCKED_UNKNOWN`):** Merupakan persona *corrupt/inconsistent lifecycle* yang masuk ke dalam domain pengujian batas negatif dan dialokasikan untuk implementasi khusus pada **SEED-06 Negative & Edge-Case Dataset**. Nomor anggota `DEV-KOP-011` tidak di-generate pada baseline SEED-03.
2. **`P14` (Pegawai PT Anak Usaha) & `P15` (Admin/HR PT Anak Usaha):** Dialokasikan sebagai persona tenaga kerja `Employee` untuk modul HR/Payroll anak usaha masa depan. Tidak dibuat pada SEED-03 untuk mencegah kebocoran model `Employee` ke seeder keanggotaan koperasi.
3. **Namespace Anggota Anak Usaha:** Ruang nama nomor anggota `DEV-KBU-*` dilarang keras dibuat karena PT anak usaha (`KBU-001`) tidak memiliki anggota koperasi.
4. **Zero Financial Fixtures:** Tidak ada pembuatan tagihan iuran (`CooperativeDuesInvoice`), pembayaran (`CooperativePayment`), mutasi buku besar (`CooperativeLedgerEntry`), atau rekening simpanan. Data finansial sintetis didelegasikan penuh ke **SEED-05**.
5. **Zero Pre-authenticated Tokens:** Tidak ada token Sanctum (`PersonalAccessToken`) yang dibuat di muka oleh seeder. Token diterbitkan secara dinamis saat skenario login diuji.

---

## 4. Spesifikasi Akun Terhubung Google SSO (P12 & P13)

### A. Persona P12 (`seed.member.google@kojaya.test`)
Mewakili anggota aktif yang telah menautkan akun Google Identity untuk pengujian SSO:
- Memiliki 1 relasi record [`SocialAccount`](../../app/Models/SocialAccount.php):
  - `provider` = `'google'`
  - `provider_id` = `'google-seed-sub-012'`
  - `provider_email` = `'seed.member.google@kojaya.test'`
  - `provider_name` = `'Seed Member Google'`
  - `linked_at` = `'2026-06-01 08:00:00'`
- **Zero Fake Login Side-Effects:**
  - `last_login_at` = `null`
  - `access_token` = `null`
  - `refresh_token` = `null`
  - Pada model `CooperativeMember`: `sso_provider` = `'google'`, `last_sso_login_at` = `null`.

### B. Persona P13 (`seed.member.no-google@kojaya.test`)
Mewakili anggota aktif dengan kredensial kata sandi standar yang belum pernah menautkan akun Google:
- Memiliki tepat **0** record `SocialAccount`.
- Pada model `CooperativeMember`: `sso_provider` = `null`, `last_sso_login_at` = `null`.

---

## 5. Aturan Invarian & Keamanan (Safety Constraints)

### A. Fail-Closed Environment Guard
Seeder menerapkan proteksi lingkungan fail-closed yang seragam:
```php
if (! in_array((string) config('app.env'), ['local', 'testing', 'playwright'], true)) {
    throw new LogicException('CooperativePersonaSeeder is only available in local, testing, or playwright environments.');
}
```
Jika dipanggil di lingkungan `production`, `staging`, `qa`, atau `development`, seeder langsung melempar `LogicException` tanpa memodifikasi baris data apa pun.

### B. Isolasi Organisasi Multi-Tenant
- Seluruh 12 pengguna dan 7 anggota koperasi ditautkan secara eksplisit ke `organization_id` milik `KOP-001` (Koperasi Jaya Bersama).
- Organisasi `KBU-001` (PT Anak Usaha) memiliki tepat **0** record `CooperativeMember`.
- Organisasi pengujian `ISO-999` tidak memiliki persona anggota default.

### C. Idempotensi & Restorasi Soft Deletes
- Pengguna diperbarui atau dibuat menggunakan pencarian `email`.
- Anggota koperasi dicari menggunakan `CooperativeMember::withTrashed()->where('member_no', ...)` dan dipulihkan melalui `->restore()` jika sebelumnya berstatus terhapus lunak, mencegah kesalahan duplikasi kunci unik saat seeder dieksekusi berulang kali.
- Relasi peran disinkronkan secara ketat menggunakan `$user->syncRoles([$roleName])`.

---

## 6. Verifikasi Pengujian

Rangkaian pengujian komprehensif diimplementasikan pada [`CooperativePersonaSeederTest`](../../tests/Feature/CooperativePersonaSeederTest.php):

1. **Skenario A:** Tepat 12 persona pengguna kanonikal (`seed.*@kojaya.test`) dibuat dengan status email terverifikasi.
2. **Skenario B:** Pemetaan peran tunggal tepat untuk P01–P05 (staf) dan P06–P10, P12, P13 (anggota).
3. **Skenario C:** Seluruh 12 persona dan 7 anggota terikat secara eksklusif ke `KOP-001`.
4. **Skenario D:** Tepat 7 anggota koperasi dibuat; staf P01–P05 memiliki 0 `CooperativeMember`.
5. **Skenario E:** Resolusi derived lifecycle experience memetakan status kanonikal secara akurat (`WaitingVerification`, `UnderReview`, `RevisionRequired`, `Rejected`, dan `Active`).
6. **Skenario F:** Persona cadangan (P11, P14, P15) dan namespace `DEV-KBU-*` tidak ada di database.
7. **Skenario G:** Organisasi `KBU-001` memiliki tepat 0 `CooperativeMember`.
8. **Skenario H:** Organisasi `ISO-999` memiliki 0 persona default.
9. **Skenario I:** Kontrak `SocialAccount` Google P12 valid dengan status login/token kosong.
10. **Skenario J:** Persona P13 memiliki 0 `SocialAccount`.
11. **Skenario K:** Kata sandi `password` tervalidasi via `Hash::check` untuk seluruh persona pengujian.
12. **Skenario L:** Eksekusi ganda seeder bersifat idempoten tanpa duplikasi baris.
13. **Skenario M:** Pemulihan anggota yang terhapus lunak (*soft-deleted*) tanpa duplikasi nomor anggota.
14. **Skenario N:** Guard fail-closed menolak eksekusi pada `production`, `staging`, `qa`, dan `development`.
15. **Uji Otentikasi Web:** Fortify web login (`POST /login`) berhasil mengotentikasi Admin Koperasi (P04) ke `/dashboard` dan Anggota Aktif (P10) ke rute `member.dashboard`.
16. **Uji Otentikasi Mobile API:** API mobile login (`POST /api/auth/login`) berhasil menerbitkan token Sanctum dan memvalidasi payload untuk Anggota Aktif (P10).

Pengujian keamanan statis ([`SeederSafetyStaticAnalysisTest`](../../tests/Feature/SeederSafetyStaticAnalysisTest.php)) dan dinamis ([`DatabaseSeederSafetyTest`](../../tests/Feature/DatabaseSeederSafetyTest.php)) juga memverifikasi bahwa seeder ini terklasifikasi sebagai non-reference yang aman dan terisolasi dari lingkungan produksi/staging.
