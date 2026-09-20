# SEED-05: Synthetic Transaction & Financial Test Data

## 1. Ringkasan Eksekutif

Dokumen ini menetapkan spesifikasi dan implementasi teknis dataset pengujian finansial sintetis deterministik untuk Phase 3 KojayaPro dan Kojayaku. Implementasi ini merealisasikan kontrak pengujian [SEED-01 Test Data Contract](./SEED-01-test-data-contract.md), batasan semantik organisasi [SEED-02R1 Organization Semantics](./SEED-02-master-reference-data.md), serta melanjutkan data persona dari [SEED-03 User & Member Persona Seeder](./SEED-03-user-member-personas.md) dan pengayaan siklus hidup dari [SEED-04 Member Lifecycle Dataset](./SEED-04-member-lifecycle-dataset.md).

Deliverable utama dari tugas ini adalah [`CooperativeFinancialFixtureSeeder`](../../database/seeders/CooperativeFinancialFixtureSeeder.php), yang bertanggung jawab menyusun data keuangan deterministik (tagihan simpanan, pembayaran, kwitansi, buku besar simpanan dan pinjaman, saldo toko anggota, katalog dan transaksi POS, serta pinjaman aktif dan lunas) khusus untuk anggota aktif kanonikal tanpa menghasilkan variasi kalender acak.

Dokumen ini juga mencakup penyelesaian **SEED-04R1 (Preservation of Admin Verification Metadata)** dan **Gap G-04 (Elimination of Calendar Dependency)**.

---

## 2. Penyelesaian Pre-Flight: SEED-04R1

Pada implementasi awal SEED-04, metadata verifikasi staf admin (`admin_validated_by`, `admin_validated_at`, `admin_validation_notes`) sempat terhapus saat anggota beralih ke status `REVISION` (P08) dan `REJECTED` (P09).

Sesuai aturan produksi alur verifikasi keanggotaan (ONB-03), status penolakan atau revisi hanya dapat terjadi setelah telaah berkas oleh staf admin. Oleh karena itu, SEED-04R1 memulihkan riwayat verifikasi admin pada P08 dan P09:

- **P08 (`DEV-KOP-008`):**
  - `admin_validated_by`: P04 (`seed.admin.kop@kojaya.test`)
  - `admin_validated_at`: `2026-06-01 09:00:00`
  - `admin_validation_notes`: Catatan telaah awal staf admin
  - `validated_by`: P04
  - `validated_at`: `2026-06-01 09:30:00`
  - `validation_notes`: Instruksi pembaruan nomor telepon dan alamat domisili
- **P09 (`DEV-KOP-009`):**
  - `admin_validated_by`: P04 (`seed.admin.kop@kojaya.test`)
  - `admin_validated_at`: `2026-06-01 09:00:00`
  - `admin_validation_notes`: Catatan telaah awal staf admin
  - `validated_by`: P02 (`seed.pengurus@kojaya.test`)
  - `validated_at`: `2026-06-01 10:00:00`
  - `validation_notes`: Penolakan pendaftaran karena tidak memenuhi syarat
  - *Maker-checker invariant terjaga:* `P04 !== P02`.

---

## 3. Pemetaan Persona Finansial & Lima Bentuk Data (Five Financial Shapes)

Sesuai aturan ketat domain, **hanya anggota kanonikal yang berstatus sepenuhnya aktif (ACTIVE/ACTIVE)** yang diizinkan memiliki catatan finansial. Anggota non-aktif (P06, P07, P08, P09) dipastikan memiliki **0 record finansial**.

Tiga persona anggota aktif dipetakan secara terarah untuk merepresentasikan 5 bentuk data finansial kanonikal dari kontrak SEED-01:

| Persona | No. Anggota | Email | Bentuk Finansial | Representasi Bisnis |
| :--- | :--- | :--- | :--- | :--- |
| **P13** | `DEV-KOP-013` | `seed.member.no-google@kojaya.test` | **1. EMPTY** | Anggota aktif baru tanpa riwayat keuangan (0 tagihan, 0 pembayaran, 0 pinjaman, 0 saldo toko, 0 POS). |
| **P10** | `DEV-KOP-010` | `seed.member.active@kojaya.test` | **2. NORMAL**<br>**4. COMPLETED / PAID** | Anggota sehat jangka panjang dengan simpanan lunas, saldo toko positif (+150k), transaksi POS tunai rutin, dan pinjaman produktif historis yang telah lunas. |
| **P12** | `DEV-KOP-012` | `seed.member.google@kojaya.test` | **3. PARTIAL / UNPAID**<br>**5. VALID BOUNDARY** | Anggota aktif dengan kewajiban berjalan: simpanan wajib belum dibayar, pinjaman produktif aktif dengan angsuran pending, serta akun saldo toko dekat batas limit kredit (-450k / limit 500k, sisa kredit 50k). |

---

## 4. Garis Waktu & Kebijakan Tanggal Kanonikal

