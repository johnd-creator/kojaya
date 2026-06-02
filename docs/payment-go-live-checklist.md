# Payment Gateway Go-Live Checklist

Tanggal: 17 Mei 2026

Checklist ini adalah P0 operasional sebelum payment gateway dan WhatsApp notification dipakai di production. Kode sudah memiliki Midtrans provider, webhook verification, idempotency, dan internal fallback; item di bawah memvalidasi dependency eksternal yang tidak bisa dibuktikan hanya dari test lokal.

## Midtrans

- Pasang production `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, dan `MIDTRANS_IS_PRODUCTION=true` di secret manager production.
- Jalankan live transaction kecil untuk QRIS, VA, dan e-wallet dari aplikasi member, lalu pastikan payment berubah dari `PENDING` ke `PAID`.
- Validasi webhook production mengirim `signature_key`, diterima endpoint `/api/payments/webhook`, dan duplicate webhook tidak membuat rekonsiliasi ganda.
- Cocokkan settlement reference di dashboard Midtrans dengan `gateway_reference` dan `reconciliation_reference`.
- Siapkan rollback: set credential production kosong atau `MIDTRANS_IS_PRODUCTION=false` untuk kembali ke internal pending payment flow saat incident.

## WhatsApp Business API

- Pasang production `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_ENDPOINT`, dan `WHATSAPP_DEFAULT_COUNTRY_CODE`.
- Pastikan WhatsApp template untuk pengingat iuran, status leave, dan pembayaran sudah approved oleh Meta.
- Kirim pesan uji ke nomor opt-in internal dan pastikan outbox mencatat status sukses atau failure yang bisa di-retry.

## FCM

- Pasang production `FCM_SERVER_KEY` dan endpoint yang sesuai project Firebase.
- Register token Android nyata, kirim notification uji, dan pastikan invalid token direvoke.

## Acceptance

- Satu live transaction berhasil untuk setiap channel payment aktif.
- Webhook paid, duplicate paid, failed, dan expired terverifikasi di log production.
- WhatsApp template production approved dan minimal satu pesan opt-in terkirim.
- Runbook rollback sudah dipahami operator sebelum rilis.
