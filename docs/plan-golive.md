# Plan Go-Live Iuran & Simpanan

## Tujuan Dokumen

Dokumen ini menyusun rencana implementasi yang detail untuk kebutuhan go-live modul:

- `http://localhost:8000/cooperative/dues`
- `http://localhost:8000/cooperative/ledger`
- kebutuhan tampilan simpanan anggota di aplikasi web/member portal
- kebutuhan API yang akan dipakai oleh Flutter

Fokus utama rencana ini adalah:

1. multiple batch payment pada halaman iuran
2. pagination yang terlihat dan usable di `dues` dan `ledger`
3. standardisasi kategori simpanan
4. penyelarasan antara iuran, simpanan, ledger, dan member app
5. API readiness untuk Flutter

## Status Eksekusi 2026-06-04

Rencana ini sudah dieksekusi pada sisi codebase dengan keputusan implementasi tahap awal berikut:

- `full settlement` dipakai sebagai mode batch payment awal; partial per invoice disiapkan sebagai fase lanjutan.
- `Simpanan Khusus` distandardisasi sebagai master aktif kategori `KHUSUS` dengan frekuensi `ADHOC`.
- Ledger anggota dinormalisasi dengan `ledger_scope`, relasi `cooperative_contribution_type_id`, dan `category_snapshot`.
- Member API dan web member memakai summary `by_category` dan ledger `SAVINGS` paginated/filterable.
- Halaman admin `dues` dan `ledger` mendapat filter kategori/member/type, pagination UI, summary, dan konfirmasi batch payment.

## Ringkasan Eksekutif

Hasil audit codebase menunjukkan bahwa fondasi fitur sebenarnya sudah ada, tetapi masih parsial.

### Temuan utama

1. `Multiple batch payment` sebenarnya **sudah ada di backend dan sebagian UI**
   - halaman `dues` sudah punya checkbox multi-select
   - route `POST /cooperative/dues/mark-paid` sudah menerima banyak invoice
   - controller `CooperativeDuesController::markPaid()` sudah memproses banyak invoice
   - test untuk batch mark paid juga sudah ada

2. `Pagination` sebenarnya **sudah ada di backend**, tetapi **belum dirender dengan baik di UI**
   - `CooperativeDuesController::index()` sudah `paginate(20)`
   - `CooperativeLedgerController::index()` sudah `paginate(20)`
   - tetapi page Vue belum menampilkan komponen navigasi pagination

3. `Kategori simpanan` sebenarnya **sudah punya fondasi model**
   - tabel `cooperative_contribution_types` sudah ada
   - field `category`, `default_amount`, `frequency`, `is_active` sudah tersedia
   - seeder sudah menyiapkan `POKOK`, `WAJIB`, dan `SUKARELA`
   - tetapi `KHUSUS` belum ada sebagai kategori baku

4. `Ledger simpanan` saat ini masih bercampur dengan mutasi non-simpanan
   - ada `OPENING_BALANCE`
   - ada `SAVING_PAYMENT`
   - ada `POS_MEMBER_CREDIT`
   - ada `LOAN_DISBURSEMENT`
   - ada `LOAN_PAYMENT`
   - ada `SAVING_WITHDRAWAL`
   - akibatnya saldo dan histori belum bersih jika tujuan bisnisnya adalah “tabungan anggota per kategori simpanan”

5. API member untuk simpanan belum siap penuh untuk Flutter
   - `member/savings/summary` masih agregasi per `entry_type`
   - `member/savings/ledger` belum paginated
   - belum ada filter kategori simpanan
   - belum ada kontrak API yang eksplisit untuk breakdown `pokok/wajib/sukarela/khusus`

Kesimpulannya:

- kebutuhan Anda **bukan membuat dari nol**
- kebutuhan Anda adalah **menyempurnakan arsitektur domain iuran-simpanan agar konsisten dari admin sampai Flutter**

## Audit Kondisi Codebase Saat Ini

## 1. Web Route Saat Ini

Route web yang relevan:

- `routes/web.php`
  - `GET cooperative/dues`
  - `POST cooperative/dues/generate`
  - `POST cooperative/dues/mark-paid`
  - `GET cooperative/ledger`

Implikasi:

- halaman admin iuran dan ledger sudah aktif
- mekanisme generate iuran dan mark paid sudah tersedia
- tidak perlu mengubah pola routing secara total

