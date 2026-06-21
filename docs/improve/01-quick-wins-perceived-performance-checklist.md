# Checklist Audit - Quick Wins Perceived Performance

Tanggal audit: 2026-06-21
Auditor: Codex
Target plan: `docs/improve/01-quick-wins-perceived-performance.md`

## Ringkasan Status

Implementasi Deepseek sudah menutup sebagian besar scope P0 untuk perceived performance:

- Sidebar navigation utama sudah memakai Inertia `prefetch`.
- Dashboard, Reports, Payroll, dan beberapa halaman Cooperative sudah memakai `Deferred` dengan fallback skeleton.
- Empty state sudah memakai `EmptyState.vue` atau shared `DataTable` pada beberapa area prioritas.

Namun belum sepenuhnya sesuai acceptance criteria karena masih ada shared loading state yang menampilkan spinner + teks `Loading data...`, beberapa komponen report masih memakai `alert()`, dan audit visual feedback belum dicatat sebagai daftar kandidat migrasi toast.

Status keseluruhan: **Perlu follow-up ringan sebelum Plan 01 dianggap selesai.**

## Checklist Kesesuaian

| Item dari Plan 01 | Status | Bukti Scan | Catatan |
| --- | --- | --- | --- |
| Inventaris link navigasi utama di sidebar dan top-level action | Selesai sebagian | `resources/js/components/AppSidebar.vue`, `resources/js/components/NavMain.vue` | Sidebar utama sudah tersentral di `NavMain`, tetapi belum ada catatan audit eksplisit untuk top-level action non-sidebar. |
| Link Inertia utama memakai `prefetch` untuk GET navigation aman | Selesai | `resources/js/components/NavMain.vue` memakai `<Link ... prefetch>` untuk item dan subitem; logo di `AppSidebar.vue` juga `prefetch`. | Parent collapsible dengan `href: "#"` tidak diprefetch karena hanya trigger grup, ini benar. |
| Dashboard deferred/loading memakai skeleton stabil | Selesai | `resources/js/pages/Dashboard.vue` memakai `<Deferred data="dashboard">` dengan skeleton dimensi tetap. | Area work queue dan management links juga memakai `prefetch`. |
| Reports deferred/loading memakai skeleton stabil | Selesai sebagian | `resources/js/pages/Reports.vue` memakai `<Deferred data="reports">` + skeleton cards. | Modal report generator masih punya `alert()` dan spinner button, perlu masuk gap visual feedback. |
| Payroll deferred/loading memakai skeleton stabil | Selesai sebagian | `resources/js/pages/Payroll/Index.vue` memakai `<Deferred data="stats">` + skeleton card. | Tabel payroll memakai shared `DataTable`, tetapi shared loading `DataTable` masih spinner + `Loading data...`. |
| Cooperative halaman prioritas punya deferred/skeleton | Selesai sebagian | `resources/js/pages/Cooperative/Dues/Index.vue`, `resources/js/pages/Cooperative/Members/Index.vue`, `resources/js/pages/Cooperative/Operator/Dashboard.vue` punya skeleton pada KPI/stats/loading. | Halaman Cooperative lain yang memakai `DataTable` masih mewarisi gap loading shared table. |
| Empty state konsisten memakai `EmptyState.vue` atau pola sepadan | Selesai sebagian | `Dashboard.vue`, `Cooperative/Dues/Index.vue`, `Cooperative/Members/Index.vue`, dan `DataTable.vue` memakai `EmptyState`. | Sudah cukup untuk empty state, tetapi loading state table belum setara. |
| Tidak ada loading text user-facing pada area prioritas | Belum selesai | `resources/js/components/ui/data-table/DataTable.vue` masih menampilkan `Loading data...`; `resources/js/components/Report/PayslipViewer.vue` masih `Loading payslip...`. | Ini gap langsung terhadap langkah implementasi nomor 5. |
| Audit flash/alert statis dan kandidat migrasi toast | Belum selesai | `resources/js/components/Report/ReportGenerator.vue`, `ReportGeneratorForm.vue`, `PayslipViewer.vue` masih memakai `alert()`. | Karena scope Plan 01 meminta audit/tandai kandidat, minimal dokumenkan kandidat ini atau migrasikan ke toast di Plan 04. |
| Tidak ada perubahan behavior data | Sesuai | Scan tidak menunjukkan perubahan kontrak data dari item quick-win; perubahan yang tampak dominan frontend feedback/prefetch. | Tetap valid selama follow-up hanya menyentuh loading/toast UI. |

## Gap yang Harus Ditutup

### 1. Shared DataTable masih memakai spinner dan teks loading

Lokasi:

- `resources/js/components/ui/data-table/DataTable.vue`

Masalah:

- Baris loading masih berupa spinner `Loader2` dan teks `Loading data...`.
- Ini melanggar acceptance criteria: loading state harus terasa stabil, tidak kosong panjang, dan tidak memakai loading text generik.
- Karena `DataTable` dipakai di Payroll dan banyak halaman Cooperative/POS, gap ini berdampak luas.

Arahan perbaikan:

- Ganti baris loading di `DataTable.vue` menjadi skeleton rows dengan jumlah baris tetap, misalnya 5 baris.
- Skeleton harus mengikuti jumlah kolom agar struktur tabel tetap terasa nyata.
- Pertahankan `aria-live="polite"` atau teks screen-reader-only berbahasa Indonesia, misalnya `Memuat data tabel.`.
- Jangan ubah API prop `loading`, `columns`, atau `data`.

Contoh arah implementasi:

```vue
<tr v-if="loading" aria-live="polite">
  <td :colspan="columns.length" class="p-0">
    <span class="sr-only">Memuat data tabel.</span>
    <div class="space-y-2 p-4">
      <div v-for="row in 5" :key="row" class="grid gap-3" :style="{ gridTemplateColumns: `repeat(${columns.length}, minmax(0, 1fr))` }">
        <Skeleton v-for="column in columns" :key="column.key" class="h-5 rounded-md" />
      </div>
    </div>
  </td>
</tr>
```

Sesuaikan detail markup dengan struktur `DataTable.vue` saat implementasi agar tidak merusak table semantics.

### 2. Report modal masih memakai `alert()`

Lokasi:

- `resources/js/components/Report/ReportGenerator.vue`
- `resources/js/components/Report/ReportGeneratorForm.vue`
- `resources/js/components/Report/PayslipViewer.vue`

Masalah:

- `alert()` masih dipakai untuk validasi report, report type tidak dikenal, dan error generate/download.
- Plan 01 meminta audit alert tradisional dan menandai kandidat migrasi toast. Saat ini belum ada catatan kandidat selain hasil scan ini.

Arahan perbaikan:

- Untuk menutup Plan 01 secara minimal: tambahkan catatan kandidat ini ke checklist/issue Plan 04, tanpa mengubah behavior.
- Jika ingin langsung diperbaiki: migrasikan ke toast/non-blocking notification sesuai pola toast yang sudah ada di project.
- Pesan validasi harus muncul di modal, bukan browser blocking alert.
- Error async report harus tetap mencatat detail teknis ke console bila diperlukan, tetapi user melihat pesan ringkas.

### 3. Payslip viewer masih memakai spinner dan teks loading

Lokasi:

- `resources/js/components/Report/PayslipViewer.vue`

Masalah:

- Loading iframe PDF masih menampilkan spinner dan teks `Loading payslip...`.
- Ini masuk area Reports, salah satu halaman prioritas Plan 01.

Arahan perbaikan:

- Ganti loader tengah dengan skeleton frame yang menyerupai viewer PDF.
- Gunakan dimensi penuh area viewer agar layout tidak berubah saat iframe siap.
- Gunakan `sr-only` untuk status loading, bukan teks visual generik.

### 4. Top-level action prefetch belum diaudit lengkap

Lokasi yang sudah sesuai:

- `resources/js/pages/Dashboard.vue`
- `resources/js/components/dashboard/GradientKpiCard.vue`
- `resources/js/components/dashboard/SectionHeader.vue`
- `resources/js/pages/Cooperative/Members/Index.vue`
- `resources/js/pages/Cooperative/Members/Show.vue`
- `resources/js/pages/Cooperative/Members/Edit.vue`
- `resources/js/pages/Cooperative/Dues/Index.vue`

Masalah:

- Banyak link read-only utama sudah memakai `prefetch`, tetapi belum ada daftar audit untuk semua top-level action di Cooperative, POS, Payroll, dan Reports.
- Ini bukan bug teknis besar, tetapi acceptance criteria menyebut sidebar dan link aksi utama.

Arahan perbaikan:

- Audit semua `<Link>` pada halaman prioritas.
- Tambah `prefetch` hanya pada GET navigation detail/index/report read-only.
- Jangan tambahkan `prefetch` pada link/action POST, PUT, PATCH, DELETE, form submit, approval, void, return, atau aksi yang memicu side effect.

## Checklist Follow-up untuk Menutup Plan 01

- [ ] Ubah loading state `DataTable.vue` dari spinner + `Loading data...` menjadi skeleton rows berdimensi stabil.
- [ ] Ubah loading state `PayslipViewer.vue` dari spinner + `Loading payslip...` menjadi skeleton frame PDF.
- [ ] Tandai atau migrasikan `alert()` di komponen Report ke toast/non-blocking UI.
- [ ] Audit ulang `<Link>` di Dashboard, Cooperative, POS, Payroll, dan Reports; tambahkan `prefetch` hanya untuk GET navigation yang aman.
- [ ] Pastikan tidak ada `Loading data...` atau loading text generik yang terlihat di halaman prioritas.
- [ ] Pastikan empty state tetap memakai `EmptyState.vue` atau pola sepadan setelah perubahan loading.
- [ ] Jalankan `npm run build`.
- [ ] Jalankan test minimal: `php artisan test --compact tests/Feature/DashboardTest.php tests/Feature/P1ArchitectureTest.php`.
- [ ] Manual smoke: buka Dashboard, Cooperative Members, Cooperative Dues, POS Transactions, Payroll, Reports; pastikan tidak ada console error dan layout tidak meloncat saat data loading.

## Kesimpulan

Deepseek sudah mengerjakan inti quick wins dengan benar untuk prefetch sidebar dan skeleton pada deferred props utama. Plan 01 belum boleh ditandai selesai penuh sampai shared table loading, report alert, dan payslip loading ditutup atau minimal dicatat sebagai kandidat migrasi yang jelas.