Untuk memastikan determinisme mutlak dan menutup **Gap G-04**, seluruh dataset finansial berpatokan pada jangkar waktu kanonikal **`2026-06-01`**:
- **Transaksi Historis:** Berjalan dari rentang `2025-09-01` hingga `2026-05-31`.
- **Jendela Pengujian Utama:** `2026-06-01`.
- **Jatuh Tempo Berjalan (Near-Due):** `2026-06-10`.
- **Penutupan Gap G-04:** Pembuatan invoice pada seeder warisan (`CooperativeSeeder` dan `AnggotaSeeder`) diubah dari `$end = Carbon::now()->startOfMonth();` menjadi `$end = Carbon::parse('2026-06-01')->startOfMonth();`. Hal ini mencegah pergeseran jumlah periode tagihan ketika seeder dijalankan pada bulan atau tahun yang berbeda di masa mendatang.

---

## 5. Matriks Tagihan & Pembayaran Simpanan (Contribution / Dues)

Menggunakan konfigurasi jenis simpanan kanonikal dari `CooperativeReferenceSeeder`:
- `POKOK`: Rp 200.000 (frekuensi: `ONCE`)
- `WAJIB`: Rp 100.000 (frekuensi: `MONTHLY`)

### Rincian Tagihan Kanonikal

| No. Anggota | Jenis Simpanan | Periode | Nominal Tagihan | Nominal Dibayar | Status | Jatuh Tempo | No. Referensi Pembayaran | No. Kwitansi |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :--- | :--- |
| `DEV-KOP-010` (P10) | `POKOK` | `2026-01` | Rp 200.000 | Rp 200.000 | `PAID` | `2026-01-10` | `SEED-PAY-POKOK-010-202601` | `SEED-RC-010-001` |
| `DEV-KOP-010` (P10) | `WAJIB` | `2026-01` | Rp 100.000 | Rp 100.000 | `PAID` | `2026-01-10` | `SEED-PAY-WAJIB-010-202601` | `SEED-RC-010-002` |
| `DEV-KOP-012` (P12) | `WAJIB` | `2026-06` | Rp 100.000 | Rp 0 | `UNPAID` | `2026-06-10` | *Tidak ada* | *Tidak ada* |

### Rekonsiliasi Buku Besar Simpanan (`SAVINGS`)
- Total pembayaran simpanan disetujui (`APPROVED`) untuk P10: **Rp 300.000**.
- Total entri kredit buku besar koperasi (`CooperativeLedgerEntry`, scope `SAVINGS`): **Rp 300.000**.
- Kwitansi dibuat secara deterministik tanpa efek samping disk IO atau rendering runtime PDF.

---

## 6. Model Akun Saldo Toko Anggota (Store Account & Credit)

Otoritas akun toko dikelola melalui model `MemberStoreAccount` dan `MemberStoreLedgerEntry` (bukan field lama `credit_limit` pada model anggota):

### A. Persona P10 (Normal State)
- `credit_limit`: Rp 500.000
- `balance`: **+Rp 150.000**
- `availableCredit()`: **Rp 650.000**
- Buku Besar Toko: 1 record `OpeningBalance` dengan efek `Credit` sebesar Rp 150.000 (`idempotency_key = 'seed-store-ledger:010:opening'`).
- Rekonsiliasi: `sum(ledger entries) == balance` (+150.000).

### B. Persona P12 (Valid Boundary Near-Limit State)
- `credit_limit`: Rp 500.000
- `balance`: **-Rp 450.000** (piutang belanja toko)
- `availableCredit()`: **Rp 50.000**
- Buku Besar Toko: 1 record `PosPurchase` dengan efek `Debit` sebesar Rp 450.000 yang terhubung ke transaksi POS `SEED-POS-TX-012-001`.
- Rekonsiliasi: `abs(balance) <= credit_limit` (450.000 <= 500.000) mencerminkan kondisi batas aman (*near-limit*) tanpa melanggar batas kredit.

---

## 7. Katalog & Transaksi Kasir POS Koperasi

Seluruh produk dan transaksi POS terikat secara eksklusif ke organisasi `KOP-001` dengan kasir operasional P05 (`seed.kasir@kojaya.test`).

### A. Katalog Produk Kanonikal KOP-001

| SKU | Nama Produk | Kategori | Harga Pokok | Harga Jual | Stok Awal | Penjualan | Stok Akhir | Satuan |
| :--- | :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| `SEED-POS-001` | Beras Seeder Premium 5kg | Sembako | Rp 65.000 | Rp 75.000 | 100 | 7 (1 P10 + 6 P12) | **93** | sak |
| `SEED-POS-002` | Minyak Goreng Seeder 2L | Sembako | Rp 28.000 | Rp 35.000 | 100 | 1 (1 P10) | **99** | pouch |
| `SEED-POS-003` | Gula Pasir Seeder 1kg | Sembako | Rp 12.000 | Rp 15.000 | 100 | 0 | **100** | kg |
| `SEED-POS-004` | Kopi Bubuk Seeder 250g | Minuman | Rp 15.000 | Rp 22.000 | 80 | 0 | **80** | pack |