## 2. API Route Saat Ini

Route API yang relevan:

- `routes/api.php`
  - `GET /api/v1/member/savings/summary`
  - `GET /api/v1/member/savings/ledger`
  - `GET /api/v1/member/dues/invoices`
  - `GET /api/v1/member/payments`
  - `GET /api/v1/dues/invoices`
  - `POST /api/v1/dues/generate`
  - `POST /api/v1/dues/payments`

Implikasi:

- channel API untuk admin dan member sudah tersedia
- untuk Flutter, lebih baik **memperluas kontrak endpoint yang sudah ada** daripada membuat namespace baru

## 3. Halaman `cooperative/dues`

File utama:

- `app/Http/Controllers/Cooperative/CooperativeDuesController.php`
- `resources/js/pages/Cooperative/Dues/Index.vue`
- `app/Http/Requests/Cooperative/MarkDuesPaidRequest.php`
- `app/Services/Cooperative/CooperativePaymentService.php`

### Kondisi saat ini

Sudah ada:

- filter `period`
- filter `status`
- tombol `Generate Periode`
- checkbox multi-select
- tombol batch `Sudah Membayar`
- single invoice action `Sudah Bayar`
- backend memproses banyak invoice sekaligus
- validasi `invoice_ids[]`

Belum optimal:

- tidak ada pagination UI
- tidak ada summary bar untuk item terpilih
- tidak ada preview total nominal yang akan diposting
- tidak ada filter berdasarkan anggota
- tidak ada filter berdasarkan kategori/jenis simpanan
- tidak ada mode batch yang lebih eksplisit di bagian atas halaman
- batch saat ini hanya melunasi `remaining_amount`, belum ada mode input nominal parsial per invoice

### Kesimpulan untuk `dues`

Kebutuhan multiple batch payment **perlu diteruskan**, tetapi bentuknya bukan dari nol. Yang dibutuhkan adalah:

- peningkatan UX
- perluasan filter
- endpoint/API batch yang lebih eksplisit
- kemungkinan dukungan batch partial payment bila dibutuhkan bisnis

## 4. Halaman `cooperative/ledger`

File utama:

- `app/Http/Controllers/Cooperative/CooperativeLedgerController.php`
- `resources/js/pages/Cooperative/Ledger/Index.vue`
- `app/Models/CooperativeLedgerEntry.php`

### Kondisi saat ini

Sudah ada:

- filter pencarian anggota
- filter `entry_type`
- pagination di backend
- tabel ledger dasar

Belum optimal:

- tidak ada pagination UI
- belum ada filter kategori simpanan
- belum ada filter periode
- belum ada filter anggota yang lebih presisi
- belum ada summary saldo per kategori
- belum ada pemisahan jelas antara mutasi simpanan dan non-simpanan

### Masalah domain yang lebih penting

`CooperativeLedgerEntry` saat ini berfungsi seperti ledger umum anggota, bukan khusus ledger simpanan.

Saat ini ledger bisa berisi:

- saldo awal
- pembayaran iuran/simpanan
- kredit belanja POS
- pencairan pinjaman
- pembayaran pinjaman
- penarikan simpanan

Akibatnya:

- saldo ledger total belum identik dengan “saldo simpanan”
- filter kategori simpanan tidak akan rapi jika hanya mengandalkan `entry_type`
- member app bisa menampilkan angka yang membingungkan

## 5. Model Data Saat Ini

File utama:

- `app/Models/CooperativeContributionType.php`
- `app/Models/CooperativeDuesInvoice.php`
- `app/Models/CooperativePayment.php`
- `app/Models/CooperativeLedgerEntry.php`
- `database/migrations/2026_03_07_000001_create_cooperative_tables.php`
- `database/seeders/CooperativeSeeder.php`

### Fondasi yang sudah ada

`cooperative_contribution_types` sudah memiliki:

- `code`
- `name`
- `category`
- `default_amount`
- `frequency`
- `is_active`

Seeder saat ini:

- `POKOK`
- `WAJIB`
- `SUKARELA`

### Gap terhadap kebutuhan bisnis Anda

Kebutuhan bisnis yang Anda sampaikan:

- `Simpanan Wajib` → bulanan
- `Simpanan Sukarela`
- `Simpanan Khusus`
- `Simpanan Pokok` → wajib setor saat daftar sebesar `200.000`

