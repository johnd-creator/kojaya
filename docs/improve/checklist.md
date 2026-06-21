# Audit Checklist Deepseek - Improve Plan 01 sampai 04

Tanggal audit: 2026-06-21  
Auditor: Codex  
Sumber audit: `git diff`, scan codebase, dokumen `docs/improve/01-*.md` sampai `04-*.md`, build, dan targeted PHPUnit.

## Ringkasan Keputusan

Deepseek sudah menutup gap teknis utama dari audit sebelumnya. POS report deferred prop dan filter query sudah dikunci test, audit `prefetch` sudah didokumentasikan, audit N+1/cache sudah dibuat, `PayslipViewer` sudah memakai dialog primitive, dan targeted verification lulus.

Status keseluruhan: **Plan 01-04 bisa dianggap selesai secara fungsional.**

Sisa yang masih perlu dibereskan sebelum commit:

1. **P1:** review generated Wayfinder diff yang sangat besar. Jika tidak relevan dengan perubahan route/controller, jangan ikutkan ke commit.
2. **P1:** jalankan `vendor/bin/pint --dirty --format agent` karena ada perubahan PHP.
3. **P2:** audit cache master data belum implementasi, tetapi sudah benar ditempatkan sebagai kandidat lanjutan, bukan klaim selesai implementasi.

## Checklist Plan 01 - Quick Wins Perceived Performance

| Item | Status terbaru | Bukti | Catatan |
| --- | --- | --- | --- |
| Sidebar dan navigasi utama pakai `prefetch` | Selesai | `resources/js/components/NavMain.vue`, `resources/js/components/AppSidebar.vue` | Link sidebar dan logo sudah memakai `prefetch`. |
| Link aksi utama read-only diaudit dan diberi `prefetch` | Selesai | `docs/improve/prefetch-audit.md` | Audit sudah memisahkan link GET read-only yang aman dan action side-effect yang tidak boleh diprefetch. |
| Loading table bukan spinner/teks | Selesai | `resources/js/components/ui/data-table/DataTable.vue` | `Loading data...` diganti skeleton rows dengan `sr-only` status. |
| Payslip loading bukan spinner/teks | Selesai | `resources/js/components/Report/PayslipViewer.vue` | Loader PDF sudah skeleton frame. |
| Empty state pakai pola shared dan tidak generik | Selesai | `DataTable.vue`, POS trend empty state | Default shared sudah Bahasa Indonesia dan POS trend memakai `EmptyState` kontekstual. |
| Tidak ada raw browser dialog di `resources/js` | Selesai | `rg "alert\\(|confirm\\(|prompt\\(" resources/js` tidak menemukan hasil | Pertahankan agar regression tidak masuk lagi. |

Verdict Plan 01: **Selesai.**

## Checklist Plan 02 - Inertia Data Loading dan Deferred Props

| Item | Status terbaru | Bukti | Catatan |
| --- | --- | --- | --- |
| Cooperative report summary dipindah ke deferred prop | Selesai | `CooperativeReportController.php`, `resources/js/pages/Cooperative/Reports.vue`, `CooperativeReportDeferredTest.php` | Test `loadDeferredProps('summary')` lulus. |
| POS report analytics dipindah ke deferred prop | Selesai | `PosReportController.php`, `resources/js/pages/Cooperative/Pos/Reports/Index.vue`, `PosPhase4ReportsTest.php` | Semua referensi template utama memakai `analytics.*`. |
| Fallback skeleton tersedia untuk deferred props baru | Selesai | `Cooperative/Reports.vue`, `Cooperative/Pos/Reports/Index.vue` | Skeleton tersedia dan build lulus. |
| Filter tetap sinkron setelah deferred props | Selesai | `test_pos_reports_filters_sync_from_query_params()` | Initial page assert `filters.payment_method` dan `filters.cashier_id`, lalu deferred analytics tetap diload. |
| Test deferred props baru ditambahkan | Selesai | `CooperativeReportDeferredTest.php`, `PosPhase4ReportsTest.php` | Targeted PHPUnit lulus 13 test / 148 assertions. |

Verdict Plan 02: **Selesai.**

## Checklist Plan 03 - Backend Query, Cache, dan Database Indexing

