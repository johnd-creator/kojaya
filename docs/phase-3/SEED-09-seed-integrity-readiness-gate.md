# SEED-09 — Seed Integrity & Readiness Gate

## 1. Tujuan & Misi

Dokumen ini mendokumentasikan spesifikasi, hasil verifikasi, dan bukti otoritatif dari **SEED-09: Seed Integrity & Readiness Gate**, tugas penutup dari **Fase 3 — Seed & Test Data** pada platform KojayaPro dan Kojayaku.

Misi utama SEED-09 adalah membangun satu gerbang integrasi (*readiness gate*) yang menguji dataset hasil kerja **SEED-01 hingga SEED-08** secara menyeluruh sebagai satu kesatuan (*cross-component integrity*). Gerbang ini menjawab pertanyaan kritis:

> *"Jika kita mereset/mereseed data uji Kojaya hari ini, apakah lingkungan uji yang dihasilkan koheren, terrekonsiliasi secara finansial, deterministik, aman, dan siap untuk memulai Functional Testing Fase 4?"*

Jawaban otoritatif setelah implementasi SEED-09: **YA (PASS)**.

---

## 2. Deliverable Utama

1. **Suite Pengujian Kesiapan Otoritatif**:
   [`SeedIntegrityGateTest.php`](../../tests/Feature/SEED09/SeedIntegrityGateTest.php) di `tests/Feature/SEED09/`. Menguji 41 skenario kesiapan (*Scenarios A through AO*) yang terorganisasi ke dalam 10 domain integritas:
   - **IDENTITY** (Skenario A, B, F, G, H, Z)
   - **LIFECYCLE** (Skenario D, E)
   - **ORGANIZATION** (Skenario C, AC)
   - **FINANCIAL** (Skenario I, J, K, L, M, N, AA, AB)
   - **STORE CREDIT** (Skenario O, P, Q)
   - **POS** (Skenario R, S, T)
   - **LOANS** (Skenario U, V, W, X, Y)
   - **EDGE ISOLATION** (Skenario AD, AE)
   - **DETERMINISM** (Skenario AF, AG)
   - **SAFETY** (Skenario AH, AI, AJ, AK, AL, AM, AN, AO)

2. **Dedicated GitHub Actions Check**:
   Pekerjaan terdedikasi `seed-integrity-gate` pada [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) dengan label visual yang jelas:
   `SEED-09 — Seed Integrity & Readiness Gate`.
   Check ini berjalan secara otomatis pada setiap PR dan push ke branch utama, memiliki semantik fail-closed, dan tidak mengizinkan bypass (`continue-on-error: false`).

3. **Dokumentasi Kesiapan & Penutupan Fase 3**:
   Dokumen ini dan pembaruan catatan audit pada [`docs/log.md`](../../docs/log.md).

---

## 3. Matriks Kesiapan Kanonikal (Canonical Readiness Matrix)