Gap yang ditemukan:

1. `KHUSUS` belum menjadi kategori default
2. `POKOK` di seeder saat ini masih `150000`, belum `200000`
3. ledger belum menyimpan relasi kategori kontribusi secara eksplisit
4. pelaporan dan API masih lebih dekat ke invoice/payment, belum ke “saldo simpanan per kategori”

## 6. Member Portal dan Member API Saat Ini

File utama:

- `app/Http/Controllers/MemberPortalController.php`
- `resources/js/pages/Kojayaku/Savings.vue`
- `app/Http/Controllers/Api/V1/MemberSelfServiceController.php`
- `app/Http/Resources/MemberInvoiceResource.php`
- `app/Http/Resources/MemberPaymentResource.php`

### Kondisi saat ini

Web member:

- sudah ada halaman `Kojayaku/Savings`
- sudah tampil ledger, tagihan, pembayaran
- pagination sudah ada pada web member untuk beberapa list

API member:

- `member/savings/summary` ada
- `member/savings/ledger` ada
- `member/dues/invoices` ada
- `member/payments` ada

### Gap terhadap kebutuhan Flutter

1. `member/savings/ledger` belum paginated
2. belum ada filter `category`
3. summary masih by `entry_type`, belum by `kategori simpanan`
4. belum ada API yang benar-benar fokus pada:
   - total simpanan pokok
   - total simpanan wajib
   - total simpanan sukarela
   - total simpanan khusus
5. histori anggota masih rawan bercampur dengan mutasi non-simpanan

## Jawaban Atas Pertanyaan Bisnis

## Apakah perlu dibuatkan kategori simpanan?

### Jawaban singkat

`Ya, perlu.`

Namun implementasinya bukan membuat konsep baru dari nol, melainkan:

- menstandardisasi kategori yang sebenarnya sudah ada di `cooperative_contribution_types`
- memastikan seluruh invoice, payment, ledger, member portal, dan API memakai klasifikasi yang sama

### Rekomendasi kategori baku

Gunakan 4 kategori inti berikut:

1. `POKOK`
   - nama: `Simpanan Pokok`
   - sifat: wajib saat daftar
   - nominal default: `200000`
   - frequency: `ONCE`

2. `WAJIB`
   - nama: `Simpanan Wajib`
   - sifat: wajib bulanan
   - frequency: `MONTHLY`

3. `SUKARELA`
   - nama: `Simpanan Sukarela`
   - sifat: fleksibel
   - frequency: `ADHOC`

4. `KHUSUS`
   - nama: `Simpanan Khusus`
   - sifat: kebutuhan tertentu, program tertentu, atau simpanan yang ditetapkan koperasi
   - frequency: `ADHOC` atau `MONTHLY`, tergantung aturan koperasi

### Catatan penting

Yang sebaiknya dijaga adalah pembedaan antara:

- `category` sebagai klasifikasi bisnis utama
- `contribution type` sebagai master jenis iuran/simpanan yang bisa berkembang

Contoh:

- kategori `KHUSUS` dapat memiliki beberapa type di masa depan:
  - simpanan pendidikan
  - simpanan hari raya
  - simpanan darurat

Dengan begitu model tetap scalable.

## Rencana Arsitektur Yang Direkomendasikan

## 1. Rapikan Domain Ledger

Ini adalah langkah paling penting.

### Masalah

Saat ini `cooperative_ledger_entries` belum cukup kaya untuk mendukung filter kategori simpanan secara aman.

### Rekomendasi perubahan

Tambahkan atribut klasifikasi ke ledger, minimal salah satu dari dua pendekatan berikut.

### Opsi yang direkomendasikan

Tambahkan kolom baru pada `cooperative_ledger_entries`:

- `cooperative_contribution_type_id` nullable
- `ledger_scope` nullable/string atau enum

Nilai `ledger_scope` yang direkomendasikan:

- `SAVINGS`
- `LOAN`
- `POS`
- `ADJUSTMENT`

Manfaat:

- ledger bisa difilter sebagai ledger simpanan murni
- kategori simpanan bisa diturunkan dari `cooperative_contribution_type_id`
- saldo anggota lebih akurat untuk member app
- Flutter tidak perlu menebak makna dari `entry_type`

