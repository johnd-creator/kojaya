# Panduan Pengguna — Admin Koperasi

> **Target peran:** `Admin Koperasi` (Spatie role: `Admin Koperasi`)
> **Permission utama:** `view_cooperative_member`, `manage_cooperative_member`, `validate_cooperative_member`, `verify_cooperative_member`, `manage_cooperative_dues`, `manage_cooperative_payment`, `view_cooperative_loan`, `manage_cooperative_loan`, `access_cooperative_pos`, `manage_cooperative_points`, `manage_cooperative_rewards`, `manage_cooperative_redemption`, `manage_cooperative_shu`, `manage_cooperative_loan_types`, `manage_pos_categories`, `manage_pos_products`, `view_pos_reports`, `view_cooperative_ledger`, `export_cooperative_member`, `review_cooperative_resignation`, `approve_pos_void`, `manage_cooperative_opening_balance`, `view_store_credit`, `manage_store_credit`, `cashier_store_credit`, `report_store_credit`.
> **Prefix route:** `cooperative.*` (lihat `routes/web.php`).

Sumber otoritatif artikel Markdown-versi: [`Database\Seeders\DocumentationArticleSeeder`](../database/seeders/DocumentationArticleSeeder.php) dengan `target_role = admin_koperasi`.

## 1. Dashboard Operasional Admin Koperasi

Grup middleware `cooperative` (lihat `routes/web.php` baris
`Route::prefix('cooperative')->name('cooperative.')`) membatasi akses
ke semua route dengan prefix `cooperative.`.

### Pintasan utama

- **Operator Dashboard** → `route('cooperative.operator.dashboard')`
  (entry point pengalaman harian Admin Koperasi).
- **Daftar Anggota** → `route('cooperative.members.index')`,
  `route('cooperative.members.create')`,
  `route('cooperative.members.edit')`, dan
  `route('cooperative.members.show')`.
- **Pengunduran Diri** →
  `route('cooperative.members.resignations.index')`
  (perlu permission `review_cooperative_resignation`).
- **Saldo Pembuka** → `route('cooperative.members.opening-balance.show')`
  (perlu permission `manage_cooperative_opening_balance`).
- **Iuran** → `route('cooperative.dues.index')`.
- **Pinjaman** → daftar `route('cooperative.loans.index')`, buat
  `route('cooperative.loans.create')`, detail
  `route('cooperative.loans.show')`, kalkulator
  `route('cooperative.loans.calculator')`, jenis
  `route('cooperative.loan-types.index')`.
- **POS & Inventori** → `route('cooperative.pos.index')`,
  `route('cooperative.pos-products.index')`,
  `route('cooperative.pos-categories.index')`,
  `route('cooperative.pos.inventory.receipts.index')`,
  `route('cooperative.pos.inventory.transfers.index')`, dan
  `route('cooperative.pos.inventory.counts.index')`.

## 2. Mengelola Jenis Pinjaman

Permission yang dibutuhkan: `manage_cooperative_loan_types`.

1. Buka **Pinjaman → Jenis Pinjaman** →
   `route('cooperative.loan-types.index')`.
2. Klik **Buat**; submit form yang men-`POST` ke
   `route('cooperative.loan-types.store')`. Tidak ada route
   `cooperative.loan-types.create` terpisah — UI memuat form
   lewat Inertia dan submit langsung ke `store`.
3. Field minimal: nama, bunga efektif, tenor maksimum, dan
   dokumen wajib (lihat `LoanTypeRequest`).
4. Perubahan langsung berlaku untuk pinjaman baru, tidak
   memengaruhi angsuran yang sudah jalan.

### Validasi

- Nama: unik per cooperative.
- Bunga: 0 ≤ bunga ≤ 100.
- Tenor: 1 ≤ tenor ≤ 360 bulan.
- Dokumen: minimal 1 jenis.

### Verifikasi

Update melalui `route('cooperative.loan-types.update')` lalu cek
`LoanTypePolicy@update` mengembalikan `true` untuk role
`Admin Koperasi`.

## 3. Operasi Harian POS, Inventori, dan Setoran Kasir

### POS

- Buka shift: `route('cooperative.pos.shifts.index')` lalu
  `route('cooperative.pos.shifts.open')`.
- Catat order: `route('cooperative.pos.transactions.store')` dan
  pantau di `route('cooperative.pos.transactions.index')`.
- Pantauan coffee orders:
  `route('cooperative.pos.coffee-orders.index')` dan ubah status
  lewat `route('cooperative.pos.coffee-orders.update-status')`.
- Void: `route('cooperative.pos.void-requests.index')` (perlu
  permission `approve_pos_void`) lalu proses via
  `route('cooperative.pos.void-requests.process')`.
- Retur: `route('cooperative.pos.returns.create')` (per
  transaksi) lalu simpan di
  `route('cooperative.pos.returns.store')`.
- Kredit/angsuran anggota:
  `route('cooperative.pos.credit.create')` dan
  `route('cooperative.pos.credit.store')`.

### Setoran kasir

Setelah shift, tutup lewat
`route('cooperative.pos.shifts.close')`. Setoran harian kemudian
direkam di `route('cooperative.pos.closings.index')` dan
ditutup via `route('cooperative.pos.closings.close')` (perlu
permission `view_pos_reports`).

### Inventori

- Stok opname: `route('cooperative.pos.inventory.counts.index')`,
  `route('cooperative.pos.inventory.counts.create')`,
  `route('cooperative.pos.inventory.counts.show')`.
- Penerimaan barang:
  `route('cooperative.pos.inventory.receipts.index')` /
  `route('cooperative.pos.inventory.receipts.create')`.
- Transfer gudang:
  `route('cooperative.pos.inventory.transfers.index')` /
  `route('cooperative.pos.inventory.transfers.create')`.

### Laporan

- Laporan POS: `route('cooperative.pos.reports.index')`.
- Laporan koperasi: `route('cooperative.reports.index')`.
- SHU: `route('cooperative.shu.index')`.