| Item | Status terbaru | Bukti | Catatan |
| --- | --- | --- | --- |
| `Model::preventLazyLoading()` aktif non-production | Selesai | `app/Providers/AppServiceProvider.php` | Sesuai Plan 03. |
| Eager loading N+1 diaudit | Selesai sebagai audit | `docs/improve/n1-cache-audit.md` | Dokumen mencatat modul yang dicek dan eager loading yang terkonfirmasi. |
| Cache operasional tanpa invalidation | Selesai ditutup | Scan cache tidak menemukan cache report baru | Keputusan benar: tidak cache dashboard/NPL tanpa invalidation. |
| Cache master data jarang berubah | Dicatat sebagai kandidat lanjutan | `docs/improve/n1-cache-audit.md` | Belum diimplementasikan, tetapi sudah diberi key, TTL, dan invalidation write path kandidat. Ini sesuai scope audit; implementasi cache harus tetap plan lanjutan. |
| Migration index terarah | Selesai | `2026_06_21_075028_add_status_validation_status_index_to_cooperative_members.php` | Index `status, validation_status` sudah ada. |
| Test cache/index ditambahkan | Cukup untuk scope sekarang | Targeted tests berbasis `DatabaseMigrations` lulus | Tidak ada cache baru yang perlu invalidation test. Migration ter-smoke lewat test migration. |

### Arahan Lanjutan Plan 03

Jangan langsung implement cache master data hanya karena kandidat sudah ada. Implementasi cache baru harus disertai invalidation model event atau service write path. Prioritas implementasi hanya jika ada bukti latency/dropdown sering dipanggil.

Verdict Plan 03: **Selesai untuk audit dan blocker. Cache master data menjadi kandidat lanjutan, bukan gap penutupan Plan 03 saat ini.**

## Checklist Plan 04 - UI Feedback, Empty State, Toast, dan Slide-over

| Item | Status terbaru | Bukti | Catatan |
| --- | --- | --- | --- |
| Raw `alert()`, `confirm()`, `prompt()` dihapus dari `resources/js` | Selesai | Scan `rg` tidak menemukan raw dialog | Bagus. |
| Toaster global tersedia | Selesai | `AppSidebarLayout.vue`, `useToast.ts`, `Toaster.vue` | Toaster global dipasang di layout app sidebar. |
| Flash success/error/warning/status masuk toast | Selesai | `AppSidebarLayout.vue` | Support `success`, `error`, `warning`, `status`. |
| Dedupe toast global | Selesai | `useToast.ts` | Dedupe 1500ms berdasarkan variant/title/description. |
| Tombol close toast punya aria label | Selesai | `Toaster.vue` | `aria-label="Tutup notifikasi"` tersedia. |
| Error report tidak lagi browser alert | Selesai | `ReportGenerator.vue`, `ReportGeneratorForm.vue`, `PayslipViewer.vue` | Inline error sudah `role="alert"`. |
| Inline file error yang menggantikan alert aksesibel | Selesai | `CertificateForm.vue`, `McuForm.vue` | `role="alert"`, pesan Bahasa Indonesia, dan invalid input reset. |
| Empty state tidak generik | Selesai | `DataTable.vue`, POS report empty trend | Gap lama tertutup. |
| Modal/dialog focus dan aria tidak rusak | Selesai | `PayslipViewer.vue` memakai `Dialog`, `DialogContent`, `DialogTitle`, `DialogClose` | Menggunakan primitive dialog project, jadi focus trap/Escape/restore focus mengikuti komponen dialog yang sudah ada. |

Verdict Plan 04: **Selesai.**

## Catatan Tentang Generated Wayfinder Files

Diff masih berisi perubahan generated di:

- `resources/js/actions/**`
- `resources/js/routes/**`

`npm run build` menjalankan Wayfinder generation, jadi perubahan ini bisa muncul karena build. Namun jumlah file generated yang berubah sangat besar dibanding scope Plan 01-04. Deepseek harus review sebelum commit:

- Jika route/controller signature memang berubah dan generated output perlu update, commit boleh ikut.
- Jika perubahan hanya noise dari build lokal/formatter generator, keluarkan dari commit UX/performance.
- Jangan review manual baris per baris; cukup validasi apakah perubahan generated punya sebab nyata.

## Verifikasi Codex

Perintah yang dijalankan pada audit ulang ini:

```bash
npm run build
php artisan test --compact tests/Feature/Cooperative/CooperativeReportDeferredTest.php tests/Feature/Cooperative/PosPhase4ReportsTest.php
```

Hasil:

- `npm run build`: **pass**.
- Targeted PHPUnit: **13 tests pass, 148 assertions**.

Catatan: saya tidak menjalankan `vendor/bin/pint --dirty --format agent` karena tugas ini audit dokumen, bukan mengambil alih formatting perubahan PHP Deepseek. Karena PHP files ikut berubah, Deepseek wajib menjalankan Pint sebelum commit.

## Checklist Penutupan untuk Deepseek

- [x] Perbaiki semua referensi data POS report menjadi `analytics.*`.
- [x] Sinkronkan filter POS report dari prop/query `filters`.
- [x] Tambahkan test untuk filter POS report dengan query aktif.
- [x] Tambahkan test deferred analytics POS report.
- [x] Tambahkan test deferred summary cooperative report.
- [x] Isi migration index `cooperative_members`.
- [x] Revert/hapus cache operasional tanpa invalidation.
- [x] Dokumentasikan audit `prefetch` top-level action di halaman prioritas.
- [x] Dokumentasikan audit N+1 lintas modul dan kandidat cache master data beserta invalidation.
- [x] Harden toaster untuk flash `warning`/`status` dan aria-label tombol close.
- [x] Pindahkan dedupe toast ke `useToast()` agar berlaku global.
- [x] Tambahkan `role="alert"` pada inline error report.
- [x] Tambahkan `role="alert"` pada inline file error `CertificateForm` dan `McuForm`.
- [x] Buat empty state gap lama lebih spesifik, termasuk default `DataTable`.
- [x] Harden aksesibilitas modal `PayslipViewer` dengan dialog primitive.
- [x] Jalankan build dan targeted PHPUnit.
- [ ] Review dan kurangi generated Wayfinder diff jika tidak relevan.
- [ ] Jalankan `vendor/bin/pint --dirty --format agent` sebelum commit karena PHP files ikut berubah.

## Kesimpulan Akhir

Deepseek sudah menutup Plan 01 sampai 04 secara fungsional dan test targeted lulus. Sisa pekerjaan bukan implementasi fitur, melainkan hygiene sebelum commit: review generated Wayfinder diff dan jalankan Pint. Setelah dua hal itu selesai, checklist ini bisa ditutup penuh.

---

# Audit Lanjutan Deepseek - Improve Plan 05 sampai 06

Tanggal audit lanjutan: 2026-06-21  
Auditor: Codex  
Sumber audit: `git diff`, scan codebase, `docs/improve/05-role-cockpit-workflow.md`, `docs/improve/06-datatable-filtering-bulk-actions.md`, dan file implementasi terkait.

## Ringkasan Keputusan Plan 05-06

Update audit setelah eksekusi ulang Deepseek:

- Plan 05 sekarang bisa dianggap selesai secara fungsional. Test deferred analytics operator sudah ditambahkan.
- Plan 06 sudah jauh lebih dekat: sorting backend whitelist, endpoint bulk approve, Form Request, confirm dialog, Wayfinder generated route, dan test sort/bulk sudah ada.
- Namun Plan 06 **belum boleh ditutup penuh** karena ada gap otorisasi bulk approve: endpoint bulk memakai permission `manage_cooperative_payment`, sedangkan single approve masih membatasi role `Admin Koperasi`. Ini membuat bulk approve lebih longgar daripada approve satuan.

Status keseluruhan: **Plan 05 selesai; Plan 06 hampir selesai tetapi masih ada gap P0/P1 pada otorisasi bulk approve.**

## Checklist Plan 05 - Role-based Cockpit dan Workflow UX