### Mapping yang direkomendasikan

- `SAVING_PAYMENT` → `ledger_scope = SAVINGS`
- `SAVING_WITHDRAWAL` → `ledger_scope = SAVINGS`
- `OPENING_BALANCE` → `ledger_scope = SAVINGS` bila memang saldo awal simpanan
- `LOAN_DISBURSEMENT` → `ledger_scope = LOAN`
- `LOAN_PAYMENT` → `ledger_scope = LOAN`
- `POS_MEMBER_CREDIT` → `ledger_scope = POS`

### Konsekuensi desain

Setelah ini:

- halaman `cooperative/ledger` bisa punya mode `Ledger Simpanan`
- API member dapat menampilkan hanya `ledger_scope = SAVINGS`
- summary saldo tidak tercampur dengan pinjaman

## 2. Kaitkan Payment ke Kategori Simpanan

Saat payment iuran disetujui, sistem sekarang membuat ledger entry `SAVING_PAYMENT`, tetapi tidak menyimpan `contribution_type` langsung di ledger.

### Rekomendasi

Saat `CooperativePaymentService::approve()` membuat ledger:

- isi `cooperative_contribution_type_id` dari invoice
- isi `ledger_scope = SAVINGS`

Sehingga setiap mutasi pembayaran iuran/simpanan dapat langsung dipetakan ke:

- pokok
- wajib
- sukarela
- khusus

## 3. Standardisasi Kontribusi Default

Perlu pembaruan data master default:

- `POKOK` → `200000`
- `WAJIB` → nominal sesuai kebijakan koperasi
- `SUKARELA` → `0`, input manual
- `KHUSUS` → ditambahkan sebagai default master aktif

### Catatan

Perubahan ini jangan hanya di seeder, tetapi juga:

- divalidasi di admin UI
- dipakai pada generator iuran
- dipakai di laporan dan API

## 4. Perlakukan `POKOK` Sebagai Kewajiban Saat Registrasi

### Target bisnis

Setiap anggota baru wajib memiliki simpanan pokok `200000`.

### Rekomendasi implementasi

Saat anggota baru dibuat atau diaktifkan:

- sistem mengecek apakah invoice `POKOK` sudah ada
- jika belum ada, buat invoice `POKOK`
- invoice ini sekali saja (`ONCE`)

### Kenapa penting

Saat ini `DuesGenerationService` memang mendukung `ONCE`, tetapi pembuatan invoice `POKOK` masih bergantung pada generate period atau seed pattern.

Untuk go-live, `POKOK` sebaiknya menjadi bagian dari proses onboarding anggota, bukan menunggu proses bulanan.

## Rencana Fungsional Per Halaman

## A. Halaman `cooperative/dues`

### Target UX

Halaman ini menjadi pusat:

- generate tagihan
- filter tagihan
- ceklist invoice
- batch payment
- pelacakan status pembayaran

### Fitur yang direkomendasikan

#### 1. Batch action bar di atas tabel

Ketika ada invoice terpilih, tampilkan bar khusus yang menampilkan:

- jumlah invoice terpilih
- total nominal sisa tagihan
- metode pembayaran
- tanggal pembayaran
- referensi batch
- catatan batch
- tombol `Proses Batch Payment`
- tombol `Clear Selection`

#### 2. Batch payment modal

Alih-alih langsung post dari form kecil, tampilkan modal konfirmasi berisi:

- daftar anggota terpilih
- jenis simpanan
- nominal sisa per invoice
- total transaksi
- warning jika ada invoice yang sudah lunas

#### 3. Filter tambahan

Tambahkan filter:

- `member_search`
- `contribution_type_id`
- `category`
- `period`
- `status`

#### 4. Pagination UI

Gunakan komponen pagination yang sudah ada, misalnya pola dari `DataTable.vue`, atau render `links` paginator secara eksplisit.

#### 5. Dukungan batch partial payment

Ini opsional tapi sangat disarankan jika proses operasional memungkinkan pembayaran parsial banyak invoice sekaligus.

Mode yang bisa dipilih:

- `full settlement`
- `partial amount per invoice`

Jika tahap awal ingin aman, minimal implementasikan:

- `full settlement` dulu
- struktur service disiapkan agar partial bisa ditambahkan kemudian

### Kesimpulan halaman `dues`