| Gerbang Integritas | Skenario Terkait | Status | Bukti Kunci |
| :--- | :--- | :---: | :--- |
| **Identity Gate** | A, B, F, G, H, Z | **PASS** | Tepat 12 user `seed.*@kojaya.test`, 7 anggota `DEV-KOP-*`, P11/P14/P15 nihil di baseline, RBAC akurat, natural key unik. |
| **Lifecycle Gate** | D, E | **PASS** | Resolusi `MemberLifecycleExperience` 100% konsisten, metadata verifikasi admin dan persetujuan pengurus koheren. |
| **Organization Gate** | C, AC | **PASS** | Topologi KOP-001 (pemilik anggota), KBU-001 (0 anggota, 0 keuangan), ISO-999 (0 anggota, 0 keuangan) terisolasi mutlak. |
| **Financial Gate** | I, J, K, L, M, N, AA, AB | **PASS** | P06-P09 & P13 nol data keuangan; P10 iuran pokok & wajib lunas; P12 iuran wajib belum bayar; rekonsiliasi kwitansi, pembayaran, dan buku besar simpanan 100%; tanpa baris yatim (*zero orphan*). |
| **Store Credit Gate** | O, P, Q | **PASS** | P10 saldo +150.000 (tersedia 650.000); P12 saldo -450.000 (limit 500.000, batas valid tersedia 50.000); rekonsiliasi buku besar saldo toko 100%. |
| **POS Gate** | R, S, T | **PASS** | P10 transaksi tunai 110.000 rekonsiliasi item & pembayaran; P12 transaksi saldo toko 450.000 termutasi di buku besar; stok produk berkurang sesuai kuantitas terjual. |
| **Loan Gate** | U, V, W, X, Y | **PASS** | P10 pinjaman lunas (outstanding 0, 6 angsuran lunas, 3.225.000); P12 pinjaman aktif (outstanding 3.225.000, 6 angsuran pending); maker-checker manajer-pengurus valid secara kronologis; 0 kredit macet di baseline. |
| **Edge Isolation Gate** | AD, AE | **PASS** | Baseline bersih dari P11; opsi `--with-edge-cases` menghadirkan P11 (`BLOCKED_UNKNOWN`, 0 keuangan); reset reguler membersihkan P11 kembali (*reversible separation*). |
| **Determinism Gate** | AF, AG | **PASS** | Snapshot Reset #1 identik mutlak dengan Reset #2 (berbasis *natural keys*); perusakan data kotor diperbaiki kembali oleh reset. |
| **Safety Gate** | AH, AI, AJ, AK, AL, AM, AN, AO | **PASS** | Data manual & ERP non-seeder terlindungi; konfigurasi kustom operator dipertahankan; SeederSafetyRegistry cakupan 100%; DatabaseSeeder di produksi hanya menghasilkan data referensi (0 fixture). |

---

## 4. Rincian Integritas Tiap Domain

### 4.1. Persona & Identitas (Identity Integrity)
- **User Kanonikal (12 User)**:
  - Staf/Operasional: `seed.system.admin`, `seed.pengurus`, `seed.manajer`, `seed.admin.kop`, `seed.kasir`.
  - Anggota: `seed.member.waiting`, `seed.member.review`, `seed.member.revision`, `seed.member.rejected`, `seed.member.active`, `seed.member.google`, `seed.member.no-google`.
- **Anggota Koperasi (7 Anggota)**:
  - `DEV-KOP-006` s.d. `DEV-KOP-010`, `DEV-KOP-012`, `DEV-KOP-013`.
  - Persona P11 (`DEV-KOP-011` / `seed.member.blocked@kojaya.test`), P14, dan P15 dipastikan tidak ada di baseline reguler.
- **Relasi User-Member**:
  - Seluruh anggota `DEV-KOP-*` terhubung ke User kanonikal yang sesuai.
  - Seluruh entitas anggota dan user berada di bawah naungan `KOP-001`.
- **Google SSO**:
  - Persona P12 memiliki tepat satu tautan `SocialAccount` Google dengan `provider_id = google-seed-sub-012`.
  - Tidak ada duplikasi akun sosial, dan anggota lain (termasuk P13) tidak memiliki tautan SSO.

### 4.2. Siklus Hidup Anggota (Lifecycle Integrity)
Hasil resolusi enum otoritatif `MemberLifecycleExperience::fromMember($member)`:
- `DEV-KOP-006` ➜ `WAITING_VERIFICATION` (belum ada validasi admin/pengurus, tanggal aktif null).
- `DEV-KOP-007` ➜ `UNDER_REVIEW` (divalidasi admin koperasi, menunggu persetujuan pengurus).
- `DEV-KOP-008` ➜ `REVISION_REQUIRED` (catatan revisi domisili & telepon dari admin/pengurus).
- `DEV-KOP-009` ➜ `REJECTED` (ditolak pengurus dengan alasan ketidaksesuaian syarat keanggotaan).
- `DEV-KOP-010` ➜ `ACTIVE` (diverifikasi admin, disetujui pengurus, tanggal aktif terisi).
- `DEV-KOP-012` ➜ `ACTIVE` (aktif dengan kredensial Google SSO).
- `DEV-KOP-013` ➜ `ACTIVE` (aktif dengan kredensial standar non-Google).