| Item | Status terbaru | Bukti | Catatan |
| --- | --- | --- | --- |
| Cockpit role dipilih konservatif, tidak membuat cockpit baru yang duplikatif | Selesai | `resources/js/pages/Cooperative/Operator/Dashboard.vue` | Deepseek memilih polish cockpit Operator Koperasi yang memang sudah ada. Ini sesuai plan: cockpit tambahan hanya dibuat jika role punya pekerjaan harian berbeda. |
| Cockpit menjawab pekerjaan harian, bukan hanya statistik | Selesai | `quickActions`, approval inbox, exception list di `Operator/Dashboard.vue` | Ada shortcut pembayaran, pinjaman, tutup periode, anggota, POS product, laporan; plus inbox approval dan exception list. |
| Item cockpit actionable | Selesai | Link ke payments, loans, rewards, low stock, export | Item approval/exception punya CTA review/lihat/export. |
| Metric berat memakai deferred prop | Selesai | `OperatorProcedureController::dashboard()`, `Deferred data="analytics"` | Analytics operator tidak lagi dipanggil sebagai request JSON terpisah dari frontend. |
| Loading dan empty state tersedia | Selesai | `Skeleton`, `EmptyState` di `Operator/Dashboard.vue` | Inbox dan exception punya fallback dan empty state kontekstual. |
| Permission backend melindungi route cockpit | Selesai | `authorizePermission('view_cooperative_report')`, `RoleSmokeTest` | Route dashboard dan endpoint JSON operator terlindungi untuk role tidak berwenang. |
| Sidebar hanya relevan untuk role terkait | Selesai | `AppSidebar.vue` menu Operator Koperasi memakai permission `view_cooperative_report` / `manage_cooperative_settings` | Sidebar mengikuti permission gate yang sudah ada. |
| Test spesifik untuk perubahan deferred analytics cockpit | Selesai | `tests/Feature/Cooperative/OperatorDashboardDeferredTest.php` | Ada test render awal, deferred analytics, dan forbidden tanpa permission. |

### Arahan Penutupan Plan 05 untuk Deepseek

- [x] Pertahankan cockpit di Operator Koperasi; jangan membuat cockpit Finance/POS/HR hanya demi memenuhi plan jika pekerjaannya belum jelas.
- [x] Pertahankan quick actions yang mengarah ke pekerjaan harian, bukan kartu statistik tambahan.
- [x] Pertahankan `Inertia::defer()` untuk analytics operator.
- [x] Tambahkan feature test untuk operator dashboard yang memastikan initial page render tidak membawa analytics eager, lalu `loadDeferredProps('analytics')` mengembalikan key analytics yang dibutuhkan.
- [x] Tambahkan assertion sederhana bahwa user tanpa `view_cooperative_report` tetap tidak bisa membuka dashboard operator setelah perubahan deferred prop.

Verdict Plan 05: **Selesai secara fungsional.**

## Checklist Plan 06 - Advanced Datatable, URL Filter, dan Bulk Actions

| Item | Status terbaru | Bukti | Catatan |
| --- | --- | --- | --- |
| Shared table punya sticky header | Selesai | `resources/js/components/ui/data-table/DataTable.vue` | `thead` sudah `sticky top-0`, container punya max height dan horizontal scroll. |
| Shared table punya row selection dan row highlight | Selesai | `DataTable.vue` | Ada checkbox selectable, selected rows diberi background highlight. |
| Shared table punya sort event dan ikon sort | Selesai sebagian | `DataTable.vue` | UI mengirim event `sort`, tetapi kebenaran sort tetap bergantung pada backend halaman pemakai. |
| Bulk action toolbar tersedia | Selesai | `BulkActionBar.vue`, `Cooperative/Payments/Index.vue` | Toolbar muncul saat ada selection. |
| URL sort/filter shareable | Selesai | `Cooperative/Payments/Index.vue`, `CooperativePaymentController@index` | Frontend mengirim `sort_field` / `sort_direction`; controller mengembalikan filters aktif. |
| Server-side sorting whitelist | Selesai | `CooperativePaymentController::SORT_WHITELIST` | Whitelist tersedia untuk `paid_at`, `status`, `amount`, `id`. `member.name` sudah tidak ditandai sortable di UI. |
| Pagination mempertahankan filter/sort | Selesai | `paginate(20)->withQueryString()` + `filters` prop sort aktif | Query sort/filter ikut URL dan pagination links. |
| Backend menolak sort field tidak valid | Selesai | `PaymentSortBulkTest::test_payments_sort_rejects_invalid_field()` | Invalid field dinormalisasi ke `paid_at`. |
| Bulk action diproses backend sebagai operasi bulk tervalidasi | Selesai sebagian | `payments/bulk-approve`, `BulkApprovePaymentsRequest`, `bulkApprove()` | Endpoint dan validation ada, tetapi otorisasinya belum selaras dengan single approve. |
| Bulk action menolak item dengan status tidak valid | Selesai sebagian | `bulkApprove()` skip non-`PENDING`, test `test_bulk_approve_skips_non_pending_payments()` | Secara behavior tidak memproses non-`PENDING`; pesan masih masuk flash `success`, jadi bisa diperbaiki menjadi warning/partial success. |
| Konfirmasi bulk jelas sebelum operasi | Selesai | `ConfirmDialog` di `Cooperative/Payments/Index.vue` | Dialog menampilkan jumlah payment dan menjelaskan hanya `PENDING` diproses. |
| Test filter/sort/bulk action | Selesai sebagian | `tests/Feature/Cooperative/PaymentSortBulkTest.php` | Test ada, tetapi perlu ditambah kasus role `System Admin`/user permission-only tidak boleh bulk approve jika single approve juga tidak boleh. |
| Otorisasi bulk approve sama ketat dengan single approve | Gap P0 | `BulkApprovePaymentsRequest::authorize()` vs `CooperativePaymentController::approve()` | Single approve memakai `abort_unless($user->hasRole('Admin Koperasi'), 403)` + policy. Bulk approve hanya cek permission `manage_cooperative_payment`, sehingga user permission-only bisa bulk approve walau single approve dilarang. |
| Router visit filter/sort memakai `preserveScroll` | Gap P2 | `handleSort()` di `Cooperative/Payments/Index.vue` | Saat ini hanya `preserveState: true`; plan 06 meminta `preserveState` dan `preserveScroll`. |
| Frontend bulk endpoint memakai Wayfinder | Gap P2 | `router.post("/cooperative/payments/bulk-approve", ...)` | Route generated sudah ada. Gunakan import `bulkApprove` dari `@/routes/cooperative/payments` agar tidak hardcode URL. |

