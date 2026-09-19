# SEED-02: Master / Reference Data Seeder & Non-Production Topology

## 1. Ringkasan Eksekutif

Dokumen ini mendefinisikan implementasi teknis lapisan data master/referensi Phase 3 untuk KojayaPro dan Kojayaku, menerjemahkan spesifikasi arsitektur yang telah disepakati pada [SEED-01 Test Data Contract](./SEED-01-test-data-contract.md).

Tujuan utama SEED-02 adalah menetapkan fondasi data deterministik yang kokoh untuk downstream tasks (SEED-03 s.d. SEED-09) dengan menegakkan batas pemisah yang ketat (*hard boundary*) antara:
1. **`PRODUCTION_SAFE_REFERENCE`**: Data referensi kanonikal yang aman dan idempoten untuk seluruh lingkungan (termasuk `production` dan `staging`).
2. **`NON_PRODUCTION_DETERMINISTIC_FIXTURE`**: Topologi organisasi multi-tenant dan pengujian isolasi yang **wajib fail-closed** di luar lingkungan `local`, `testing`, dan `playwright`.

---

## 2. Batas Pemisah Referensi Produksi (Production-Safe Reference Boundary)

Sesuai kontrak SEED-01, lingkungan produksi (`production` dan `staging`) hanya boleh mengeksekusi seeder referensi operasional. Seeder dalam kelompok ini diwajibkan memenuhi kriteria keamanan statis dan dinamis:
- **Zero Credentials:** Tidak membuat akun pengguna (`User`), kata sandi (`Hash::make`), atau relasi login.
- **Zero Member Fixtures:** Tidak membuat data anggota (`CooperativeMember`) sintetis.
- **Zero Transactions:** Tidak membuat transaksi finansial, tagihan iuran, pinjaman, kuitansi, mutasi buku besar, atau transaksi kasir POS.
- **Zero Destructive Operations:** Bebas dari `truncate()`, `forceDelete()`, `delete()`, atau perintah SQL destruktif.

### Daftar Seeder Referensi Produksi

| Nama Seeder | Entitas Utama | Natural Key | Strategi Idempotensi & Preservasi Operator | Perubahan di SEED-02 |
| :--- | :--- | :--- | :--- | :--- |
| [`TaxRuleSeeder`](../../database/seeders/TaxRuleSeeder.php) | `TaxRule` | `code` (`TER_2024`) | `updateOrCreate` data tarif resmi PPh 21 TER 2024. | Tidak ada (Preserved) |
| [`RolePermissionSeeder`](../../database/seeders/RolePermissionSeeder.php) | `Role`, `Permission` | `name` | `firstOrCreate` role & permission, `syncPermissions` kanonikal. | Tidak ada (Preserved) |
| [`LoanTypeSeeder`](../../database/seeders/LoanTypeSeeder.php) | `LoanType` | `code` (`emergency`, `productive`, `consumer`) | `firstOrCreate` menjaga parameter suku bunga, plafon, tenor, dan `is_active` operator. | Tidak ada (Preserved) |
| [`JobGradeSeeder`](../../database/seeders/JobGradeSeeder.php) | `JobGrade` | `code` (`PELAKSANA` s.d. `DIREKSI`) | `firstOrCreate` menjaga master grade jabatan HR. | Tidak ada (Preserved) |
| [`LeaveTypeSeeder`](../../database/seeders/LeaveTypeSeeder.php) | `LeaveType` | `name` | `firstOrCreate` menjaga konfigurasi jenis cuti. | Tidak ada (Preserved) |
| [`SalaryComponentTypeSeeder`](../../database/seeders/SalaryComponentTypeSeeder.php) | `SalaryComponentType` | `code` (`P1`, `P2`, `TGT`, `TPL`, `TP`) | `firstOrCreate` menjaga komponen penggajian. | Tidak ada (Preserved) |
| [`WorkShiftSeeder`](../../database/seeders/WorkShiftSeeder.php) | `WorkShift` | `name` | `firstOrCreate` menjaga jadwal shift dan toleransi fleksibel. | Tidak ada (Preserved) |
| [`CooperativeReferenceSeeder`](../../database/seeders/CooperativeReferenceSeeder.php) | `Organization`, `CooperativeContributionType`, `PosCategory` | `code` / `slug` | `firstOrCreate` menjaga anchor KOP-001, tarif iuran POKOK/WAJIB, dan kategori POS. | Tidak ada (Preserved) |

---

## 3. Kepemilikan dan Topologi Organisasi (Organization Topology & Ownership)

