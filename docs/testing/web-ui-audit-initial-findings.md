# Initial Findings — Kojaya Web UI Audit Pilot

Dokumen ini menampung temuan UI yang ditemukan saat pilot. Temuan di sini tidak diperbaiki dalam foundation PR kecuali bug fatal yang menghalangi capture atau test harness.

## P1

- Belum ada temuan blocker dari pilot desktop. CI tetap harus menjadi verifikasi akhir.

## P2

- Axe melaporkan `color-contrast` ber-impact serious pada Dashboard, Profil, Saldo Toko index, dialog buka akun, dan detail akun.
- Axe melaporkan kontrol form tanpa accessible name (`select-name`/`label`) pada POS, Saldo Toko index, dialog buka akun, dan detail akun.
- Temuan ini sengaja tidak diperbaiki pada foundation PR; buat task UX/accessibility terpisah setelah artifact direview.

## P3

- Screenshot dan runtime pilot desktop belum menjadi audit UX final; review visual per-screen masih diperlukan.

## P4

- Belum dinilai sampai audit UX per-screen selesai.
