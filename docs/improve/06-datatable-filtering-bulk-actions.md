# Plan 06 - Advanced Datatable, URL Filter, dan Bulk Actions

## Tujuan

Meningkatkan efisiensi halaman daftar yang berisi data besar dengan filter URL, sorting, pagination yang stabil, sticky header, row highlight, dan bulk actions untuk pekerjaan massal.

## Prioritas

P1. Penting untuk operasional data besar, tetapi perlu kontrak frontend-backend yang konsisten.

## Sumber Dari new_improve.md

- Frontend: Infinite Scrolling / Optimized Pagination
- Workflow UX: Advanced Datatables
- Phase 2: Datatable Upgrades
- Phase 3: Bulk Actions

## Baseline Repo

- `resources/js/components/ui/data-table/DataTable.vue` sudah ada.
- `resources/js/components/FilterBar.vue` dan `resources/js/composables/useTableFilters.ts` sudah mulai dipakai.
- Beberapa halaman sudah migrasi ke `DataTable`, tetapi adopsi belum tentu merata.

## Scope

Termasuk:

- Audit halaman list yang masih punya filter manual duplikatif.
- Standardisasi filter di URL.
- Tambah sorting server-side bila memang dibutuhkan.
- Tambah bulk action pada halaman yang punya kebutuhan operasional nyata.
- Tambah sticky header/row highlight untuk scanability.

Tidak termasuk:

- Infinite scroll untuk semua tabel. Gunakan hanya jika pagination biasa tidak cukup.
- Bulk delete luas tanpa permission dan konfirmasi kuat.

## Kandidat Modul

Prioritaskan:

- Cooperative payments dan dues.
- Cooperative members.
- POS transactions dan products.
- Loans approval.
- HR leave/overtime/reimbursement.
- Audit logs dan reports.

## Langkah Implementasi

1. Pilih satu halaman daftar.
2. Cek query parameter yang sudah ada dan pastikan tidak memutus link lama.
3. Pindahkan filter ke `FilterBar` atau composable yang sudah ada jika cocok.
4. Pastikan `router.get()` memakai `preserveState`, `preserveScroll`, dan query bersih.
5. Untuk sorting, whitelist kolom di backend agar tidak raw order bebas.
6. Untuk bulk actions:
   - Tambah checkbox selection.
   - Tambah toolbar bulk ketika ada selection.
   - Tambah confirm dialog.
   - Backend harus validasi permission dan status setiap item.
7. Tambah test untuk filter/sort/bulk action.

## Acceptance Criteria

- Filter bisa dibagikan via URL dan survive refresh.
- Pagination mempertahankan filter/sort.
- Backend tidak menerima sort field atau bulk action yang tidak diizinkan.
- Bulk action menolak item yang statusnya tidak valid.
- Empty/loading state tetap konsisten setelah filter berubah.

## Verifikasi Minimal

- Test feature halaman yang disentuh.
- `npm run build`
- Manual smoke: filter, sort, pagination, refresh URL, bulk action sukses/gagal.

## Risiko

- Infinite scroll dengan data finansial bisa mengurangi akurasi konteks. Untuk laporan dan approval, pagination eksplisit sering lebih aman.
- Bulk action rentan salah operasi. Gunakan preview jumlah item dan konfirmasi jelas.
- Filter URL yang terlalu panjang perlu dibatasi untuk query penting saja.