### Arahan Penutupan Plan 06 untuk Deepseek

- [x] Di `CooperativePaymentController@index`, tambahkan parsing query `sort_field` dan `sort_direction` dengan whitelist.
- [x] Pastikan `filters` prop mengembalikan `status`, `sort_field`, dan `sort_direction`, agar refresh URL dan state table konsisten.
- [x] Jangan tampilkan kolom sebagai sortable jika backend belum mendukung field tersebut. `member.name` sudah tidak sortable.
- [x] Buat endpoint bulk yang eksplisit, misalnya `POST /cooperative/payments/bulk-approve`, dengan Form Request berisi `ids: array`, `ids.*: exists`.
- [ ] Samakan authorization bulk approve dengan single approve. Minimal tambahkan guard role `Admin Koperasi` di `BulkApprovePaymentsRequest::authorize()` atau di controller, lalu tambahkan test permission-only/System Admin tidak bisa bulk approve.
- [ ] Di endpoint bulk, lock rows dalam transaksi atau dokumentasikan kenapa transaksi per item dari `CooperativePaymentService::approve()` cukup. Saat ini service mengunci per item, tetapi query awal bulk belum `lockForUpdate`.
- [x] Tambahkan konfirmasi bulk sebelum submit. Minimal tampilkan jumlah item dan action, misalnya `3 pembayaran akan disetujui`.
- [ ] Tambahkan test yang memastikan urutan data benar, bukan hanya `filters.sort_field` dan `filters.sort_direction` benar.
- [ ] Tambahkan `preserveScroll: true` pada `handleSort()`.
- [ ] Ganti hardcoded URL bulk approve dengan Wayfinder route helper.
- [ ] Tambahkan smoke manual setelah backend selesai: filter status, sort, pagination, refresh URL, bulk approve sukses, bulk approve gagal/partial dengan toast/flash jelas.

Verdict Plan 06: **Hampir selesai, tetapi belum boleh ditutup sampai otorisasi bulk approve disamakan dengan single approve.**

## Catatan Hygiene untuk Deepseek

- Generated Wayfinder diff masih sangat besar di `resources/js/actions/**` dan `resources/js/routes/**`. Commit hanya jika perubahan route/controller memang membutuhkan regenerasi.
- Karena PHP berubah, jalankan `vendor/bin/pint --dirty --format agent` sebelum commit.
- Setelah menambah route bulk baru, regenerate Wayfinder dengan sengaja dan pastikan hanya generated file yang relevan ikut commit.

## Checklist Penutupan Plan 05-06