### 4.3. Isolasi Multi-Tenant & Organisasi (Organization Isolation)
- `KOP-001` (Koperasi Jaya Bersama, L0 HEAD_OFFICE): pemilik tunggal seluruh data keanggotaan dan keuangan koperasi kanonikal.
- `KBU-001` (PT Koperasi Berkah Usaha, L1 BRANCH): entitas komersial anak perusahaan. Memiliki 0 anggota koperasi dan 0 transaksi keuangan koperasi.
- `ISO-999` (Koperasi Mandiri Sejahtera, L0 HEAD_OFFICE): entitas pihak ketiga terisolasi untuk pengujian batas multi-tenant. Baseline memiliki 0 anggota dan 0 keuangan.

### 4.4. Rekonsiliasi Keuangan & Iuran (Financial Reconciliation)
- **Isolasi Finansial Non-Aktif**: Persona P06, P07, P08, dan P09 memiliki tepat 0 tagihan iuran, pembayaran, kwitansi, entri buku besar simpanan, akun toko, pinjaman, dan transaksi POS.
- **P13 Bentuk Finansial Kosong (Empty State)**: Persona P13 aktif secara keanggotaan tetapi memiliki 0 riwayat keuangan, membuktikan bahwa status `ACTIVE` tidak mensyaratkan polusi data historis.
- **P10 Iuran Lunas**:
  - Simpanan Pokok: Tagihan Rp 200.000 `PAID`, Pembayaran `APPROVED`, Kwitansi `SEED-RC-010-001`, Buku Besar Simpanan Kredit Rp 200.000.
  - Simpanan Wajib: Tagihan Rp 100.000 `PAID`, Pembayaran `APPROVED`, Kwitansi `SEED-RC-010-002`, Buku Besar Simpanan Kredit Rp 100.000.
- **P12 Iuran Belum Bayar**:
  - Simpanan Wajib Juni 2026: Tagihan Rp 100.000 `UNPAID` (paid_amount Rp 0, jatuh tempo 2026-06-10), tanpa pembayaran yang mencatat lunas palsu.
- **Rekonsiliasi Kwitansi & Pembayaran**: Seluruh kwitansi SEED terhubung 1:1 dengan pembayaran yang berstatus `APPROVED` dan anggota yang bersangkutan.

### 4.5. Rekonsiliasi Saldo Toko (Store Credit Reconciliation)
- **P10 (Kondisi Normal)**:
  - Limit kredit: Rp 500.000, Saldo: +Rp 150.000, Saldo Tersedia: Rp 650.000.
  - Entri buku besar: `OpeningBalance` Kredit Rp 150.000 (balance_after = +150.000).
- **P12 (Batas Kredit Valid)**:
  - Limit kredit: Rp 500.000, Saldo: -Rp 450.000, Saldo Tersedia: Rp 50.000.
  - Terpenuhi `balance >= -credit_limit` (-450.000 >= -500.000), akun tidak berada dalam kondisi over-limit ilegal.
  - Entri buku besar: `PosPurchase` Debit Rp 450.000 (balance_after = -450.000).
- **Integritas Buku Besar Saldo Toko**:
  - Pada P10 dan P12, saldo akun dihitung ulang dari formula `SUM(Kredit) - SUM(Debit)` dan menghasilkan angka yang identik persis dengan saldo ter-cache pada tabel `member_store_accounts`.
  - Setiap entri memenuhi rantai audit: `balance_before + mutasi = balance_after`.

### 4.6. Integritas Transaksi POS & Stok Produk (POS Integrity)
- **P10 (Pembelian Tunai)**:
  - Transaksi `SEED-POS-TX-010-001`, subtotal Rp 110.000, status `COMPLETED`.
  - Item: 1x Beras 5kg (`SEED-POS-001`, Rp 75.000) + 1x Minyak Goreng 2L (`SEED-POS-002`, Rp 35.000).
  - Pembayaran tunai Rp 110.000 (`cash_received` = 110.000, `cash_change` = 0).