Pengujian Phase 4 membutuhkan topologi multi-organisasi yang merepresentasikan entitas legal koperasi, PT anak usaha (*subsidiary company*), dan pihak ketiga yang terisolasi. Namun, ketiga organisasi ini memiliki kelas keamanan dan batas domain hukum yang berbeda:

```text
DatabaseSeeder (Production / Staging)
        │
        ▼
CooperativeReferenceSeeder
        └── KOP-001 (HEAD_OFFICE, L0)  <-- HANYA INI YANG DIBUAT DI PRODUCTION

───────────────────────────────────────────────────────────────────────────

DatabaseSeeder (Local Development) / CooperativeFixtureReferenceSeeder (Testing)
        │
        ▼
CooperativeFixtureReferenceSeeder (Guarded: local, testing, playwright)
        ├── KOP-001 (HEAD_OFFICE, L0, parent: null)  [Koperasi Legal Entity - OWNS ALL MEMBERS]
        ├── KBU-001 (BRANCH, L1, parent: KOP-001)    [PT Anak Usaha / Subsidiary - ZERO MEMBERS]
        └── ISO-999 (HEAD_OFFICE, L0, parent: null)  [Tenant Terisolasi Third-Party Synthetic]
```

### Rincian Organisasi & Kepemilikan Tunggal (Single Ownership Strategy)

1. **`KOP-001` (Koperasi Jaya Bersama)**
   - **Pemilik Tunggal:** [`CooperativeReferenceSeeder`](../../database/seeders/CooperativeReferenceSeeder.php).
   - **Tipe:** `HEAD_OFFICE`, Level `L0`, Parent `null`.
   - **Aturan:** Merupakan **satu-satunya entitas legal koperasi** saat ini yang berhak memiliki data anggota koperasi (`CooperativeMember`), proses onboarding anggota, simpanan, pinjaman, kredit toko POS, dan SHU. Menjadi anchor organisasi utama produksi dan non-produksi. Downstream seeder hanya mengonsumsi (*read/lookup*) `KOP-001` tanpa mengubah properti operator yang sudah ada.

2. **`KBU-001` (PT Koperasi Berkah Usaha)**
   - **Pemilik Tunggal:** [`CooperativeFixtureReferenceSeeder`](../../database/seeders/CooperativeFixtureReferenceSeeder.php).
   - **Tipe:** `BRANCH` (secara skema teknis enum), Level `L1`, Parent `KOP-001`.
   - **Aturan:** Mewakili **PT Anak Usaha (*subsidiary commercial company*)** di bawah kepemilikan/kendali koperasi. Tujuannya dalam hierarki organisasi adalah untuk mendukung pengujian modul tenaga kerja (`Employee`), HR/HRM, absensi, penggajian (*payroll*), aset, work order, dan proyek di masa depan. **DILARANG KERAS MEMILIKI ANGGOTA KOPERASI (`CooperativeMember` count = 0)**, simpanan, atau pinjaman. Persona `P14` dan `P15` di-reserve untuk tenaga kerja anak usaha masa depan. **Dilarang dibuat di production/staging.**

3. **`ISO-999` (Koperasi Mandiri Sejahtera)**
   - **Pemilik Tunggal:** [`CooperativeFixtureReferenceSeeder`](../../database/seeders/CooperativeFixtureReferenceSeeder.php).
   - **Tipe:** `HEAD_OFFICE`, Level `L0`, Parent `null`.
   - **Aturan:** Khusus lingkungan non-produksi. Entitas sintetis pihak ketiga yang terisolasi untuk pengujian otorisasi multi-tenant antar badan hukum koperasi yang independen (bukan antara koperasi dengan anak usahanya). Tidak menerima persona anggota default pada SEED-03. **Dilarang dibuat di production/staging.**

---

## 4. Matriks Lingkungan Eksekusi (Environment Matrix)

| Lingkungan | `CooperativeReferenceSeeder` | `CooperativeFixtureReferenceSeeder` | `KOP-001` | `KBU-001` | `ISO-999` | Users / Personas |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **`production`** | **RUN** | **FAIL-CLOSED (`LogicException`)** | **ADA** | **TIDAK ADA** | **TIDAK ADA** | **0** |
| **`staging`** | **RUN** | **FAIL-CLOSED (`LogicException`)** | **ADA** | **TIDAK ADA** | **TIDAK ADA** | **0** |
| **`qa`** | **RUN** | **FAIL-CLOSED (`LogicException`)** | **ADA** | **TIDAK ADA** | **TIDAK ADA** | **0** |
| **`development`** | **RUN** | **FAIL-CLOSED (`LogicException`)** | **ADA** | **TIDAK ADA** | **TIDAK ADA** | **0** |
| **`local`** | **RUN** | **RUN** | **ADA** | **ADA** | **ADA** | Dibuat oleh demo seeder downstream |
| **`testing`** | **RUN** | **RUN** | **ADA** | **ADA** | **ADA** | Dibuat sesuai kebutuhan suite pengujian |
| **`playwright`** | **RUN** | **RUN** | **ADA** | **ADA** | **ADA** | Dibuat sesuai kebutuhan E2E |

