# Plan API Core Midtrans - Member Sandbox Payments

## Ringkasan

Fokus implementasi saat ini adalah pembayaran anggota di `http://localhost:8000/member` dan API Kojayaku `/api/v1/member/*` memakai Midtrans Core API Sandbox, bukan Snap dan bukan production. Kunci keberhasilan fase ini adalah QRIS untuk tagihan iuran/simpanan anggota berhasil dibuat, QR dapat ditampilkan oleh UI member dan Flutter, lalu dibayar melalui Midtrans Sandbox QRIS Simulator sampai webhook mengubah pembayaran menjadi lunas.

Laravel tetap menjadi payment authority. Flutter dan web member hanya memanggil API Kojaya; `MIDTRANS_SERVER_KEY` hanya boleh berada di `.env`, secret manager, dan konfigurasi server-side `config/services.php`.

## Scope Prioritas

1. **Phase 1 - Member dues/savings payment**
   - Web member: `/member`, terutama dialog bayar di halaman member/savings dan dashboard.
   - API Flutter: `POST /api/v1/member/bills/dues:{invoice}/payment-intent`.
   - Channel: `QRIS`, `VA`, dan `E_WALLET`, dengan QRIS sebagai gate utama.

2. **Phase 2 - Loan/POS credit/member intent**
   - Tetap memakai `member_payment_intents`, tetapi baru dilanjutkan setelah QRIS dues/savings stabil.
   - Perlu endpoint QR/status khusus intent atau kontrak unified agar Flutter tidak keliru memakai endpoint `CooperativePayment`.

3. **Out of scope untuk fase ini**
   - `MIDTRANS_IS_PRODUCTION=true`.
   - Snap token, Snap checkout, Snap WebView.
   - POS cashier QRIS settlement.
   - Refund/reversal otomatis.
   - Broad seeding atau reset database lokal.

## Arsitektur Target

```text
Member Web / Kojayaku Flutter
  -> Kojaya member payment endpoint
  -> PaymentGatewayService
  -> MidtransPaymentProvider Core API Sandbox
  -> gateway payload disimpan server-side
  -> response provider-neutral ke client
  -> client render QR/VA/e-wallet dari data aman
  -> Midtrans Sandbox Simulator melakukan pembayaran
  -> POST /api/payments/webhook
  -> signature diverifikasi
  -> CooperativePaymentService::reconcile() berjalan sekali
  -> status endpoint menampilkan sukses ke client
```

## Kontrak API Aman

Response charge untuk web dan Flutter hanya boleh berisi:

```json
{
  "provider": "midtrans",
  "reference": "KOJ-99-ABCD1234",
  "status": "PENDING",
  "channel": "QRIS",
  "amount": 100000,
  "checkout_url": null,
  "qr_image_url": "/api/v1/member/payments/99/qris-image",
  "expires_at": "2026-07-02 10:00:00",
  "instructions": {
    "title": "Scan QRIS untuk membayar",
    "description": "Status pembayaran diperbarui setelah Midtrans mengonfirmasi transaksi."
  },
  "poll_after_seconds": 5
}
```

Tidak boleh mengekspos server key, Basic Authorization, raw secret, atau `qr_action_url` ke Flutter/web. Action URL Midtrans tetap disimpan di `gateway_payload` dan diambil server-side oleh endpoint QR image.

## Status Mapping

| Midtrans | Kojaya | Rekonsiliasi |
| --- | --- | --- |
| `pending` | `PENDING` | Tidak |
| `settlement` | `PAID` | Ya, sekali |
| `capture` + fraud accepted | `PAID` | Ya, sekali |
| `expire` | `EXPIRED` | Tidak |
| `deny` / `failure` | `FAILED` | Tidak |
| `cancel` | `CANCELLED` | Tidak |
| `refund` / `partial_refund` | Deferred refund state | Tidak otomatis |

## Checklist Implementasi

