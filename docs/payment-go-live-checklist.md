# Payment Gateway Go-Live Checklist

Direkonsiliasi: 28 September 2026 (RC-07).

Checklist ini adalah gate operator sebelum integrasi dipakai di production, bukan izin menjalankan transaksi atau mengubah secret. Eksekusi memerlukan persetujuan release owner. Bukti lokal memakai provider palsu; bukti produksi dan perangkat tetap harus ditandatangani terpisah.

## Midtrans

- Pasang production `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, dan `MIDTRANS_IS_PRODUCTION=true` di secret manager production.
- Jalankan live transaction kecil untuk QRIS, VA, dan e-wallet dari aplikasi member, lalu pastikan payment berubah dari `PENDING` ke `PAID`.
- Validasi webhook production mengirim `signature_key`, diterima endpoint `/api/payments/webhook`, dan duplicate webhook tidak membuat rekonsiliasi ganda.
- Cocokkan settlement reference di dashboard Midtrans dengan `gateway_reference` dan `reconciliation_reference`.
- Saat insiden, hentikan checkout baru dan ikuti pemulihan yang disetujui. Runtime production tidak mengizinkan internal simulation; mengosongkan credential membuat charge gagal tertutup, bukan kembali ke payment flow internal. Jangan mengarahkan transaksi production ke sandbox dengan mengubah mode. Rekonsiliasi transaksi in-flight dan webhook sebelum membuka kembali checkout.

## WhatsApp Business API

- Pasang production `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_ENDPOINT`, dan `WHATSAPP_DEFAULT_COUNTRY_CODE`.
- Pastikan WhatsApp template untuk pengingat iuran, status leave, dan pembayaran sudah approved oleh Meta.
- Kirim pesan uji ke nomor opt-in internal dan pastikan outbox mencatat status sukses atau failure yang bisa di-retry.

## FCM

- Push Android **wajib aktif** untuk rilis Bundle A; tidak boleh dianggap lulus hanya karena credential kosong.
- Aktifkan Firebase Cloud Messaging API v1 pada project yang disetujui. Beri service account hanya izin pengiriman FCM yang diperlukan pada target project. Simpan JSON service account di secret mount privat, di luar repository, `public/`, dan `storage/app/public/`; batasi akses OS ke proses aplikasi/worker.
- Set `FCM_PROJECT_ID` dan `FCM_SERVICE_ACCOUNT_PATH` di konfigurasi runtime. Hapus `FCM_SERVER_KEY` dan `FCM_ENDPOINT` lama. Backend menggunakan OAuth RS256 dengan scope `firebase.messaging`, token berumur pendek, dan URL HTTPS Google tetap; tidak ada fallback legacy. Jangan mengunggah JSON/private key ke Git, chat, VITE variables, atau log.
- Jalankan `app:release-preflight --strict-production --require-android-push` pada konfigurasi stable production yang disetujui (untuk kandidat QA: `--strict-release-candidate --require-android-push`). Pemeriksaan membaca dan memvalidasi berkas secara lokal, **tidak** menguji izin IAM maupun pengiriman. Pastikan worker dimulai ulang setelah perubahan konfigurasi/key mount.
- Operator/mobile owner mendaftarkan token perangkat Android synthetic melalui kontrak `/api/devices/push-token` yang tidak berubah. Uji foreground/background, payload string/deep-link yang memang didukung aplikasi Kotlin, refresh token, serta user/tenant yang benar. Catat SHA backend, versi aplikasi, waktu, project alias, dan hasil tanpa token mentah.
- Penerimaan FCM (`name` respons) bukan bukti tampil di perangkat. Catat keduanya. `UNREGISTERED` yang bertipe FCM menyebabkan revoke; `INVALID_ARGUMENT`, salah sender, auth, kuota, atau timeout tidak boleh mencabut token secara membabi buta.
- Validasi retry outbox untuk kegagalan parsial, 429/503 dan `Retry-After`; tidak ada retry HTTP langsung. Pengiriman bersifat at-least-once: retry seluruh outbox dapat mengulang notifikasi pada perangkat yang sebelumnya sukses. Pastikan pengalaman client menangani duplikasi; jangan mengklaim exactly-once.
- Repository Kotlin ditemukan di `F:\kojayaapp`; implementasi pendamping memakai Firebase Messaging, registrasi terikat sesi, izin Android 13+, serta receiver data-only dengan pemeriksaan penerima. Konfigurasi Android per varian, IAM/backend, dan bukti perangkat masih diperlukan; test lokal tidak memakai service account asli atau jaringan FCM. Aktivasi production wajib ditutup di RC-11. APNs langsung tetap placeholder, bukan pengiriman iOS.
- Uji payload data-only versi 1 yang benar melalui backend, bukan notification-composer Firebase yang dapat melewati pemeriksaan receiver saat background. Uji logout, pergantian akun, process restart, dan penolakan izin; jangan memasang sender baru dengan client yang belum mendukung kontrak tersebut.

## Security / environment (RC-08)

- Strict production preflight requires `APP_ENV=production`, `APP_DEBUG=false`, a stable approved version, HTTPS `APP_URL` without embedded credentials, secure and HTTP-only session cookies, and the existing PII/ability safeguards. It prints check names, never values.
- Confirm trusted reverse-proxy addresses, TLS forwarding, database TLS/network restrictions, private object ACLs, filesystem permissions, and independent offsite recovery on the actual target. A local config review does not verify these controls remotely.
- Production mail must use an approved delivering transport; do not use `log` or a failover route to `log` for reset/verification messages. Keep credentials and PII keys out of VITE variables, source control, backups accessible to the public, and diagnostic output.
- Provider logs contain only safe status/identifiers; WhatsApp transport errors are replaced with a generic failure before outbox persistence. Retain established authentication, authorization, tenant isolation and secret rotation policy.

Rujukan kontrak: [FCM HTTP v1](https://firebase.google.com/docs/cloud-messaging/send/v1-api) dan [kode kegagalan FCM](https://firebase.google.com/docs/cloud-messaging/error-codes).

## Acceptance

- Satu live transaction berhasil untuk setiap channel payment aktif.
- Webhook paid, duplicate paid, failed, dan expired terverifikasi di log production.
- WhatsApp template production approved dan minimal satu pesan opt-in terkirim.
- Runbook rollback sudah dipahami operator sebelum rilis.