- **P12 (Pembelian Saldo Toko)**:
  - Transaksi `SEED-POS-TX-012-001`, subtotal Rp 450.000, status `COMPLETED`.
  - Item: 6x Beras 5kg (`SEED-POS-001` @ Rp 75.000 = Rp 450.000).
  - Pembayaran metode `MEMBER_STORE_ACCOUNT` sebesar Rp 450.000 termutasi ke buku besar saldo toko.
- **Rekonsiliasi Stok Produk**:
  - `SEED-POS-001`: Stok akhir 93 = Stok awal 100 - Terjual 7 (1 P10 + 6 P12).
  - `SEED-POS-002`: Stok akhir 99 = Stok awal 100 - Terjual 1 (1 P10).
  - `SEED-POS-003`: Stok akhir 100 = Stok awal 100 - Terjual 0.
  - `SEED-POS-004`: Stok akhir 80 = Stok awal 80 - Terjual 0.

### 4.7. Integritas Pinjaman & Maker-Checker (Loan Integrity)
- **P10 (Pinjaman Lunas / Historical Paid-Off)**:
  - Referensi `SEED-LOAN-CLOSED-010-001`, pokok Rp 3.000.000, jasa/admin Rp 225.000, total tagihan Rp 3.225.000.
  - Status `PAID_OFF`, outstanding Rp 0.
  - 6 angsuran berstatus `PAID` (@ Rp 537.500), 6 pencatatan pembayaran pinjaman total Rp 3.225.000.
- **P12 (Pinjaman Aktif / Active Ongoing)**:
  - Referensi `SEED-LOAN-ACTIVE-012-001`, pokok Rp 3.000.000, jasa/admin Rp 225.000, total tagihan Rp 3.225.000.
  - Status `ACTIVE`, outstanding Rp 3.225.000.
  - 6 angsuran berstatus `PENDING` (@ Rp 537.500), 0 pembayaran pinjaman.
- **Maker-Checker Workflow**:
  - Reviewer Manajer: P03 (`seed.manajer@kojaya.test`).
  - Final Approver: P02 (`seed.pengurus@kojaya.test`).
  - Disburser: P02 (`seed.pengurus@kojaya.test`).
  - Memenuhi `manager_reviewed_by != approved_by` dan urutan kronologis ketat: `manager_reviewed_at < approved_at <= disbursed_at`.
- **Nol Kredit Macet di Baseline**: Jumlah pinjaman berstatus `DEFAULTED` dan `WRITTEN_OFF` adalah tepat 0 di baseline normal.

### 4.8. Isolasi Data Uji Anomali (Edge Case Isolation)
- Baseline reguler bebas dari anomali SEED-06.
- Eksekusi perintah `php artisan cooperative:reset-test-data --with-edge-cases` hanya diizinkan di lingkungan `testing` dan `playwright`. Menghasilkan persona P11 (`BLOCKED_UNKNOWN`) tanpa riwayat finansial.
- Eksekusi reset ulang standar (`php artisan cooperative:reset-test-data`) membersihkan persona P11 kembali dan memulihkan baseline kanonikal murni.

### 4.9. Determinisme & Pemulihan Keadaan Kotor (Reset Determinism & Recovery)
- Dua kali reset berturut-turut menghasilkan snapshot bisnis yang identik (*deterministic idempotence*) dihitung berdasarkan *natural keys* (email, no anggota, no referensi, barcode produk), mengabaikan perbedaan ID auto-increment.
- Data fixture yang sengaja dirusak (perubahan status validasi anggota, penggelembungan utang toko, korupsi tagihan iuran) berhasil dipulihkan secara sempurna ke konfigurasi kanonikal oleh perintah reset.

