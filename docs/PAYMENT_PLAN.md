# Payment Plan Kojaya — Anggota, Keanggotaan, POS, dan Pembayaran Lain

**Last updated:** 2026-06-28  
**Primary gateway:** Midtrans via `App\Services\Integrations\MidtransPaymentProvider`  
**Current source of truth:** `CooperativePayment` untuk iuran/simpanan/keanggotaan, `PosPayment` untuk POS, `PosMemberCreditPayment` untuk pelunasan kredit POS, dan `LoanPayment` untuk angsuran pinjaman.

## Status Implementasi Saat Ini

### Sudah Live

- Midtrans sandbox sudah dikonfigurasi di `.env` lokal pada 2026-06-28 dengan `MIDTRANS_IS_PRODUCTION=false`, merchant id `M156573283`, client key sandbox, dan `MIDTRANS_VA_BANK=permata`. Server key sengaja tidak ditulis di dokumen repo; simpan hanya di `.env`/secret manager.
- `POST /api/v1/member/dues/invoices/{invoice}/payment-intent` membuat `CooperativePayment` pending untuk invoice iuran/simpanan anggota.
- `POST /api/v1/member/bills/{bill}/payment-intent` membuat intent + charge untuk bill `dues:{id}`.
- `POST /api/payments/charge` men-charge `CooperativePayment` melalui `PaymentGatewayService`.
- `POST /api/payments/webhook` menerima webhook gateway, update `gateway_status`, lalu menjalankan `CooperativePaymentService::reconcile()` saat status `PAID`.
- Gateway punya fallback internal jika Midtrans belum dikonfigurasi, sehingga flow lokal tetap bisa diuji.
- Member bills default menggabungkan `dues` dan `loan`; `pos_credit` tersedia hanya bila client meminta `category=pos_credit` agar tagihan iuran tidak tercampur dengan domain POS.
- POS kasir mendukung `CASH`, `TRANSFER`, `QRIS`, dan `MEMBER_CREDIT` melalui `PosTransactionService`.
- POS member credit sudah punya pencatatan pelunasan manual melalui `MemberCreditService`.
- Upload bukti transfer manual anggota tetap tersedia lewat `POST /api/v1/member/payments/proof`.
- `member_payment_intents` sudah tersedia untuk payment gateway lintas domain non-iuran: `loan_installment`, `pos_credit`, dan `coffee_order`.
- `POST /api/v1/member/bills/{bill}/payment-intent` sudah mendukung `loan:*` dan `pos_credit:*`; webhook `PAID` menyelesaikan ke `LoanService::recordPayment()` atau `MemberCreditService::recordPayment()`.
- `POST /api/v1/member/coffee/orders` sudah membuat payment intent + charge terlebih dahulu dan mendukung payload `items[]` untuk beberapa item kopi dalam satu order. POS transaction, stock deduction, dan `coffee_orders` baru dibuat setelah webhook `PAID`.

### Belum Boleh Dianggap Selesai

- Gateway settlement baru aman untuk `CooperativePayment` iuran/simpanan. Jangan gunakan `CooperativePayment` untuk loan/POS credit tanpa mapper settlement domain.
- Settlement `loan`, `pos_credit`, dan `coffee_order` sudah ada jalur awalnya, tetapi masih perlu sandbox E2E Midtrans untuk expired/failed/retry/duplicate webhook per domain.
- Belum ada polling endpoint khusus `member_payment_intents`; mobile saat ini memakai response charge awal dan domain endpoint setelah settlement.

## Prinsip Arsitektur