Batch payment di `dues` tidak perlu dibuat ulang, tetapi perlu dinaikkan levelnya menjadi fitur batch operasional yang jelas dan nyaman dipakai.

## B. Halaman `cooperative/ledger`

### Target UX

Halaman ini menjadi pusat monitoring simpanan anggota, bukan sekadar dump mutasi ledger.

### Fitur yang direkomendasikan

#### 1. Filter utama

- `member_search`
- `cooperative_member_id`
- `category`
- `contribution_type_id`
- `start_date`
- `end_date`
- `ledger_scope`
- `entry_type`

#### 2. Default scope

Untuk kebutuhan simpanan, default halaman sebaiknya menampilkan:

- `ledger_scope = SAVINGS`

Dengan opsi tambahan untuk melihat semua mutasi bila dibutuhkan admin senior.

#### 3. Summary cards

Tampilkan kartu ringkasan:

- total saldo simpanan pokok
- total saldo simpanan wajib
- total saldo simpanan sukarela
- total saldo simpanan khusus
- total saldo simpanan anggota terfilter

#### 4. Breakdown per anggota

Tambahkan mode list/agregat:

- total simpanan per anggota
- total per kategori
- klik anggota untuk drill-down ke detail mutasi

#### 5. Pagination UI

Wajib ditambahkan agar data besar tetap usable.

## C. Halaman anggota / member savings

### Target

Setiap anggota dapat melihat simpanannya dengan jelas di aplikasi.

### Tampilan yang direkomendasikan

#### Ringkasan utama

- total simpanan
- total pokok
- total wajib
- total sukarela
- total khusus

#### Tab atau section

- `Ringkasan`
- `Mutasi Simpanan`
- `Tagihan`
- `Pembayaran`

#### Filter member-facing

- kategori
- rentang tanggal
- status tagihan

### Catatan

Member-facing ledger harus menampilkan mutasi yang benar-benar relevan terhadap simpanan, bukan seluruh cooperative ledger campuran.

## Rencana API Untuk Flutter

## Prinsip API

Untuk Flutter, API harus:

- paginated
- filterable
- konsisten
- allowlisted
- tidak mengembalikan model mentah yang mudah berubah

## Endpoint yang direkomendasikan

### 1. Admin / cooperative API

#### `GET /api/v1/dues/invoices`

Tambahkan dukungan query:

- `member_id`
- `member_search`
- `period`
- `status`
- `category`
- `contribution_type_id`
- `page`
- `per_page`

#### `POST /api/v1/dues/payments/batch`

Endpoint baru yang direkomendasikan untuk proses batch:

Payload minimum:

```json
{
  "invoice_ids": [1, 2, 3],
  "payment_method": "TRANSFER",
  "paid_at": "2026-06-04",
  "reference_no": "BATCH-20260604-01",
  "notes": "Batch payment operator"
}
```

Payload versi lanjut:

```json
{
  "items": [
    { "invoice_id": 1, "amount": 50000 },
    { "invoice_id": 2, "amount": 35000 }
  ],
  "payment_method": "TRANSFER",
  "paid_at": "2026-06-04",
  "reference_no": "BATCH-20260604-01",
  "notes": "Batch payment operator"
}
```

#### `GET /api/v1/savings/ledger`

Endpoint admin baru atau perluasan endpoint cooperative existing:

filter:

- `member_id`
- `member_search`
- `category`
- `contribution_type_id`
- `ledger_scope`
- `start_date`
- `end_date`
- `page`
- `per_page`

#### `GET /api/v1/savings/categories`

Balikkan daftar category/type yang aktif:

- pokok
- wajib
- sukarela
- khusus

### 2. Member / Flutter API

#### `GET /api/v1/member/savings/summary`

Ubah contract menjadi:

```json
{
  "data": {
    "total_balance": 1250000,
    "by_category": {
      "POKOK": 200000,
      "WAJIB": 600000,
      "SUKARELA": 300000,
      "KHUSUS": 150000
    },
    "pending_invoices": 2,
    "pending_invoice_amount": 100000
  }
}
```

#### `GET /api/v1/member/savings/ledger`

Tambahkan:

- pagination
- filter kategori
- filter tanggal
- hanya mutasi `ledger_scope = SAVINGS`

Contoh query:

- `/api/v1/member/savings/ledger?category=WAJIB&page=1&per_page=15`

