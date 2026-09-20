# SEED-07: Deterministic Reset / Reseed Tooling

## 1. Ringkasan Eksekutif

Dokumen ini menetapkan spesifikasi teknis, batasan kepemilikan data, dan arsitektur isolasi untuk perintah reset dan reseed data uji koperasi deterministik (**SEED-07**). Implementasi ini menuntaskan kesenjangan **G-09** dari kontrak [SEED-01 Test Data Contract](./SEED-01-test-data-contract.md), menghormati batas semantik organisasi [SEED-02R1 Organization Semantics](./SEED-02-master-reference-data.md), serta mengonsolidasikan seluruh dataset yang telah dibangun pada [SEED-03](./SEED-03-user-member-personas.md), [SEED-04](./SEED-04-member-lifecycle-dataset.md), [SEED-05](./SEED-05-synthetic-financial-data.md), dan [SEED-06](./SEED-06-negative-edge-case-dataset.md).

Deliverable utama dari tugas ini meliputi:
1. Layanan orkestrasi pembersihan dan reseed terisolasi [`CooperativeTestDataResetService`](../../app/Services/Cooperative/CooperativeTestDataResetService.php).
2. Perintah CLI Artisan [`CooperativeResetTestData`](../../app/Console/Commands/CooperativeResetTestData.php) (`php artisan cooperative:reset-test-data`).
3. Dukungan mode pratinjau aman (`--dry-run`) dan pemuatan dataset anomali terkontrol (`--with-edge-cases`).
4. Pengujian verifikasi end-to-end komprehensif pada [`CooperativeResetTestDataCommandTest`](../../tests/Feature/SEED07/CooperativeResetTestDataCommandTest.php) (23 skenario, 125 assertions).

---

## 2. Masalah & Solusi Arsitektur

### A. Masalah Sebelumnya (Kesenjangan G-09)
Sebelum SEED-07, tim pengembang dan QA tidak memiliki satu perintah terpadu untuk mereset lingkungan uji koperasi. Upaya reset sering kali bergantung pada perintah destruktif berbahaya seperti `migrate:fresh` atau `db:wipe` yang:
- Menghancurkan seluruh data ERP non-koperasi (karyawan, aset, penggajian, proyek).
- Menghapus konfigurasi referensi yang telah disesuaikan operator (misalnya tarif bunga pinjaman atau nominal simpanan wajib).
- Merusak data uji manual atau data QA yang sengaja dibuat di lingkungan lokal.

### B. Solusi SEED-07
SEED-07 mengimplementasikan pembersihan selektif berbasis ruang nama (*natural key/fixture-owned scoping*):
```text
Identifikasi kepemilikan fixture
        ↓
Hapus HANYA data mutable milik fixture (FK-safe order)
        ↓
Pertahankan konfigurasi referensi & master data
        ↓
Pertahankan data manual & data ERP non-koperasi
        ↓
Reseed baseline kanonikal Phase 3
        ↓
Verifikasi status deterministik
```

---

## 3. Sintaks Perintah & Parameter

```bash
php artisan cooperative:reset-test-data [options]
```

### Opsi:
- `--dry-run`: Menampilkan pratinjau rencana pembersihan dan reseed tanpa melakukan mutasi database apa pun (100% read-only).
- `--with-edge-cases`: Turut memuat dataset anomali SEED-06 (`CooperativeEdgeCaseFixtureSeeder` / persona P11 `BLOCKED_UNKNOWN`).

---

## 4. Kebijakan Lingkungan & Keamanan (Environment Isolation)

Perintah reset ini dilengkapi proteksi bertingkat (*fail-closed guard*) yang dievaluasi **sebelum** mutasi database dimulai:

| Lingkungan | Baseline Reset (`cooperative:reset-test-data`) | Dengan Edge Cases (`--with-edge-cases`) |
| :--- | :---: | :---: |
| `local` | **DIIZINKAN** | **DITOLAK KERAS** (Exit code 1, 0 mutasi) |
| `testing` | **DIIZINKAN** | **DIIZINKAN** |
| `playwright` | **DIIZINKAN** | **DIIZINKAN** |
| `development` | **DITOLAK** (Exit code 1, 0 mutasi) | **DITOLAK** (Exit code 1, 0 mutasi) |
| `qa` | **DITOLAK** (Exit code 1, 0 mutasi) | **DITOLAK** (Exit code 1, 0 mutasi) |
| `staging` | **DITOLAK** (Exit code 1, 0 mutasi) | **DITOLAK** (Exit code 1, 0 mutasi) |
| `production` | **DITOLAK** (Exit code 1, 0 mutasi) | **DITOLAK** (Exit code 1, 0 mutasi) |

