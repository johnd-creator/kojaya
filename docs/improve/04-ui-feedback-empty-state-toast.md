# Plan 04 - UI Feedback, Empty State, Toast, dan Slide-over

## Tujuan

Membuat interaksi harian lebih jelas dengan feedback visual konsisten: skeleton, empty state, toast, modal, dan slide-over untuk aksi sederhana.

## Prioritas

P0. Banyak perbaikan bisa dilakukan bertahap per halaman dengan risiko rendah.

## Sumber Dari new_improve.md

- UI/UX: Skeleton Loaders
- UI/UX: Toast Notifications & Slide-overs
- UI/UX: Transisi & Hover Effects
- Workflow UX: Empty States
- Phase 1: Visual Feedback
- Phase 1: Eradikasi Alert Tradisional

## Baseline Repo

- `resources/js/components/EmptyState.vue` sudah tersedia.
- `resources/js/components/ConfirmDialog.vue` sudah tersedia.
- `DataTable.vue` sudah memakai `EmptyState`.
- Beberapa halaman operator koperasi sudah memiliki toast lokal.

## Scope

Termasuk:

- Standardisasi empty state pada tabel/list penting.
- Ganti alert/confirm/prompt browser yang tersisa dengan komponen UI.
- Buat atau adopsi pola toast global jika belum ada.
- Gunakan modal/slide-over untuk create/edit ringan yang tidak perlu pindah halaman penuh.
- Tambahkan hover/focus feedback yang konsisten tanpa membuat UI terlalu ramai.

Tidak termasuk:

- Redesign full theme/dark mode. Itu masuk `08-theming-dark-mode.md`.
- Refactor semua form kompleks ke slide-over sekaligus.

## Audit Awal

Gunakan pencarian berikut:

```bash
rg -n "alert\\(|confirm\\(|prompt\\(|Loading data|No Data|No records|spinner|toast" resources/js
```

Kelompokkan hasil menjadi:

- Wajib ganti: raw browser dialog.
- Perlu konsistensi: empty/loading text.
- Biarkan dulu: toast lokal yang sudah baik tetapi bisa distandardisasi nanti.

## Langkah Implementasi

1. Tentukan komponen toast yang akan jadi standar. Jika belum ada global toaster, buat satu komponen kecil di layout utama.
2. Ubah satu modul prioritas sebagai pilot, misalnya Koperasi atau Payroll.
3. Pastikan success/error dari Inertia form memakai toast yang sama.
4. Pastikan empty state punya:
   - title singkat.
   - deskripsi berguna.
   - CTA jika ada aksi natural.
5. Pastikan modal/slide-over punya focus state dan close behavior yang jelas.
6. Baru lanjutkan adopsi ke modul lain dalam batch kecil.

## Acceptance Criteria

- Tidak ada raw `alert()`, `confirm()`, atau `prompt()` di halaman yang disentuh.
- Empty state tidak hanya teks generik.
- Toast muncul untuk submit sukses/gagal yang relevan.
- Loading state tidak mengubah ukuran layout secara kasar.
- Keyboard focus dan aria label tidak rusak untuk modal/dialog.

## Verifikasi Minimal

- `npm run build`
- Manual smoke pada create/update/delete halaman yang disentuh.
- Test feature existing untuk flow submit jika ada.

## Risiko

- Toast global bisa dobel tampil jika flash message lama masih dirender.
- Slide-over untuk form kompleks bisa memperburuk UX jika validasi/error panjang. Mulai dari form sederhana.
- Jangan menghilangkan redirect yang dibutuhkan untuk refresh data besar.