- [x] Cockpit operator dipoles dengan quick actions.
- [x] Cockpit operator punya approval inbox dan exception list.
- [x] Cockpit operator punya skeleton dan empty state.
- [x] Analytics operator memakai deferred prop.
- [x] Shared `DataTable` punya sticky header, row selection, row highlight, dan sort event.
- [x] Bulk toolbar dasar tersedia.
- [x] Test deferred analytics operator dashboard.
- [x] Backend payments sorting dengan whitelist.
- [x] `filters` prop payments memuat sort query aktif.
- [x] Hapus/disable sortable UI untuk field yang belum didukung backend.
- [ ] Endpoint bulk approve payments dengan Form Request dan policy/authorization yang sama ketat dengan single approve.
- [x] Bulk approve menolak atau melaporkan item non-`PENDING`.
- [x] Konfirmasi bulk action sebelum submit.
- [x] Test filter/sort/bulk plan 06 tersedia.
- [ ] Tambahkan test permission-only/System Admin tidak bisa bulk approve.
- [ ] Tambahkan assertion urutan sort data aktual.
- [ ] Tambahkan `preserveScroll` pada sort visit dan gunakan Wayfinder untuk URL bulk approve.
- [ ] Pint dan review generated Wayfinder sebelum commit.

---

# Audit Lanjutan Minimax - Plan 06 sampai 08

Tanggal audit lanjutan: 2026-06-22  
Auditor: Codex  
Sumber audit: `git diff`, scan codebase, `docs/improve/06-datatable-filtering-bulk-actions.md`, `docs/improve/07-background-jobs-export-progress.md`, `docs/improve/08-theming-dark-mode.md`, targeted PHPUnit, dan build.

## Ringkasan Keputusan Plan 06-08

Plan 06 sekarang bisa dianggap **selesai secara fungsional**. Gap otorisasi bulk approve yang sebelumnya P0 sudah ditutup dengan guard role `Admin Koperasi`; frontend sudah memakai Wayfinder untuk bulk route; sort visit sudah memakai `preserveScroll`; dan test urutan sort aktual sudah ditambahkan.

Plan 07 sekarang **selesai secara fungsional**. Fondasi backend benar: tabel `background_jobs`, model status, job `ShouldQueue`, retry/backoff, endpoint enqueue/status/download, private storage via disk `local`, owner guard, dan test coverage untuk enqueue, status, download, failure, serta idempotency. Gap UI `pending` yang sebelumnya ditemukan sudah ditutup dengan `pdfJob.hasJobStarted.value`.

Plan 08 sekarang **selesai untuk scope P3**. Audit doc sudah dibuat, manual smoke light/dark + mobile/desktop sudah dicatat sebagai hasil aktual, dan perubahan visual yang masuk akal sudah dilakukan (`Card.vue`, `JobProgressTracker.vue`, tabel `top_products`).

Status keseluruhan: **Plan 06 selesai, Plan 07 selesai secara fungsional, Plan 08 selesai untuk scope P3.**

## Checklist Final Plan 06 - Datatable Filtering dan Bulk Actions

| Item | Status terbaru | Bukti | Catatan |
| --- | --- | --- | --- |
| Sorting backend memakai whitelist | Selesai | `CooperativePaymentController::SORT_WHITELIST` | `paid_at`, `status`, `amount`, `id` aman dari raw order bebas. |
| Invalid sort field/direction dinormalisasi | Selesai | `PaymentSortBulkTest` | Invalid field kembali ke `paid_at`, invalid direction kembali ke `desc`. |
| Sort URL dan pagination stabil | Selesai | `filters` prop + `withQueryString()` + `handleSort(... preserveScroll)` | Query sort/filter bertahan di URL dan pagination. |
| Sort order aktual dites | Selesai | `test_payments_sort_order_by_amount_asc()` | Test memvalidasi urutan `payments.data`, bukan hanya prop filter. |
| Bulk endpoint eksplisit | Selesai | `POST /cooperative/payments/bulk-approve` | Endpoint tidak lagi menjalankan banyak single request dari frontend. |
| Bulk action memakai Wayfinder | Selesai | `bulkApprove().url` di `Cooperative/Payments/Index.vue` | Hardcoded URL lama sudah diganti. |
| Bulk authorization sama ketat dengan single approve | Selesai | `abort_unless($request->user()?->hasRole('Admin Koperasi'), 403)` | Permission-only user ditolak. |
| Bulk status validation | Selesai cukup | `bulkApprove()` skip non-`PENDING` | Behavior aman. Masih bisa ditingkatkan dengan flash `warning` untuk partial skip. |
| Confirm dialog sebelum bulk action | Selesai | `ConfirmDialog` di payments page | Dialog menampilkan jumlah pembayaran dan aturan status `PENDING`. |
| Test filter/sort/bulk | Selesai | `PaymentSortBulkTest.php` | Coverage sudah mencakup permission, role, validation, success, skip non-pending, dan sort order. |

