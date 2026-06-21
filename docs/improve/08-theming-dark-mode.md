# Plan 08 - Theming, Dark Mode, dan Aestetika Premium

## Tujuan

Merapikan konsistensi visual aplikasi agar nyaman dipakai harian: tipografi, warna, dark mode, hover/focus state, dan densitas UI admin yang profesional.

## Prioritas

P3. Kerjakan setelah perbaikan performa/UX utama, kecuali ada halaman spesifik yang sedang disentuh.

## Sumber Dari new_improve.md

- UI/UX: Transisi & Hover Effects
- Workflow UX: Aestetika Premium
- Phase 3: Dark Mode & Theming

## Scope

Termasuk:

- Audit warna Tailwind yang tidak konsisten.
- Rapikan dark mode pada komponen shared.
- Pastikan hover/focus state bisa dibaca di light dan dark mode.
- Hindari warna primary terlalu terang atau dominasi satu hue berlebihan.
- Rapikan typography scale pada card, table, form, dan dashboard.

Tidak termasuk:

- Redesign brand total.
- Mengganti UI library atau dependency.
- Membuat landing page marketing.

## Area Prioritas

- `resources/js/components/ui/**`
- `resources/js/components/dashboard/**`
- `resources/js/components/EmptyState.vue`
- `resources/js/components/PageContainer.vue`
- `resources/js/layouts/**`
- Halaman Dashboard, Cooperative, POS, Payroll, Reports.

## Langkah Implementasi

1. Audit class warna hardcoded yang berulang dan tidak konsisten.
2. Prioritaskan komponen shared agar perubahan menyebar luas.
3. Pastikan setiap komponen punya varian light/dark yang seimbang.
4. Cek table density: padding, font size, line height, dan sticky header bila ada.
5. Rapikan button/icon state: hover, active, disabled, focus-visible.
6. Hindari card bersarang dan section yang terlalu dekoratif.
7. Screenshot manual halaman prioritas di light dan dark mode.

## Acceptance Criteria

- Komponen shared terlihat konsisten di light dan dark mode.
- Text contrast tetap terbaca.
- Tidak ada layout overlap pada mobile dan desktop.
- Card radius dan spacing mengikuti pola aplikasi.
- Tidak ada decorative gradient/orb yang tidak membantu pekerjaan user.

## Verifikasi Minimal

- `npm run build`
- Manual smoke light/dark mode.
- Cek halaman prioritas di viewport mobile dan desktop.

## Risiko

- Perubahan warna global bisa merusak status semantic seperti danger/success/warning.
- Dark mode parsial membuat halaman terasa tidak selesai.
- Terlalu banyak dekorasi mengurangi densitas informasi ERP.

