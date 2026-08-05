---
title: Antrean Verifikasi Bukti Pembayaran
slug: admin-koperasi-payment-queue
summary: Cara memproses bukti transfer anggota yang masuk ke antrean PENDING.
category: Admin Koperasi · Pembayaran
module: payments
roles:
  - admin_koperasi
permissions:
  - manage_cooperative_payment
permission_mode: all
route_names:
  - cooperative.payments.index
  - cooperative.payments.approve
  - cooperative.payments.bulk-approve
risk_level: medium
screenshot_entries:
  - admin-koperasi-payment-queue-desktop
related_articles:
  - anggota-payment-flow
  - admin-koperasi-operational-dashboard
last_reviewed_commit: 20c86960
status: published
sort_order: 40
---

# Antrean Verifikasi Bukti Pembayaran

Bukti transfer yang diunggah anggota melalui
`route('member.payments.proof')` (`PaymentProofDialog.vue`)
menjadi catatan `CooperativePayment` berstatus `PENDING`.
Antrean ini **bukan** berasal dari `cooperative.dues.mark-paid`
— endpoint tersebut adalah perekaman kasir langsung berstatus
`APPROVED`.

## Alur verifikasi

1. Buka `route('cooperative.payments.index')` — secara default
   filter `status = PENDING` aktif untuk peran yang memiliki
   `canApprovePaymentsFromUi()`.
2. Periksa bukti transfer pada setiap baris. Bukti disimpan di
   `cooperative/payment-proofs/{member_id}`.
3. Setujui satu per satu via
   `route('cooperative.payments.approve')`.
4. Atau gunakan `route('cooperative.payments.bulk-approve')`
   untuk verifikasi masal (dipakai Admin pada akhir hari untuk
   memproses banyak bukti sekaligus).

## Hak akses

`canApprovePaymentsFromUi()` di
`App\Http\Controllers\Cooperative\CooperativePaymentController`
memerlukan:

- `manage_cooperative_payment`
- `verify_cooperative_member`

Pengguna dengan `view_cooperative_all` (Pengurus) tidak dapat
menyetujui dari antrean ini; Pengurus hanya memantau.
