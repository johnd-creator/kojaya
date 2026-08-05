---
title: Alur Pembayaran Iuran via Midtrans
slug: anggota-payment-flow
summary: Cara membayar iuran bulanan, memilih invoice yang benar, dan mengunggah bukti.
category: Anggota · Pembayaran
module: payments
roles:
  - anggota
permissions: []
permission_mode: all
route_names:
  - member.payments.intent
  - member.payments.proof
  - member.payments.status
  - cooperative.payments.index
  - cooperative.payments.approve
  - cooperative.payments.bulk-approve
risk_level: medium
screenshot_entries:
  - anggota-payment-flow-desktop
related_articles:
  - anggota-portal-overview
last_reviewed_commit: 20c86960
status: published
sort_order: 20
---

# Alur Pembayaran Iuran via Midtrans

1. Buka **Simpanan** atau **Dashboard** anggota.
2. Pilih tagihan dengan status `pending`. Frontend hanya menampilkan
   invoice yang statusnya `pending` agar anggota tidak salah bayar.
3. Klik **Bayar**; sistem membuat payment intent via
   `route('member.payments.intent')`
   (`MemberPortalController@createPaymentIntent`).
4. Selesaikan pembayaran di `MidtransPaymentDialog.vue` dengan VA
   bank sandbox sesuai `MIDTRANS_VA_BANK` di `.env`.
5. Status dipantau di `route('member.payments.status')` sampai
   menjadi `paid`.

## Bukti transfer manual

Jika memilih manual, gunakan `PaymentProofDialog.vue` lalu unggah
bukti ke `route('member.payments.proof')`
(`MemberPortalController@uploadPaymentProof`). Bukti yang diunggah
menciptakan catatan `CooperativePayment` berstatus `PENDING` dan
masuk ke **antrean verifikasi** Admin Koperasi, bukan ke
`route('cooperative.dues.mark-paid')`.

> **Catatan:** `cooperative.dues.mark-paid` adalah endpoint perekaman
> kasir yang langsung menyetujui pembayaran. Antrean bukti anggota
> diproses melalui:
>
> - `route('cooperative.payments.index')` — daftar `PENDING`
> - `route('cooperative.payments.approve')` — verifikasi satu per satu
> - `route('cooperative.payments.bulk-approve')` — verifikasi
>   masal

## Notifikasi

Pembayaran sukses memperbarui `notifications` dan mengirim lewat
outbox dengan idempotency key = UUID outbox (lihat
[`docs/decisions.md`](../../decisions.md)).