### Arahan Lanjutan Plan 06

- [x] Tutup Plan 06 secara fungsional.
- [ ] Enhancement kecil: ubah pesan bulk partial skip menjadi `warning` atau flash terpisah, agar user tidak membaca operasi campuran sebagai full success.
- [ ] Enhancement kecil: jika ingin atomic batch, bungkus query bulk dalam transaksi dan lock row di level controller. Saat ini tiap item sudah aman melalui `CooperativePaymentService::approve()` yang melakukan lock per payment.

Verdict Plan 06: **Selesai.**

## Checklist Plan 07 - Background Jobs, Export, dan Progress Tracker

| Item | Status terbaru | Bukti | Catatan |
| --- | --- | --- | --- |
| Operasi berat dipilih sebagai pilot | Selesai | POS report PDF | Sinkron PDF export diganti flow enqueue job. |
| Endpoint enqueue cepat dan return job id | Selesai | `PosReportController::enqueuePdf()` | Return `202` dengan `job_id`, `status`, `progress`. |
| Job memakai queue | Selesai | `GeneratePosReportPdf implements ShouldQueue` | Ada `tries`, `timeout`, `backoff()`, dan `WithoutOverlapping`. |
| Status job disimpan | Selesai | `background_jobs` migration, `BackgroundJob` model | Kolom minimum plan tersedia: user, type, status, progress, file path, error, metadata, timestamps. |
| Output disimpan private | Selesai | `Storage::disk($job->disk)` default `local` | Download lewat controller, bukan public URL. |
| Endpoint status tersedia | Selesai | `pdfStatus()` | Return pending/processing/completed/failed metadata dan `download_url` saat completed. |
| Download file hanya owner | Selesai | `abort_unless($job->isOwnedBy(...), 404)` | Test owner/intruder ada. |
| Failure menyimpan pesan aman | Selesai | `markFailed(mb_substr(..., 0, 1000))` | Test failure ada. |
| Job idempotent/retry safe | Selesai | `claimJob()` skip completed + `WithoutOverlapping` | Test run dua kali tidak reprocess file. |
| UI progress tracker tersedia | Selesai | `JobProgressTracker.vue`, `useBackgroundJob.ts` | Polling, retry, download, dan reset sudah tersedia. |
| User bisa melihat status pending | Selesai | `Reports/Index.vue` memakai `v-if="pdfJob.hasJobStarted.value"` | Gap lama sudah ditutup. Setelah enqueue selesai, `state.jobId` tetap membuat tracker terlihat walau status masih `pending`. |
| Notification bell saat file siap | Tidak diimplementasi, masih acceptable | Plan memperbolehkan progress tracker atau notification item | Karena tracker ada, notification bell tidak wajib untuk pilot ini. |
| Queue failure terlihat di monitoring existing | Selesai untuk pilot | `failed()` + status `failed` + endpoint status | Monitoring operasional queue tetap perlu worker/log dashboard, tetapi acceptance user-facing sudah ditutup oleh tracker dan status endpoint. |

### Arahan Penutupan Plan 07 untuk Minimax

- [x] Pertahankan model `BackgroundJob` dan enum status.
- [x] Pertahankan endpoint owner-guarded untuk status dan download.
- [x] Pertahankan job idempotent dengan skip jika status sudah `completed`.
- [x] Render condition tracker sudah benar memakai `pdfJob.hasJobStarted.value`.
- [x] Route enqueue/status di composable sudah memakai Wayfinder controller helper.
- [x] Catatan smoke manual pending/processing sudah masuk di `docs/improve/theming-dark-mode-audit.md`.
- [x] Status completed/download sudah tercakup oleh `Plan07BackgroundJobExportTest`.
- [x] Requirement queue worker sudah terdokumentasi di `docs/decisions.md`; untuk environment local `.env.example` masih `QUEUE_CONNECTION=sync`, jadi flow tetap bisa berjalan tanpa worker saat development.

