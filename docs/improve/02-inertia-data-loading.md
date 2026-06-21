# Plan 02 - Inertia Data Loading dan Deferred Props

## Tujuan

Memindahkan data berat ke Inertia Deferred Props agar halaman bisa render shell lebih cepat, sementara metrik/report besar dimuat setelahnya.

## Prioritas

P1. Kerjakan setelah quick wins karena butuh perubahan controller, props, dan test.

## Sumber Dari new_improve.md

- Frontend: Inertia Deferred Props
- Phase 2: Deferred Props
- Phase 2: Data Loading

## Baseline Repo

Deferred props sudah ditemukan di:

- `resources/js/pages/Dashboard.vue`
- `resources/js/pages/Reports.vue`
- `resources/js/pages/Payroll/Index.vue`
- `resources/js/pages/Cooperative/Dues/Index.vue`
- `resources/js/pages/Cooperative/Members/Index.vue`

Test arsitektur terkait deferred props sudah ada di `tests/Feature/P1ArchitectureTest.php`.

## Scope

Termasuk:

- Audit halaman yang initial payload-nya besar.
- Pindahkan query agregasi atau chart ke `Inertia::defer()`.
- Tambahkan fallback skeleton di Vue dengan `<Deferred>`.
- Tambahkan test yang memastikan deferred prop bisa diload.

Tidak termasuk:

- Optimasi query SQL yang membutuhkan index baru. Itu masuk `03-backend-query-cache-indexing.md`.
- Redesign visual besar pada dashboard. Itu masuk `05-role-cockpit-workflow.md`.

## Kandidat Halaman

Prioritaskan halaman dengan metrik agregasi, chart, atau tabel besar:

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/PayrollController.php`
- `app/Http/Controllers/CooperativeMemberController.php`
- Controller laporan koperasi, POS, SHU, ledger, dan payment jika payload-nya berat.

## Langkah Implementasi

1. Ukur atau inspeksi props controller yang menggabungkan data ringan dan berat.
2. Pisahkan props menjadi:
   - props ringan: judul, filter, pilihan dropdown kecil, permission, breadcrumbs.
   - props berat: KPI agregat, chart, summary, table statistik, trend.
3. Gunakan `Inertia::defer()` untuk props berat.
4. Di Vue, bungkus area data berat dengan `<Deferred data="namaProp">`.
5. Buat fallback skeleton yang ukurannya mendekati konten akhir.
6. Pastikan filter URL tetap bekerja dengan `preserveState` dan reload hanya props yang dibutuhkan.
7. Tambahkan atau update test `loadDeferredProps()` untuk halaman yang berubah.

## Acceptance Criteria

- Page shell bisa render tanpa menunggu semua query agregasi selesai.
- Deferred props punya fallback yang jelas dan tidak menyebabkan layout shift besar.
- Filter tetap sinkron dengan URL.
- Test membuktikan initial page dan deferred load sama-sama valid.
- Tidak ada query lazy loading baru akibat pemisahan props.

## Verifikasi Minimal

- `php artisan test --compact tests/Feature/P1ArchitectureTest.php`
- Test spesifik controller/page yang disentuh.
- `npm run build`

## Risiko

- Prop names yang berubah bisa memutus Vue component.
- Deferred data yang saling bergantung perlu dipisah hati-hati agar fallback tidak membaca `undefined`.
- Query yang tetap berat di deferred masih bisa membebani database; pindahkan optimasi query ke plan 03.