---

## 5. Strategi Idempotensi dan Preservasi Konfigurasi Operator

### A. Pola `firstOrCreate`
Seluruh pembuatan record referensi menggunakan pencarian berbasis *natural key* (`code` untuk organisasi, jenis simpanan, jenis pinjaman, dan grade jabatan; `slug` untuk kategori POS; `name` untuk peran, izin, jenis cuti, dan jadwal kerja).

Jika record telah ada di basis data:
- `firstOrCreate` mengembalikan record yang sudah ada tanpa melakukan mutasi atribut (`UPDATE`).
- Nilai kustomisasi operator (seperti perubahan nama koperasi, alamat, nomor telepon, perubahan tarif simpanan pokok menjadi Rp 350.000, suku bunga pinjaman darurat, atau penonaktifan kategori POS) **tetap terjaga utuh**.

### B. Eliminasi Duplikasi Kepemilikan pada `CooperativeSeeder`
Sebelum SEED-02, [`CooperativeSeeder`](../../database/seeders/CooperativeSeeder.php) menggunakan `updateOrCreate` pada `KOP-001`, `KBU-001`, dan 4 jenis simpanan pokok/wajib. Pola ini berpotensi menimpa konfigurasi operator lokal setiap kali reseed dijalankan.

Pada SEED-02:
1. `CooperativeSeeder` tidak lagi mendefinisikan ulang organisasi atau jenis simpanan.
2. `CooperativeSeeder` mencari `KOP-001` dan `KBU-001` yang telah disediakan oleh `CooperativeReferenceSeeder` dan `CooperativeFixtureReferenceSeeder`. Jika belum ada, seeder referensi dipanggil secara otomatis.
3. Pada pembuatan produk inventaris POS (`seedPosInventory`), pencarian kategori POS menggunakan penelusuran slug yang fleksibel terhadap `organization_id` yang bernilai null maupun yang sudah terikat, mencegah duplikasi slug kategori yang dapat memicu kegagalan rollback migrasi.

---

## 6. Integrasi dengan `DatabaseSeeder`

Alur orkestrasi utama pada [`DatabaseSeeder`](../../database/seeders/DatabaseSeeder.php) dikonfigurasi sebagai berikut:

```php
public function run(): void
{
    // 1. Production-safe reference seeders (dieksekusi di SEMUA lingkungan)
    $this->call([
        TaxRuleSeeder::class,
        RolePermissionSeeder::class,
        LoanTypeSeeder::class,
        JobGradeSeeder::class,
        LeaveTypeSeeder::class,
        SalaryComponentTypeSeeder::class,
        WorkShiftSeeder::class,
        CooperativeReferenceSeeder::class,
    ]);

    // 2. Guarded non-production fixtures (HANYA dieksekusi di local development)
    if (app()->environment('local')) {
        $this->call([
            CooperativeFixtureReferenceSeeder::class,
            CooperativeSeeder::class,
            AnggotaSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
```

---

## 7. Kesiapan Downstream Tasks (SEED-03 onward)

Implementasi SEED-02 menyelesaikan dependensi master data untuk tahapan berikutnya:
- **SEED-03 (User & Member Persona):** Memiliki anchor organisasi kanonikal yang pasti (`KOP-001` untuk seluruh persona anggota dan staf koperasi P01–P10 & P12–P13; `KBU-001` sebagai PT anak usaha dengan 0 CooperativeMember dan P14/P15 di-reserve untuk tenaga kerja anak usaha masa depan).
- **SEED-04 (Member Lifecycle Dataset):** Siap mengaitkan anggota ke tipe simpanan resmi `POKOK` dan `WAJIB` di bawah `KOP-001`.
- **SEED-05 (Synthetic Financial Data):** Memiliki produk pinjaman kanonikal (`emergency`, `productive`, `consumer`) dan kategori POS aktif.
- **SEED-06 (Negative & Edge Cases):** Memiliki tenant terisolasi `ISO-999` yang bersih untuk pengujian batas otorisasi multi-tenant antar entitas independen.