- [x] Konfirmasi `services.midtrans` membaca sandbox config dari `config/services.php`.
- [x] Pastikan `MidtransPaymentProvider` memakai `/v2/charge` Core API untuk QRIS/VA/e-wallet.
- [x] Pastikan QRIS menyimpan action `generate-qr-code-v2` / `generate-qr-code` di `gateway_payload`.
- [x] Pastikan response public memakai `qr_image_url`, bukan raw action URL.
- [x] Update member web dialog agar menampilkan QR dari `qr_image_url` ketika `qr_string` tidak tersedia.
- [ ] Manual sandbox: bayar QRIS melalui Midtrans Sandbox QRIS Simulator dengan tunnel webhook publik.
- [ ] Setelah QRIS sukses, validasi VA sandbox dan e-wallet sandbox sesuai channel aktif di akun Midtrans.
- [ ] Setelah dues/savings stabil, rancang endpoint QR/status untuk `MemberPaymentIntent` loan/POS-credit/coffee.

## Manual Sandbox Runbook

1. Pastikan `.env` lokal memakai:
   - `MIDTRANS_IS_PRODUCTION=false`
   - `MIDTRANS_SERVER_KEY=<sandbox server key>`
   - `MIDTRANS_CLIENT_KEY=<sandbox client key jika dibutuhkan>`
   - `MIDTRANS_VA_BANK=permata`
2. Jalankan app lokal:
   - `php artisan serve`
   - `npm run dev`
3. Buat tunnel HTTPS ke `localhost:8000` dengan ngrok atau Cloudflare Tunnel.
4. Set Midtrans Sandbox Dashboard payment notification URL ke:
   - `https://<tunnel-host>/api/payments/webhook`
5. Login sebagai anggota aktif di `http://localhost:8000/member`.
6. Pilih tagihan iuran/simpanan `UNPAID` atau `PARTIAL`.
7. Pilih QRIS dan pastikan QR image tampil.
8. Buka Midtrans Sandbox QRIS Simulator, gunakan QR image yang ditampilkan, lalu klik accept/pay.
9. Verifikasi:
   - webhook masuk ke Laravel,
   - `cooperative_payments.gateway_status = PAID`,
   - `cooperative_payments.status = APPROVED`,
   - invoice paid amount bertambah,
   - ledger `SAVING_PAYMENT` hanya satu,
   - polling UI berubah menjadi sukses.
10. Replay webhook yang sama untuk memastikan tidak ada double ledger/reconciliation.

## Test Plan

Target test:

- `tests/Feature/MemberPortal/MemberPaymentIntentWebTest.php`
- `tests/Feature/PhaseBContractApiTest.php`
- `tests/Feature/SimulateMidtransWebhookCommandTest.php`
- `tests/Feature/MemberPortal/MemberUnifiedEndpointsTest.php`

Coverage wajib:

- Member tidak bisa charge invoice anggota lain.
- QRIS charge menghasilkan payload Midtrans Core API yang benar.
- Response client aman dan tidak memuat secret/action URL.
- QR image endpoint authenticated dan member-scoped.
- Webhook invalid signature ditolak.
- Settlement webhook approve/reconcile sekali.
- Replay webhook tidak menggandakan ledger.
- Expire/deny/cancel tidak reconcile.
- VA mengembalikan nomor VA/instruksi.
- E-wallet mengembalikan checkout/deeplink aman.

## Referensi Operasional

- Core API: `https://docs.midtrans.com/docs/custom-interface-core-api`
- Sandbox testing: `https://docs.midtrans.com/docs/testing-payment-on-sandbox`
- Webhook: `https://docs.midtrans.com/docs/https-notification-webhooks`
- Transaction status: `https://docs.midtrans.com/docs/transaction-status-cycle`
- Local guide existing: `docs/midtrans_local_testing.md`
- QRIS implementation plan existing: `docs/midtrans_plan/plan_midtrans_native_core_api_qris_sandbox.md`
