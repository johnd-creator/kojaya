# Payment Plan Kojaya — Anggota, Keanggotaan, POS, dan Pembayaran Lain

**Last updated:** 2026-06-28  
**Primary gateway:** Midtrans via `App\Services\Integrations\MidtransPaymentProvider`  
**Current source of truth:** `CooperativePayment` untuk iuran/simpanan/keanggotaan, `PosPayment` untuk POS, `PosMemberCreditPayment` untuk pelunasan kredit POS, dan `LoanPayment` untuk angsuran pinjaman.

## Status Implementasi Saat Ini

### Sudah Live

- `POST /api/v1/member/dues/invoices/{invoice}/payment-intent` membuat `CooperativePayment` pending untuk invoice iuran/simpanan anggota.
- `POST /api/v1/member/bills/{bill}/payment-intent` membuat intent + charge untuk bill `dues:{id}`.
- `POST /api/payments/charge` men-charge `CooperativePayment` melalui `PaymentGatewayService`.
- `POST /api/payments/webhook` menerima webhook gateway, update `gateway_status`, lalu menjalankan `CooperativePaymentService::reconcile()` saat status `PAID`.
- Gateway punya fallback internal jika Midtrans belum dikonfigurasi, sehingga flow lokal tetap bisa diuji.
- Member bills sudah menggabungkan `dues`, `loan`, dan `pos_credit`.
- POS kasir mendukung `CASH`, `TRANSFER`, `QRIS`, dan `MEMBER_CREDIT` melalui `PosTransactionService`.
- POS member credit sudah punya pencatatan pelunasan manual melalui `MemberCreditService`.
- Upload bukti transfer manual anggota tetap tersedia lewat `POST /api/v1/member/payments/proof`.

### Belum Boleh Dianggap Selesai

- Gateway settlement baru aman untuk `CooperativePayment` iuran/simpanan. Jangan gunakan `CooperativePayment` untuk loan/POS credit tanpa mapper settlement domain.
- `loan:{installment_id}` di unified bills belum bisa dibuat payment intent gateway. Harus masuk ke `LoanService::recordPayment()` agar installment, outstanding, dan ledger pinjaman benar.
- `pos_credit:{member_id}` di unified bills belum bisa dibuat payment intent gateway. Harus masuk ke `MemberCreditService::recordPayment()` agar `outstanding_balance` turun dan ledger POS benar.
- Pembayaran POS checkout anggota/kopi saat ini mencatat metode `QRIS` sebagai metode pembayaran POS, bukan gateway charge yang menunggu settlement.
- Belum ada tabel payment intent generik lintas domain. Saat ini intent gateway disimpan langsung pada `cooperative_payments.gateway_*`.

## Prinsip Arsitektur

1. **Jangan campur domain settlement.** Iuran/simpanan, pinjaman, POS, dan kredit anggota punya service dan ledger berbeda.
2. **Gateway hanya layer koleksi dana.** Setelah `PAID`, service domain yang mem-posting ledger.
3. **Idempotency wajib untuk write finansial.** Endpoint create intent/charge harus memakai middleware `idempotent`.
4. **Member hanya boleh bayar miliknya sendiri.** Scope selalu lewat `cooperative_member_id` dari user login.
5. **Webhook tidak boleh dipercaya tanpa signature.** Provider Midtrans wajib memverifikasi signature sebelum status dipakai.
6. **Fallback internal hanya untuk dev/local.** Produksi wajib Midtrans configured dan diuji sandbox/live small transaction.

## Kontrak API Saat Ini

### Unified Bills

```http
GET /api/v1/member/bills?category=dues|loan|pos_credit
GET /api/v1/member/bills/{bill}
POST /api/v1/member/bills/{bill}/payment-intent
```

`{bill}` adalah composite id:

- `dues:{cooperative_dues_invoice_id}`
- `loan:{loan_installment_id}`
- `pos_credit:{cooperative_member_id}`

`payment-intent` saat ini hanya mendukung `dues:*`. Untuk `loan:*` dan `pos_credit:*`, API mengembalikan `422` sampai settlement domain selesai.

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
- [x] Tambahkan `POST /api/v1/member/bills/{bill}/payment-intent` untuk source `dues`.
- [x] Tampilkan `pos_credit` di unified bills agar anggota melihat hutang POS.
- [x] Tolak `loan` dan `pos_credit` payment intent dengan `422` sampai settlement mapper ada.
- [x] Regenerasi `docs/openapi.json` setelah rute baru stabil.
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

- [ ] Buat `member_payment_intents` atau mapper intent setara untuk `loan_installment`.
- [ ] Tambahkan `MemberPaymentSettlementService::settleLoanInstallment()`.
- [ ] Settlement harus memanggil `LoanService::recordPayment()` dengan `payment_method = gateway channel`.
- [ ] Pastikan allocation principal/interest/fee/penalty mengikuti logika `LoanService`.
- [ ] Tambahkan notifikasi `member.loan.payment_recorded` dan admin operational event.
- [ ] Test partial payment, overpayment rejection, duplicate webhook, dan pembayaran installment milik anggota lain.

### Phase 3 — POS Member Credit

Tujuan: anggota bisa melunasi hutang belanja POS dari aplikasi.

- [ ] Gunakan source `pos_credit:{member_id}` dari unified bills.
- [ ] Settlement harus memanggil `MemberCreditService::recordPayment()`.
- [ ] Pastikan `cooperative_members.outstanding_balance` turun dan ledger `POS_MEMBER_CREDIT_PAYMENT` tercatat.
- [ ] Tambahkan history `pos_member_credit_payments` ke transaksi unified member.
- [ ] Test pembayaran sebagian, pembayaran penuh, overpayment rejection, duplicate webhook, dan void POS yang mengubah outstanding.

### Phase 4 — POS Checkout dan Pesan Kopi

Tujuan: transaksi POS member-facing tidak lagi mencatat QRIS sebagai paid sebelum settlement.

- [ ] Tambahkan mode `PENDING_PAYMENT` untuk POS order yang dibuat dari member app.
- [ ] Untuk kopi, `POST /api/v1/member/coffee/orders` sebaiknya membuat order + payment intent bila channel gateway dipilih.
- [ ] Setelah gateway `PAID`, finalize POS transaction, deduct stock, posting journal, dan ubah coffee order `RECEIVED`.
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

- [ ] Midtrans sandbox E2E untuk QRIS, VA, E-WALLET, expired, failed, duplicate webhook.
- [ ] Gunakan queue job untuk webhook jika latency provider meningkat.
- [ ] Dashboard monitoring gateway: pending aging, failed charge, webhook ignored, invalid signature.
- [ ] Runbook refund/cancel/manual reconciliation.
- [ ] Kunci `.env` production: `MIDTRANS_SERVER_KEY`, `MIDTRANS_IS_PRODUCTION=true`, webhook URL, redirect URL.
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