> [!IMPORTANT]
> Opsi `--with-edge-cases` hanya diizinkan di `testing` dan `playwright`. Jika dieksekusi di `local`, perintah langsung dibatalkan sebelum operasi pembersihan berjalan, menjaga database lokal default tetap bersih dari status anomali.

---

## 5. Matriks Kepemilikan Fixture (Fixture Ownership Matrix)

Penghapusan data **TIDAK PERNAH** menggunakan kriteria luas seperti `organization_id = KOP-001`. Penghapusan terikat secara ketat pada ruang nama eksplisit berikut:

| Ruang Nama / Pola Kunci Alami | Entitas / Model Terkait | Klasifikasi Kepemilikan | Tindakan Reset |
| :--- | :--- | :--- | :--- |
| `seed.*@kojaya.test` | `User` (P01-P10, P12-P13, serta P11 jika ada) | Canonical Seed Users | Dihapus & Di-reseed |
| `DEV-KOP-*` | `CooperativeMember` (P06-P10, P12-P13, serta P11 jika ada) | Canonical Seed Members | Dihapus & Di-reseed |
| `google-seed-*` | `SocialAccount` (Google SSO P12) | Canonical Social Binding | Dihapus & Di-reseed |
| `SEED-PAY-*` | `CooperativePayment` | Financial Fixture | Dihapus & Di-reseed |
| `SEED-RC-*` | `CooperativeReceipt` | Financial Fixture | Dihapus & Di-reseed |
| `SEED-LOAN-*` | `Loan`, `LoanInstallment`, `LoanPayment` | Financial Fixture | Dihapus & Di-reseed |
| `seed-store-ledger:*` | `MemberStoreLedgerEntry` | Financial Fixture | Dihapus & Di-reseed |
| `SEED-POS-TX-*`, `SEED-POS-REF-*` | `PosTransaction`, `PosPayment`, `PosTransactionItem` | Financial Fixture | Dihapus & Di-reseed |
| Barcode `8999001000010` s/d `041` | `PosProduct` (Beras, Minyak, Gula, Kopi Seeder) | POS Catalog Fixture | Dihapus & Di-reseed |
| `DEMO-KOP-*` | `CooperativeMember` | Legacy Demo Fixture | Dihapus (Pembersihan Total) |
| `DEMO-ANG-*` | `CooperativeMember` | Legacy Demo Fixture | Dihapus (Pembersihan Total) |
| `demo.anggota*@koperasijayabersama.id` | `CooperativeMember` | Legacy Demo Fixture | Dihapus (Pembersihan Total) |
| `admin.kop@koj.id`, `kasir@...` | `User` | Legacy Demo Staff | Dihapus (Pembersihan Total) |
| `AUD-*` | `CooperativeMember` (UiAudit) | **DIKECUALIKAN** | **DIPERTAHANKAN** |
| `ui.*@kojaya.test` | `User` (UiAudit) | **DIKECUALIKAN** | **DIPERTAHANKAN** |
| `MANUAL-*` | `CooperativeMember` (Manual QA) | **DIKECUALIKAN** | **DIPERTAHANKAN** |
| Email non-seed / manual | `User` | **DIKECUALIKAN** | **DIPERTAHANKAN** |
| Data ERP non-koperasi | `Employee`, `Payroll`, `Asset`, dll | **DIKECUALIKAN** | **DIPERTAHANKAN** |
| Konfigurasi referensi | `CooperativeContributionType`, `LoanType` | **DIKECUALIKAN** | **DIPERTAHANKAN** |

---

## 6. Urutan Penghapusan Aman Relasi (FK-Safe Cleanup Ordering)

Untuk menjamin integritas referensial dan mencegah galat constraint foreign key, pembersihan dilakukan dengan urutan anak-ke-induk (*children-before-parents*) di dalam satu transaksi database (`DB::transaction`):