#### `GET /api/v1/member/dues/invoices`

Tambahkan filter:

- `status`
- `category`
- `period`

#### `GET /api/v1/member/payments`

Tambahkan filter:

- `category`
- `status`
- `start_date`
- `end_date`

## Rencana Perubahan Data dan Migrasi

## 1. Migrasi Schema

### Perubahan minimum yang direkomendasikan

Tambahkan kolom ke `cooperative_ledger_entries`:

- `cooperative_contribution_type_id` nullable
- `ledger_scope` nullable/string

Tambahkan index:

- `cooperative_member_id + ledger_scope + posted_at`
- `cooperative_contribution_type_id + posted_at`

### Optional enhancement

Jika ingin lebih kuat:

- tambah `category_snapshot`

Fungsinya agar laporan historis tetap stabil walau master type berubah di masa depan.

## 2. Backfill Data

Data lama harus dimigrasikan hati-hati.

### Mapping yang direkomendasikan

#### Dari payment iuran

Jika ledger entry berasal dari `CooperativePayment` dan payment punya invoice:

- isi `cooperative_contribution_type_id` dari invoice
- isi `ledger_scope = SAVINGS`

#### Dari withdrawal sukarela

Jika source dari `SavingsWithdrawal`:

- isi `ledger_scope = SAVINGS`
- kategori bisa diarahkan ke `SUKARELA` bila bisnis setuju

#### Dari opening balance

Ini perlu keputusan bisnis:

- apakah opening balance dianggap saldo awal total tanpa kategori
- atau harus dipecah ke kategori tertentu

Rekomendasi aman:

- simpan sebagai `ledger_scope = SAVINGS`
- `cooperative_contribution_type_id = null`
- exclude dari breakdown kategori sampai ada data mapping manual

#### Dari loan dan POS

- set `ledger_scope = LOAN` untuk `LOAN_DISBURSEMENT` dan `LOAN_PAYMENT`
- set `ledger_scope = POS` untuk `POS_MEMBER_CREDIT`

## 3. Seeder dan Master Data

Update `CooperativeSeeder`:

- `POKOK = 200000`
- tambah `KHUSUS`

Tambahkan seeder master yang lebih eksplisit agar data default konsisten di seluruh environment.

## Rencana Implementasi Per Fase

## Fase 1 - Normalisasi Domain Data

Tujuan:

- rapikan kategori dan ledger

Pekerjaan:

- tambah kolom ledger
- backfill data existing
- update service posting ledger
- update seed kategori default
- finalisasi aturan `POKOK`

Output:

- ledger siap dipakai untuk pelaporan simpanan
- saldo per kategori bisa dihitung dengan aman

## Fase 2 - Penyempurnaan Halaman `dues`

Tujuan:

- batch payment operasional dan pagination usable

Pekerjaan:

- batch action bar
- modal konfirmasi batch
- filter anggota dan kategori
- pagination UI
- optional endpoint batch dedicated

Output:

- operator dapat memproses banyak invoice dengan alur yang jelas

## Fase 3 - Penyempurnaan Halaman `ledger`

Tujuan:

- ledger admin benar-benar mendukung monitoring simpanan

Pekerjaan:

- filter kategori
- filter periode
- summary cards
- mode default `SAVINGS`
- pagination UI

Output:

- admin bisa melihat total simpanan per anggota dan per kategori

## Fase 4 - Member Portal dan Flutter API

Tujuan:

- anggota bisa melihat simpanan secara jelas
- Flutter bisa langsung pakai API

Pekerjaan:

- ubah summary by category
- paginasi ledger API
- filter kategori
- harmonisasi response contract
- update web member savings page

Output:

- web anggota dan Flutter memakai struktur data yang sama

## Fase 5 - Testing, UAT, dan Go-Live

Pekerjaan:

- tambah/update feature tests web
- tambah/update feature tests API
- test data migration/backfill
- UAT dengan skenario operator
- UAT dengan skenario anggota

Output:

- release aman untuk produksi

## File Yang Hampir Pasti Tersentuh Saat Implementasi

### Backend