1. **Jangan campur domain settlement.** Iuran/simpanan, pinjaman, POS, dan kredit anggota punya service dan ledger berbeda.
2. **Gateway hanya layer koleksi dana.** Setelah `PAID`, service domain yang mem-posting ledger.
3. **Idempotency wajib untuk write finansial.** Endpoint create intent/charge harus memakai middleware `idempotent`.
4. **Member hanya boleh bayar miliknya sendiri.** Scope selalu lewat `cooperative_member_id` dari user login.
5. **Webhook tidak boleh dipercaya tanpa signature.** Provider Midtrans wajib memverifikasi signature sebelum status dipakai.
6. **Fallback internal hanya untuk dev/local.** Produksi wajib Midtrans configured dan diuji sandbox/live small transaction.

## Kontrak API Saat Ini

### Member Portal Web (Native Inline Checkout)

Anggota membayar iuran/simpanan dari `/member` (web, sesi Inertia) dengan dialog **native** — pilih metode (QRIS/VA/E-Wallet), lalu QR/VA ditampilkan langsung di dialog (bukan modal web Midtrans). Pakai direct charge per channel; settlement tetap lewat webhook yang sama (`order_id` → `gateway_reference`).

```http
POST member/payments/intent                          # body: { cooperative_dues_invoice_id, channel: QRIS|VA|E_WALLET } → CooperativePayment + direct charge (qr_string / va_number / checkout_url)
GET  member/payments/{payment}/status                # polling status gateway untuk deteksi PAID
```

Provider: `PaymentGatewayService::createCharge()` (Midtrans `/v2/qris/charge` atau `/v2/charge`). QRIS `qr_string` di-render jadi QR di frontend lewat library `qrcode` npm. Fallback internal tetap tersedia bila Midtrans belum dikonfigurasi (dev/test).

Untuk sandbox yang belum mengaktifkan QRIS/e-wallet, endpoint web `member/payments/intent` otomatis retry ke channel `VA` memakai `MIDTRANS_VA_BANK`. Response tetap `201` dan menyertakan `requested_channel` + `fallback_reason` agar dialog member menampilkan Virtual Account aktual, bukan error 503.

### Unified Bills

```http
GET /api/v1/member/bills?category=dues|loan|pos_credit
GET /api/v1/member/bills/{bill}
POST /api/v1/member/bills/{bill}/payment-intent
```

Tanpa parameter `category`, daftar bills hanya mengembalikan `dues` dan `loan`. Gunakan `category=pos_credit` untuk konteks hutang belanja POS dan arahkan UI ke transaksi/POS credit, bukan halaman tagihan iuran.

`{bill}` adalah composite id:

- `dues:{cooperative_dues_invoice_id}`
- `loan:{loan_installment_id}`
- `pos_credit:{cooperative_member_id}`

`payment-intent` mendukung `dues:*`, `loan:*`, dan `pos_credit:*`. POS credit tetap dipanggil eksplisit dengan `category=pos_credit`/`pos_credit:{member_id}` agar tidak muncul sebagai tagihan iuran default.

Payload:

```json
{
  "channel": "QRIS"
}
```

Channel valid: `QRIS`, `VA`, `E_WALLET`, `TRANSFER`.

### Direct Charge

```http
POST /api/payments/charge
```

Payload:

```json
{
  "cooperative_payment_id": 123,
  "channel": "QRIS"
}
```

Endpoint ini tetap ada untuk backward compatibility dan admin/operator integrations. Untuk mobile anggota, prefer `POST /api/v1/member/bills/{bill}/payment-intent`.

### Webhook

```http
POST /api/payments/webhook
```

Jika Midtrans configured, webhook diverifikasi oleh `MidtransPaymentProvider::verifyWebhook()`. Jika status gateway `PAID`, controller memanggil `CooperativePaymentService::reconcile()`.

## Target Model Jangka Menengah

Tambahkan payment intent generik agar satu gateway flow bisa menampung semua payable domain tanpa memaksa semuanya menjadi `CooperativePayment`.

Rencana tabel:

```text
member_payment_intents
- id
- user_id
- cooperative_member_id
- payable_type      // dues_invoice, loan_installment, pos_credit, pos_order, other
- payable_id        // id domain
- amount
- channel
- gateway_provider
- gateway_reference
- gateway_status    // PENDING, PAID, EXPIRED, FAILED, CANCELLED, REFUNDED
- gateway_payload
- expires_at
- settled_at
- settled_by_service // cooperative_payment, loan_payment, member_credit, pos
- created_at, updated_at
```

Settlement dispatcher:

```text
PaymentGatewayWebhook -> PaymentGatewayService -> MemberPaymentSettlementService
  dues_invoice      -> CooperativePaymentService::reconcile()
  loan_installment  -> LoanService::recordPayment()
  pos_credit        -> MemberCreditService::recordPayment()
  pos_order/coffee  -> PosTransactionService gateway settlement path
```

## Phase Plan

### Phase 0 — Audit dan Kontrak Stabil

Status: sebagian selesai.

- [x] Dokumentasikan bahwa `CooperativePayment` hanya untuk iuran/simpanan.
- [x] Tambahkan env/config Midtrans sandbox lokal (`MIDTRANS_MERCHANT_ID`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_IS_PRODUCTION=false`) tanpa menulis server key ke dokumen repo.
- [x] Tambahkan `POST /api/v1/member/bills/{bill}/payment-intent` untuk source `dues`.
- [x] Tampilkan `pos_credit` hanya saat `category=pos_credit` agar anggota melihat hutang POS tanpa mencampurnya ke tagihan iuran default.
- [x] Dukung `loan` dan `pos_credit` payment intent setelah settlement mapper tersedia.
- [x] Regenerasi `docs/openapi.json` setelah rute baru stabil.
- [x] Mapping channel `TRANSFER` ke payload Midtrans `bank_transfer` agar kontrak channel gateway tidak mengirim charge kosong.
- [x] Tambahkan fallback sandbox web checkout: QRIS/e-wallet inactive otomatis retry ke `VA` dengan `MIDTRANS_VA_BANK`.
- [ ] Tambahkan contoh Flutter untuk `bills -> payment-intent -> poll payment`.

### Phase 1 — Keanggotaan, Iuran, dan Simpanan

Tujuan: pembayaran anggota untuk simpanan pokok/wajib/sukarela berjalan end-to-end.

- [ ] Pastikan onboarding keanggotaan membuat invoice simpanan pokok/awal yang tampil di `/api/v1/member/bills`.
- [ ] Tambahkan UI member web/mobile untuk memilih channel dari payment intent.
- [ ] Pastikan webhook `PAID` membuat ledger `SAVING_PAYMENT`, update invoice `PAID/PARTIAL`, issue receipt, dan kirim notifikasi.
- [ ] Tambahkan test duplicate webhook, invalid signature, dan replay idempotency untuk `dues:*`.
- [ ] Tambahkan monitoring pending intent lebih dari 24 jam.

### Phase 2 — Angsuran Pinjaman

Tujuan: anggota bisa membayar `loan:{installment_id}` tanpa salah posting ledger.

- [x] Buat `member_payment_intents` atau mapper intent setara untuk `loan_installment`.
- [x] Tambahkan `MemberPaymentSettlementService::settleLoanInstallment()`.
- [x] Settlement harus memanggil `LoanService::recordPayment()` dengan `payment_method = gateway channel`.
- [ ] Pastikan allocation principal/interest/fee/penalty mengikuti logika `LoanService`.
- [ ] Tambahkan notifikasi `member.loan.payment_recorded` dan admin operational event.
- [ ] Test partial payment, overpayment rejection, duplicate webhook, dan pembayaran installment milik anggota lain.

### Phase 3 — POS Member Credit

Tujuan: anggota bisa melunasi hutang belanja POS dari aplikasi.

- [x] Gunakan source `pos_credit:{member_id}` dari unified bills.
- [x] Settlement harus memanggil `MemberCreditService::recordPayment()`.
- [x] Pastikan `cooperative_members.outstanding_balance` turun dan ledger `POS_MEMBER_CREDIT_PAYMENT` tercatat.
- [ ] Tambahkan history `pos_member_credit_payments` ke transaksi unified member.
- [ ] Test pembayaran sebagian, pembayaran penuh, overpayment rejection, duplicate webhook, dan void POS yang mengubah outstanding.

### Phase 4 — POS Checkout dan Pesan Kopi

Tujuan: transaksi POS member-facing tidak lagi mencatat QRIS sebagai paid sebelum settlement.

- [x] Tambahkan mode `PENDING_PAYMENT` untuk POS order yang dibuat dari member app.
- [x] Untuk kopi, `POST /api/v1/member/coffee/orders` sebaiknya membuat order + payment intent bila channel gateway dipilih.
- [x] Setelah gateway `PAID`, finalize POS transaction, deduct stock, posting journal, dan ubah coffee order `RECEIVED`.
- [ ] Jika gateway `EXPIRED/FAILED`, batalkan order pending tanpa mengurangi stok.
- [ ] Pertahankan POS kasir offline yang langsung `COMPLETED` untuk cash/QRIS manual.
- [ ] Test race condition: webhook datang dua kali, user polling saat pending, order expired.

### Phase 5 — Other Payments

Tujuan: satu pola untuk pembayaran non-iuran, misalnya biaya dokumen, denda, event koperasi, atau layanan khusus.

- [ ] Definisikan katalog payable `other_payment_types`.
- [ ] Tambahkan source `other:{id}` di bills bila biaya ditagihkan ke anggota.
- [ ] Settlement masuk ke service domain atau ledger scope khusus, bukan default `SAVING_PAYMENT`.
- [ ] Tambahkan kategori notifikasi `payment.other`.
- [ ] Tambahkan laporan rekonsiliasi per source.

### Phase 6 — Production Hardening

- [ ] Midtrans sandbox E2E untuk QRIS, VA, E-WALLET, TRANSFER/VA, expired, failed, duplicate webhook.
- [ ] Gunakan queue job untuk webhook jika latency provider meningkat.
- [ ] Dashboard monitoring gateway: pending aging, failed charge, webhook ignored, invalid signature.
- [ ] Runbook refund/cancel/manual reconciliation.
- [ ] Kunci `.env` production: `MIDTRANS_MERCHANT_ID`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_IS_PRODUCTION=true`, webhook URL, redirect URL.
- [ ] Smoke test transaksi kecil real sebelum go-live.

## Testing Matrix Minimum

| Domain | Create intent | Webhook PAID | Duplicate webhook | Invalid owner | Ledger/service |
| --- | --- | --- | --- | --- | --- |
| Dues/Savings | Required | Required | Required | Required | `CooperativePaymentService` |
| Loan installment | Phase 2 | Phase 2 | Phase 2 | Phase 2 | `LoanService` |
| POS credit | Phase 3 | Phase 3 | Phase 3 | Phase 3 | `MemberCreditService` |
| Coffee/POS order | Phase 4 | Phase 4 | Phase 4 | Phase 4 | `PosTransactionService` |
| Other payments | Phase 5 | Phase 5 | Phase 5 | Phase 5 | domain-specific |

## AI Implementation Notes

- Jangan membuat tabel `payments` generik lama dari template. Aplikasi ini sudah memakai model domain spesifik.
- Jangan mengubah `CooperativePaymentService::approve()` untuk loan/POS credit.
- Jika menambah settlement domain baru, tulis test feature dulu dan pastikan tidak membuat `SAVING_PAYMENT` untuk domain non-simpanan.
- Gunakan `PaymentGatewayService` dan kontrak `App\Contracts\Integrations\PaymentGatewayProvider`; jangan membuat adapter paralel baru tanpa alasan.
- Update `docs/api.md` dan `docs/openapi.json` setiap kali kontrak endpoint berubah.
