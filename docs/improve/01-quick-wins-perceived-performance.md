# Plan 01 - Quick Wins Perceived Performance

## Tujuan

Membuat aplikasi terasa lebih cepat dalam pemakaian harian tanpa refactor besar. Fokusnya adalah prefetch navigasi, loading state yang rapi, dan audit ringan terhadap halaman yang paling sering dibuka.

## Prioritas

P0. Kerjakan pertama karena dampaknya langsung terasa dan risikonya rendah.

## Sumber Dari new_improve.md

- Phase 1: Prefetching Menu
- Phase 1: Visual Feedback
- Phase 1: Eradikasi Alert Tradisional
- Phase 1: Audit Query Sederhana
- Frontend: Inertia Prefetching
- UI/UX: Skeleton Loaders

## Scope

Termasuk:

- Tambah atau audit `prefetch` pada link navigasi utama.
- Tambah skeleton loader pada area deferred atau loading yang saat ini masih spinner/kosong.
- Audit flash/alert statis yang mengganggu flow, lalu tandai kandidat migrasi toast.
- Audit halaman prioritas yang dipakai harian: Dashboard, Cooperative, POS, Payroll, Reports.

Tidak termasuk:

- Refactor query berat menjadi deferred props penuh. Itu masuk `02-inertia-data-loading.md`.
- Redesign dashboard role secara besar. Itu masuk `05-role-cockpit-workflow.md`.
- Queue/export background. Itu masuk `07-background-jobs-export-progress.md`.

## File dan Area yang Perlu Dicek

- `resources/js/components/AppSidebar.vue`
- `resources/js/layouts/AppLayout.vue`
- `resources/js/pages/Dashboard.vue`
- `resources/js/pages/Cooperative/**`
- `resources/js/pages/Payroll/Index.vue`
- `resources/js/pages/Reports.vue`
- `resources/js/components/dashboard/**`
- `resources/js/components/ui/skeleton/**`
- `tests/Feature/DashboardTest.php`
- `tests/Feature/P1ArchitectureTest.php`

## Langkah Implementasi

1. Inventaris link navigasi utama di sidebar dan top-level action.
2. Pastikan link Inertia utama memakai `prefetch` jika tujuan halaman aman diprefetch dan tidak melakukan side effect.
3. Cek halaman yang sudah deferred, lalu pastikan fallback-nya skeleton berbentuk konten, bukan spinner generik.
4. Cek tabel/list utama yang empty/loading state-nya belum konsisten.
5. Ganti loading text seperti `Loading data...` yang muncul di permukaan user dengan skeleton atau progress state yang konsisten.
6. Catat halaman yang butuh migrasi lebih besar ke plan lanjutan, jangan campur scope.

## Acceptance Criteria

- Sidebar dan link aksi utama yang read-only memakai prefetch.
- Tidak ada halaman prioritas yang menampilkan area kosong panjang saat data deferred/loading.
- Loading state punya dimensi stabil sehingga layout tidak meloncat saat data masuk.
- Empty state memakai `EmptyState.vue` atau pola sepadan.
- Tidak ada perubahan behavior data, hanya perceived performance dan feedback UI.

## Verifikasi Minimal

- `npm run build`
- Test terkait halaman yang disentuh, contoh: `php artisan test --compact tests/Feature/DashboardTest.php`
- Manual smoke: buka dashboard, pindah menu Cooperative/POS/Payroll/Reports, pastikan tidak ada console error.

## Risiko

- Prefetch pada link yang memicu request mahal bisa menambah beban server. Batasi pada GET navigation dan halaman utama.
- Skeleton yang tidak punya tinggi stabil bisa menyebabkan layout shift.
- Jangan pakai prefetch pada tombol POST/PUT/DELETE atau action yang memiliki side effect.