- `routes/web.php`
- `routes/api.php`
- `app/Http/Controllers/Cooperative/CooperativeDuesController.php`
- `app/Http/Controllers/Cooperative/CooperativeLedgerController.php`
- `app/Http/Controllers/Api/V1/CooperativeDuesApiController.php`
- `app/Http/Controllers/Api/V1/CooperativePaymentApiController.php`
- `app/Http/Controllers/Api/V1/MemberSelfServiceController.php`
- `app/Models/CooperativeContributionType.php`
- `app/Models/CooperativeDuesInvoice.php`
- `app/Models/CooperativeLedgerEntry.php`
- `app/Models/CooperativePayment.php`
- `app/Services/Cooperative/DuesGenerationService.php`
- `app/Services/Cooperative/CooperativePaymentService.php`
- `app/Services/Cooperative/SavingsWithdrawalService.php`
- `app/Services/Cooperative/CooperativeOpeningBalanceService.php`

### Frontend Web

- `resources/js/pages/Cooperative/Dues/Index.vue`
- `resources/js/pages/Cooperative/Ledger/Index.vue`
- `resources/js/pages/Kojayaku/Savings.vue`
- kemungkinan memanfaatkan `resources/js/components/ui/data-table/DataTable.vue`

### Database

- migration baru untuk ledger classification
- seeder cooperative master data

### Tests

- `tests/Feature/Cooperative/CooperativeFeatureTest.php`
- `tests/Feature/Phase1MemberSelfServiceApiTest.php`
- test baru khusus ledger by category dan batch API bila diperlukan

## Risiko dan Mitigasi

## Risiko 1 - Saldo lama tidak langsung cocok per kategori

Penyebab:

- ledger lama belum punya kategori eksplisit

Mitigasi:

- lakukan backfill bertahap
- tandai opening balance sebagai uncategorized sementara
- validasi hasil dengan sampel anggota

## Risiko 2 - Batch payment membingungkan operator

Penyebab:

- terlalu banyak mode batch sejak awal

Mitigasi:

- tahap 1 pakai full settlement dahulu
- partial payment dijadikan fase lanjutan bila diperlukan

## Risiko 3 - Contract API Flutter berubah setelah app berjalan

Penyebab:

- endpoint existing masih ada yang belum stabil secara bisnis

Mitigasi:

- finalisasi contract response sebelum implementasi Flutter
- gunakan Resource yang allowlisted
- dokumentasikan payload yang final

## Risiko 4 - Laporan saldo member berbeda antara admin dan app

Penyebab:

- sumber data ringkasan dan ledger tidak seragam

Mitigasi:

- satu sumber kebenaran: ledger `SAVINGS` + contribution type
- hindari perhitungan campuran dari invoice saja

## Rekomendasi Prioritas

Urutan prioritas yang paling aman:

1. rapikan klasifikasi ledger
2. standardisasi kategori simpanan
3. perbaiki `dues` UI batch + pagination
4. perbaiki `ledger` filter + pagination + summary
5. finalisasi API member/admin untuk Flutter
6. baru lanjut ke implementasi Flutter

## Keputusan Yang Perlu Disepakati Sebelum Eksekusi

Beberapa hal perlu disepakati agar implementasi tidak salah arah:

1. apakah `Simpanan Khusus` selalu manual, atau bisa digenerate periodik
2. apakah batch payment cukup `full settlement`, atau wajib mendukung `partial per invoice`
3. apakah saldo awal lama perlu dipecah ke kategori, atau boleh tetap sebagai saldo awal umum
4. apakah `ledger` admin akan fokus hanya simpanan, atau tetap bisa melihat mode campuran semua mutasi
5. apakah nominal `Simpanan Wajib` juga akan dibakukan sekarang

## Rekomendasi Final

Untuk kebutuhan yang Anda sampaikan, pendekatan terbaik adalah:

- `ya`, gunakan kategori simpanan sebagai tulang punggung domain
- jangan hanya tambahkan checklist atau filter UI
- rapikan juga struktur ledger dan contract API
- jadikan `cooperative/ledger` sebagai pusat data simpanan yang siap dibaca admin dan Flutter

Jika rencana ini diikuti, maka hasil akhirnya akan memenuhi tujuan bisnis:

- operator bisa melakukan batch payment dengan nyaman
- admin bisa memfilter simpanan per anggota dan per kategori
- setiap anggota bisa melihat simpanannya dengan jelas
- Flutter bisa memakai API yang konsisten tanpa perlu redesign backend lagi