1. **Token Autentikasi:** `PersonalAccessToken` milik user fixture.
2. **Akun Sosial:** `SocialAccount` milik user fixture dan provider `google-seed-*`.
3. **Sub-transaksi POS:** `CoffeeOrder`, `PosTransactionItem`, `PosPayment`, `PosReturn`, `PosVoidRequest`, lalu `PosTransaction`.
4. **Poin & Reward POS Anggota:** `PosMemberCreditPayment`, `PosMemberPoint`, `PointTransaction`, `RewardRedemption`, `MemberPaymentIntent`.
5. **Akun Toko Anggota:** `MemberStoreLedgerEntry`, `MemberStoreFundingRequest`, `MemberStoreDelegate`, lalu `MemberStoreAccount`.
6. **Pinjaman Koperasi:** `ApprovalLog` (subject `Loan`), `LoanPayment`, `LoanInstallment`, `LoanRestructure`, lalu `Loan`.
7. **Kwitansi & Buku Besar:** `CooperativeReceipt`, `CooperativeLedgerEntry`, `CooperativePayment`, lalu `CooperativeDuesInvoice`.
8. **Data Siklus Hidup & Dokumen Anggota:** `CooperativeShuAllocation`, `CooperativeMemberOpeningBalanceLine`, `CooperativeMemberOpeningBalanceBatch`, `MemberOnboardingProgress`, `CooperativeMemberDocument`, `MemberResignationRequest`, `CooperativeSupportTicket`.
9. **Anggota Koperasi:** `CooperativeMember` (`forceDelete()` termasuk soft-deleted rows).
10. **Katalog POS Seeder:** `PosProduct` kanonikal seeder.
11. **Pengguna:** Pelepasan Spatie roles (`model_has_roles`), lalu `User` (`forceDelete()`).

---

## 7. Orkestrasi Reseed Kanonikal

Setelah pembersihan selesai, seeder kanonikal dieksekusi berurutan:
1. [`CooperativeFixtureReferenceSeeder`](../../database/seeders/CooperativeFixtureReferenceSeeder.php) (Organisasi KOP-001, KBU-001, ISO-999)
2. [`CooperativePersonaSeeder`](../../database/seeders/CooperativePersonaSeeder.php) (12 pengguna persona, 7 anggota persona, Google SSO P12)
3. [`CooperativeMemberLifecycleSeeder`](../../database/seeders/CooperativeMemberLifecycleSeeder.php) (Matriks siklus hidup P06-P10, P12-P13)
4. [`CooperativeFinancialFixtureSeeder`](../../database/seeders/CooperativeFinancialFixtureSeeder.php) (Data finansial P10, P12, P13)
5. *(Opsional jika `--with-edge-cases`)*: [`CooperativeEdgeCaseFixtureSeeder`](../../database/seeders/CooperativeEdgeCaseFixtureSeeder.php) (P11 `BLOCKED_UNKNOWN`)

---

## 8. Kondisi Baseline Pasca-Reset

Setelah eksekusi normal `php artisan cooperative:reset-test-data`:
- **Pengguna Kanonikal:** Tepat **12** user (`seed.*@kojaya.test`).
- **Anggota Kanonikal:** Tepat **7** anggota (`DEV-KOP-006` s/d `DEV-KOP-010`, `DEV-KOP-012`, `DEV-KOP-013`).
- **Matriks Siklus Hidup:**
  - P06: `WAITING_VERIFICATION` (`PENDING`/`PENDING`)
  - P07: `UNDER_REVIEW` (`PENDING`/`PENDING_VALIDATION`)
  - P08: `REVISION_REQUIRED` (`INACTIVE`/`REVISION`)
  - P09: `REJECTED` (`INACTIVE`/`REJECTED`)
  - P10, P12, P13: `ACTIVE` (`ACTIVE`/`ACTIVE`)
- **Status Finansial:**
  - P10: Simpanan Lunas, Saldo Toko +150.000, Pinjaman Lunas (`PAID_OFF`), Transaksi POS Selesai.
  - P12: Simpanan Belum Bayar, Saldo Toko -450.000 (Sisa Kredit 50.000), Pinjaman Aktif (`ACTIVE`), Belanja POS Kredit Selesai.
  - P13: Kosong (0 transaksi/tagihan).
  - P06-P09: Kosong (0 transaksi/tagihan).
- **Integritas Anomali:** P11 = 0, P14 = 0, P15 = 0.
- **Integritas Multi-Tenant:** Anggota pada KBU-001 = 0, Anggota baseline pada ISO-999 = 0.
- **Konfigurasi Operator:** Konfigurasi kustom `WAJIB.default_amount` dan `LoanType.interest_rate` tetap terjaga tanpa tertimpa nilai default seeder.