Verdict Plan 07: **Selesai.** Backend, route, private download, UI tracker, retry/idempotency, dan verifikasi minimal sudah sesuai plan.

## Checklist Plan 08 - Theming, Dark Mode, dan Aestetika Premium

| Item | Status terbaru | Bukti | Catatan |
| --- | --- | --- | --- |
| Audit warna hardcoded dan inkonsistensi | Selesai | `docs/improve/theming-dark-mode-audit.md` | Audit doc memakai tanggal 21-22 Juni 2026 dan memuat keputusan per komponen/halaman. |
| Komponen shared prioritas dirapikan | Selesai untuk P3 | `Card.vue`, audit `Button`, `DataTable`, `Input`, `Textarea`, `Select`, `EmptyState`, `PageContainer` | Perubahan kode kecil tetapi tepat; audit menunjukkan mayoritas shared component sudah sesuai token existing. |
| Dark mode komponen shared konsisten | Selesai | Audit doc + manual smoke dark mode | Dashboard, Payments, POS Reports, Payroll, dan Reports dicatat OK di dark mode. |
| Hover/focus state readable light/dark | Selesai | Audit doc bagian fokus, disabled, selected row | Button/link/input/select/checkbox punya `focus-visible`; disabled dan selected row sudah dicek. |
| Table density dan sticky header dicek | Selesai cukup | `DataTable.vue`, `Reports/Index.vue` top products | POS Reports sudah ditambah `py-2.5`, `tabular-nums`, dan hover state. |
| Tidak ada dekorasi berlebihan / gradient orb | Selesai dengan keputusan sadar | Audit doc bagian "Yang Tidak Diubah" + smoke manual | Hero gradient/orbs dipertahankan karena tidak overlap, tetap readable, dan elemen dekoratif `aria-hidden`. |
| Screenshot/manual smoke light/dark | Selesai | `Manual Smoke Notes - Hasil Verifikasi` dan `Verifikasi Manual Lanjutan - Hasil` | Light/dark, mobile 375px, focus, disabled, skeleton, sticky header, dan selected row sudah dicatat. |

### Arahan Penutupan Plan 08 untuk Minimax

- [x] Audit doc `docs/improve/theming-dark-mode-audit.md` sudah dibuat.
- [x] Gap visual langsung yang masuk akal sudah ditutup: `Card.vue` solid surface, `JobProgressTracker.vue` konsisten dengan POS Reports, dan tabel `top_products` punya density/hover/tabular numbers.
- [x] `Manual Smoke Notes` sudah diubah menjadi hasil aktual per halaman dan mode.
- [x] Bagian `Verifikasi Manual Lanjutan` sudah selesai dan tidak lagi menulis "belum selesai".
- [x] Metadata audit sudah dikoreksi ke 21-22 Juni 2026.
- [x] Keputusan mempertahankan hero gradient/orbs sudah disertai hasil smoke readability dan `aria-hidden`.
- [x] Native `<select>` sudah ditulis sebagai P3 follow-up, bukan blocker Plan 08.
- [ ] Enhancement dokumentasi kecil: path komponen di audit doc masih ringkas, misalnya `status-badge/StatusBadge.vue`. Itu masih bisa dipahami dari heading `resources/js/components/ui/**`, tetapi lebih baik ditulis penuh sebagai `ui/status-badge/StatusBadge.vue` saat cleanup berikutnya.

Verdict Plan 08: **Selesai untuk scope P3.** Tidak ada gap blocking terhadap acceptance criteria Plan 08.

## Verifikasi Audit Lanjutan

Perintah yang dijalankan saat audit ini:

```bash
npm run build
php artisan test --compact tests/Feature/Cooperative/Plan07BackgroundJobExportTest.php tests/Feature/Cooperative/PosSprint6ReportFiltersTest.php
```

Hasil terbaru: `npm run build` lulus; PHPUnit terkait Plan 07 dan POS report lulus **21 tests / 63 assertions**.

Catatan: karena ada PHP files berubah di diff junior, Minimax/Deepseek tetap wajib memastikan `vendor/bin/pint --dirty --format agent` sudah dijalankan sebelum commit final.
