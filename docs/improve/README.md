# Improve Plan Index

Sumber utama: `docs/new_improve.md`

Direktori ini memecah rencana peningkatan aplikasi menjadi beberapa paket kerja yang bisa dipilih oleh Codex agent secara terpisah. Setiap paket dibuat agar agent bisa langsung memahami konteks, prioritas, area file, acceptance criteria, dan verifikasi minimal.

## Cara Memilih Pekerjaan

1. Mulai dari prioritas P0/P1 yang berdampak langsung ke rasa cepat aplikasi.
2. Pilih satu file plan saja untuk satu sesi kerja agar scope tetap terkendali.
3. Sebelum coding, tetap ikuti `AGENTS.md`: baca `docs/project.md`, `docs/architecture.md`, dan dokumen task-specific.
4. Cek implementasi aktual lebih dulu karena sebagian item dari `new_improve.md` sudah mulai dikerjakan di repo.
5. Setelah selesai, update `docs/log.md` jika perubahan bersifat signifikan atau mengubah pola arsitektur.

## Urutan Prioritas Disarankan

| Prioritas | Plan | Dampak | Risiko |
| --- | --- | --- | --- |
| P0 | `01-quick-wins-perceived-performance.md` | Navigasi terasa lebih cepat, loading lebih rapi | Rendah |
| P0 | `04-ui-feedback-empty-state-toast.md` | UX harian membaik tanpa perubahan data besar | Rendah-Sedang |
| P1 | `02-inertia-data-loading.md` | Initial load dashboard/report lebih cepat | Sedang |
| P1 | `03-backend-query-cache-indexing.md` | Query berat dan master data lebih stabil | Sedang-Tinggi |
| P1 | `06-datatable-filtering-bulk-actions.md` | Tabel operasional lebih efisien untuk data besar | Sedang |
| P2 | `05-role-cockpit-workflow.md` | Workflow role lebih fokus dan sedikit klik | Sedang |
| P2 | `07-background-jobs-export-progress.md` | Operasi berat tidak mengunci request user | Tinggi |
| P3 | `08-theming-dark-mode.md` | Konsistensi visual dan kenyamanan penggunaan | Sedang |

## Baseline Saat Ini

Catatan baseline dari repo saat dokumen ini dibuat:

- `resources/js/pages/Dashboard.vue`, `resources/js/pages/Reports.vue`, `resources/js/pages/Payroll/Index.vue`, dan beberapa halaman koperasi sudah memakai Inertia `Deferred`.
- `resources/js/components/EmptyState.vue` dan `resources/js/components/ui/data-table/DataTable.vue` sudah ada.
- `resources/js/components/FilterBar.vue`, `StatsCard.vue`, dan `ConfirmDialog.vue` sudah mulai diadopsi di beberapa halaman.
- Cockpit Operator Koperasi sudah ada di `resources/js/pages/Cooperative/Operator/Dashboard.vue`.
- Queue, notification outbox, health check, dan monitoring dasar sudah tersedia, tetapi export/report berat masih perlu diaudit per modul.

## Definisi Selesai Global

Sebuah paket kerja dianggap selesai jika:

- Implementasi mengikuti pola Laravel 12, Inertia v2, Vue 3, Tailwind v4, dan Wayfinder yang sudah dipakai repo.
- Ada test atau update test yang membuktikan behavior utama.
- Untuk perubahan PHP, `vendor/bin/pint --dirty --format agent` sudah dijalankan.
- Untuk perubahan frontend, minimal `npm run build` atau command validasi frontend yang tersedia dijalankan bila perubahan menyentuh TypeScript/Vue.
- Dokumentasi terkait diperbarui bila ada keputusan arsitektur baru.

