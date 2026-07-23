# Initial Findings — Kojaya Web UI Audit Pilot

Dokumen ini menampung temuan UI yang ditemukan saat pilot. Temuan di sini tidak diperbaiki dalam foundation PR kecuali bug fatal yang menghalangi capture atau test harness.

## P1

- Tidak ada blocker baru setelah full desktop inventory capture. Endpoint
  candidates dipindahkan ke exclusion karena JSON; crash detail anggota
  akibat prop `invoices` yang tidak ada ditangani sebagai empty list.

## P2

- Axe melaporkan 210 node critical/serious pada 42 dari 61 default desktop
  pages. Fingerprint exact tersimpan pada `tests/visual/accessibility-known-findings.json`;
  210 entries memiliki tracking ID `UI-A11Y-001` sampai `UI-A11Y-210` dan
  expiry 2026-09-30.
- Rule yang muncul mencakup `color-contrast`, `button-name`, `label`,
  `select-name`, `link-name`, dan `scrollable-region-focusable`.
- Temuan ini sengaja tidak diperbaiki pada foundation PR; buat task UX/accessibility terpisah setelah artifact direview.

## P3

- Full registry mencakup 61 renderable routes, 13 approved non-visual
  exclusions, 72 desktop scenarios, 59 tablet scenarios, dan 41 mobile
  scenarios. Review UX per-screen tetap dilakukan
  dari artifact; framework ini tidak melakukan redesign.
- Backend contract untuk invoice pada detail anggota masih layak ditinjau pada
  task UX terpisah setelah artifact tersedia.

## P4

- Copywriting, hierarchy, dan preference-level findings menunggu audit ChatGPT.
