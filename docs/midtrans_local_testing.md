# Testing Midtrans Locally (localhost + ngrok)

Panduan mengetes integrasi Midtrans di `http://localhost:8000` **tanpa server produksi**.
Cocokkan dengan arsitektur yang sudah ada: Core API inline (bukan Snap popup), dialog
`MidtransPaymentDialog.vue`, webhook `POST /api/payments/webhook`, dan command
`midtrans:simulate-webhook`.

> Penting: Sandbox Midtrans memang dirancang untuk development lokal. API charge
> (QRIS/VA/E-Wallet) dan simulator pembayaran jalan dari laptop. Satu-satunya hal yang
> tidak otomatis sampai ke localhost polos adalah **webhook masuk** — karena Midtrans
> tidak bisa menjangkau `http://localhost`. Itulah yang diatasi ngrok / command simulasi.

---

## 0. Prasyarat (sudah diverifikasi untuk repo ini)

| Config | Nilai sandbox | Cek |
|---|---|---|
| `MIDTRANS_IS_PRODUCTION` | `false` | `php artisan tinker` → `config('services.midtrans.is_production')` |
| `MIDTRANS_SERVER_KEY` | (sandbox, akhiran `TdE4`) | harus non-kosong |
| `MIDTRANS_CLIENT_KEY` | (sandbox) | |
| `MIDTRANS_MERCHANT_ID` | `M156573283` | |
| `MIDTRANS_VA_BANK` | `permata` | bank VA untuk channel VA/TRANSFER |

Jalankan dua server:
```bash
php artisan serve          # http://localhost:8000  (backend)
npm run dev                # Vite HMR supaya dialog terbaru termuat
```

---

## 1. Cara A — ngrok (webhook otomatis sampai)

### 1.1 Expose localhost
```bash
ngrok http 8000
```
Akan muncul baris seperti:
```
Forwarding   https://abcd-123-45.ngrok-free.app -> http://localhost:8000
```
Catat URL HTTPS itu.

### 1.2 Set URL di Midtrans Sandbox Dashboard
1. Login ke https://dashboard.sandbox.midtrans.com/
2. **Settings → Payment → Payment Settings**
3. **Payment Notification URL** = `https://abcd-123-45.ngrok-free.app/api/payments/webhook`
4. **Finish Redirect URL** (opsional) = `https://abcd-123-45.ngrok-free.app/member/savings`
5. Save

### 1.3 (Opsional) Whitelist IP Midtrans
Jika ada firewall keluar, izinkan IP sandbox Midtrans: `103.58.103.177`.
Lihat https://docs.midtrans.com/docs/ip-address untuk daftar lengkap.

### 1.4 Jalankan transaksi
1. Login sebagai anggota → buka `http://localhost:8000/member/savings`
2. Klik **Bayar** pada invoice `UNPAID`/`PARTIAL`
3. Pilih metode:
   - **QRIS** → salin URL gambar QR → tempel di
     https://simulator.sandbox.midtrans.com/v2/qris/index → klik **Accept**
   - **Virtual Account (Permata)** → salin nomor VA → tempel di
     https://simulator.sandbox.midtrans.com/openapi/va/index (pilih Permata) → bayar
   - **E-Wallet (GoPay)** → buka `checkout_url`/deeplink di
     https://simulator.sandbox.midtrans.com/v2/deeplink/index
4. Midtrans settlement dikirim via ngrok ke app → dialog polling mendeteksi
   `is_paid=true` → muncul "Pembayaran Berhasil" → halaman reload otomatis.

### 1.5 Catatan QRIS di sandbox
Jika muncul error "Payment channel is not activated" saat QRIS:
- Flow member (`POST /member/payments/intent`) **otomatis fallback ke VA Permata**.
- Atau aktivasi QRIS di Sandbox Dashboard → **Payment Method Activation**.

---

## 2. Cara B — `midtrans:simulate-webhook` (tanpa ngrok)

Untuk mengetes settlement di laptop murni tanpa tunnel. Command membangun payload
Midtrans lengkap dengan `signature_key` valid (dihitung dari server key sandbox) lalu
POST ke endpoint webhook lokal sendiri.

```bash
# Buat charge dulu lewat dialog (dapat payment_id dari response /member/payments/intent),
# lalu simulasikan settlement:
php artisan midtrans:simulate-webhook {paymentId}

# Skenario lain:
php artisan midtrans:simulate-webhook {paymentId} --status=pending
php artisan midtrans:simulate-webhook {paymentId} --status=expire
php artisan midtrans:simulate-webhook {paymentId} --status=cancel
php artisan midtrans:simulate-webhook {paymentId} --status=deny
php artisan midtrans:simulate-webhook {paymentId} --payment-type=bank_transfer
```

Status yang didukung: `settlement`, `capture`, `pending`, `deny`, `cancel`, `expire`.

**Keamanan:** command menolak jalan jika `MIDTRANS_IS_PRODUCTION=true`. Hanya untuk sandbox.

**Alur lengkap:**
1. Buka dialog → pilih VA → `payment_id` muncul di response (atau cek
   `cooperative_payments` terbaru)
2. `php artisan midtrans:simulate-webhook 180` (ganti 180 dengan payment_id aktual)
3. Kembali ke browser → dialog polling mendeteksi sukses dalam ~3 detik

---

## 3. Debug

- **Webhook tidak sampai (Cara A):** cek ngrok masih hidup, URL dashboard sama, dan IP
  `103.58.103.177` tidak diblok. Pantau `storage/logs/laravel.log` + inspector ngrok
  (`http://127.0.0.1:4040`).
- **Signature mismatch:** pastikan `MIDTRANS_SERVER_KEY` lokal = key yang ada di
  Sandbox Dashboard (Settings → Access Keys). Signature dihitung dengan
  `sha512(order_id + status_code + gross_amount + server_key)`.
- **Charge gagal "402 Payment channel is not activated":** QRIS/GoPay belum aktif di
  akun sandbox. Pakai VA, atau aktivasi metode pembayaran di dashboard.

---

## 4. Referensi

- Sandbox test credentials: https://docs.midtrans.com/docs/testing-payment-on-sandbox
- Webhook: https://docs.midtrans.com/docs/https-notification-webhooks
- IP Midtrans: https://docs.midtrans.com/docs/ip-address
- Pindah ke production: https://docs.midtrans.com/docs/switching-to-production-mode

---

*Doc ini mendokumentasikan arsitektur yang sudah ada (`MidtransPaymentProvider` Core API,
bukan Snap). Lihat `docs/decisions.md` untuk konteks ADR.*