### 4.10. Keamanan Produksi & Preservasi Data (Production Safety)
- **Preservasi Data Manual**: Anggota dan user non-seeder (`MANUAL-KOP-001`) tidak terhapus saat reset koperasi dijalankan.
- **Isolasi ERP**: Data modul ERP lain (`Employee`, HR, penggajian) tidak terpengaruh oleh reset koperasi.
- **Preservasi Pengaturan Operator**: Nilai default iuran atau suku bunga pinjaman yang telah disesuaikan pengurus tetap dipertahankan setelah reset.
- **Keamanan Produksi Mutlak**: Eksekusi `DatabaseSeeder` di lingkungan produksi hanya membuat data referensi publik/aman dan menghasilkan 0 data persona, 0 anggota sintetis, 0 pinjaman sintetis, dan 0 transaksi POS demo.
- **Fail-Closed Runtime Guard**: Diskrepansi antara `config('app.env')` dan `app()->environment()` segera membatalkan eksekusi dengan `LogicException` sebelum terjadi mutasi basis data.
- **Cakupan Registri 100%**: [`SeederSafetyRegistry`](../../app/Support/SeedSafety/SeederSafetyRegistry.php) mencakup 100% seeder tanpa ada seeder yang tidak terklasifikasi.

---

## 5. Integrasi CI & GitHub Actions Readiness Check

Untuk memastikan kelaikan berkelanjutan, SEED-09 mengintegrasikan pekerjaan khusus pada alur kerja CI:

```text
GitHub Actions CI (.github/workflows/ci.yml)
    ├── Change Classification (changes)
    ├── Frontend Build (frontend-build)
    │     └── Asset Artifacts (manifest.json, etc.)
    │
    ├── SEED-09 — Seed Integrity & Readiness Gate (seed-integrity-gate)  [DEDICATED JOB]
    │     ├── Environment Setup: PHP 8.4, SQLite, Composer
    │     ├── Download Frontend Assets
    │     └── php artisan test --compact tests/Feature/SEED09/SeedIntegrityGateTest.php
    │
    ├── PHPUnit Shards 1..4 (phpunit-shard)
    ├── Quality Gate Aggregation (unit-feature-tests)
    ├── Migration & Seed (migration-seed)
    ├── Pint / Coding Style (style)
    └── PostgreSQL Concurrency (postgres-concurrency)
```

Semantik kegagalan (*Fail-Closed*):
- Jika salah satu skenario pada `SeedIntegrityGateTest` gagal, pekerjaan GitHub Action berstatus **FAILED**.
- Tidak menggunakan `continue-on-error: true`.
- PR tidak dapat digabungkan dan Fase 3 tidak dapat ditutup jika check `SEED-09 — Seed Integrity & Readiness Gate` tidak berstatus **GREEN**.

---

## 6. Kriteria Penutupan Fase 3 (Phase-3 Closure Statement)

> [!IMPORTANT]
> **Pernyataan Penutupan Fase 3 — Seed & Test Data**:
> Fase 3 dinyatakan **RESMI DITUTUP (CLOSED)** dan siap diserahkan ke **Fase 4 — Functional Test & Fix** dengan syarat mutlak berikut yang telah terpenuhi:
> 1. `SeedIntegrityGateTest` lulus 100% (41 skenario, 504 assertions).
> 2. Seluruh rangkaian tes regresi Fase 3 sebelumnya (SEED-03 s.d. SEED-08) lulus 100%.
> 3. Check terdedikasi `SEED-09 — Seed Integrity & Readiness Gate` pada GitHub Actions berstatus **GREEN**.
> 4. Seluruh alur kerja CI repository berstatus **GREEN**.
> 5. Branch kerja tersinkronisasi penuh terhadap branch `main` (`behind main = 0`).
> 6. Tidak ada cacat integritas P0/P1 yang belum terselesaikan.

---

## 7. Serah Terima ke Fase 4 (Handoff to Phase 4)

Dengan ditutupnya Fase 3, tim implementasi Fase 4 memperoleh jaminan:
1. Perintah `php artisan cooperative:reset-test-data` dapat dipanggil kapan saja untuk membangun lingkungan uji yang bersih dan deterministik dalam waktu < 2 detik.
2. Persona uji P01 s.d. P13 memiliki kredensial, peran, dan status keanggotaan yang terdokumentasi dan terbukti stabil.
3. Seluruh alur fungsional (pendaftaran anggota, verifikasi/approval, pembayaran iuran, belanja toko, transaksi kasir, dan pengajuan pinjaman) memiliki baseline data yang terrekonsiliasi secara matematis dan akuntansi.