### B. Transaksi POS P10 (Rutin / Tunai)
- No. Transaksi: `SEED-POS-TX-010-001` (`client_reference = 'SEED-POS-REF-010-001'`)
- Tanggal: `2026-05-15 11:00:00` | Status: `COMPLETED`
- Item: 1x `SEED-POS-001` (Rp 75.000) + 1x `SEED-POS-002` (Rp 35.000) = Subtotal Rp 110.000
- Pembayaran: `CASH` sebesar Rp 110.000
- Laba Kotor: Rp 17.000

### C. Transaksi POS P12 (Belanja Saldo Toko)
- No. Transaksi: `SEED-POS-TX-012-001` (`client_reference = 'SEED-POS-REF-012-001'`)
- Tanggal: `2026-05-20 14:00:00` | Status: `COMPLETED`
- Item: 6x `SEED-POS-001` @ Rp 75.000 = Subtotal Rp 450.000
- Pembayaran: `MEMBER_STORE_ACCOUNT` sebesar Rp 450.000
- Laba Kotor: Rp 60.000
- Rekonsiliasi: `PosPayment.amount` == `MemberStoreLedgerEntry.amount` (Rp 450.000).

---

## 8. Matriks Pinjaman Anggota (Loans)

Menggunakan jenis pinjaman kanonikal `productive` (bunga 1,25% per bulan flat, biaya admin Rp 0) dari `LoanTypeSeeder`:

### A. Persona P10: Pinjaman Lunas Historis (`PAID_OFF`)
- No. Referensi: `SEED-LOAN-CLOSED-010-001`
- Pokok: Rp 3.000.000 | Tenor: 6 bulan (`2025-09-01` s.d. `2026-03-10`)
- Workflow Review & Approval: Direview Manajer P03 (`2025-09-02 09:00:00`), disetujui Pengurus P02 (`2025-09-02 14:00:00`), dicairkan Pengurus P02 (`2025-09-03 10:00:00`).
- Maker-Checker invariant: `manager_reviewed_by (P03) != approved_by (P02)` dengan urutan `manager_reviewed_at < approved_at <= disbursed_at`.
- Angsuran per bulan: Rp 537.500 (Pokok Rp 500.000 + Bunga Rp 37.500)
- Total Pinjaman: Rp 3.225.000 | Sisa Pinjaman (*Outstanding*): **Rp 0**
- Status: `LoanStatus::PaidOff`
- Seluruh 6 angsuran berstatus `PAID` dengan 6 pembayaran `LoanPayment` yang saling merekonsiliasi.
- Buku Besar: 1 entri debit pencairan (`LOAN_DISBURSEMENT` Rp 3.000.000) dan 6 entri kredit angsuran (`LOAN_PAYMENT` total Rp 3.225.000).

### B. Persona P12: Pinjaman Aktif Berjalan (`ACTIVE`)
- No. Referensi: `SEED-LOAN-ACTIVE-012-001`
- Pokok: Rp 3.000.000 | Tenor: 6 bulan (`2026-05-01` s.d. `2026-11-10`)
- Workflow Review & Approval: Direview Manajer P03 (`2026-05-02 09:00:00`), disetujui Pengurus P02 (`2026-05-02 14:00:00`), dicairkan Pengurus P02 (`2026-05-03 10:00:00`).
- Maker-Checker invariant: `manager_reviewed_by (P03) != approved_by (P02)` dengan urutan `manager_reviewed_at < approved_at <= disbursed_at`.
- Total Pinjaman: Rp 3.225.000 | Sisa Pinjaman (*Outstanding*): **Rp 3.225.000**
- Status: `LoanStatus::Active`
- Angsuran ke-1 (jatuh tempo `2026-06-10`) dan seterusnya berstatus `PENDING` (belum ada pembayaran).
- Buku Besar: 1 entri debit pencairan (`LOAN_DISBURSEMENT` Rp 3.000.000).

---

## 9. Aturan Keamanan, Idempotensi, & Batasan SEED-06

1. **Fail-Closed Environment Guard:** Seeder dibatasi strictly pada environment `local`, `testing`, dan `playwright`. Panggilan pada `production`, `staging`, `qa`, atau `development` langsung memicu `LogicException`.
2. **Idempotensi Penuh:** Seluruh entitas memiliki natural key deterministik (`reference_no`, `receipt_no`, `idempotency_key`, `sku`, `client_reference`). Eksekusi berulang tidak menduplikasi baris, mutasi buku besar, maupun mengubah saldo.
3. **Isolasi Organisasi:** KBU-001 (PT Anak Usaha) dan ISO-999 memiliki strictly **0 anggota** dan **0 record finansial koperasi**.
4. **Batas Domain SEED-06:** Baseline SEED-05 tidak memuat data negatif atau rusak:
   - Tidak ada pinjaman macet (`DEFAULTED` / `WRITTEN_OFF`).
   - Tidak ada saldo toko yang melampaui limit (`balance < -credit_limit`).
   - Tidak ada record finansial untuk P11 (`BLOCKED_UNKNOWN`).
