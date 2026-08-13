---
title: Mengelola Iuran dan Membaca Ledger Simpanan
slug: cooperative-dues-and-ledger
summary: Panduan lintas role untuk memantau tagihan, membaca ledger, dan menindaklanjuti transaksi simpanan.
category: Koperasi · Keuangan
module: savings
roles:
  - admin_koperasi
  - manajer_koperasi
  - pengurus_koperasi
permissions:
  - manage_cooperative_dues
  - view_cooperative_ledger
permission_mode: any
route_names:
  - cooperative.dues.index
  - cooperative.dues.generate
  - cooperative.dues.mark-paid
  - cooperative.dues.mark-unpaid
  - cooperative.ledger.index
  - cooperative.savings.withdrawals.index
  - cooperative.savings.withdrawals.process
  - cooperative.ledger.cancel-payment
  - cooperative.ledger.revise-payment
risk_level: high
screenshot_entries: []
related_articles:
  - admin-koperasi-payment-queue
  - manajer-financial-monitoring
  - pengurus-shu-and-governance
last_reviewed_commit: 999684c5f72029bd52ea8dced11203cac344c2d1
status: published
sort_order: 45
---

# Mengelola Iuran dan Membaca Ledger Simpanan

## Tujuan

Menjelaskan cara membaca kondisi tagihan dan transaksi simpanan tanpa
mencampur pekerjaan operasional Admin dengan koreksi yang memerlukan
kewenangan lebih tinggi.

## Kapan digunakan

- Ingin melihat tagihan berdasarkan periode atau status.
- Perlu memeriksa apakah pembayaran sudah tercatat pada ledger.
- Ada transaksi yang memerlukan tindak lanjut atau koreksi.

## Langkah penggunaan

1. Buka **Iuran dan Tagihan** untuk melihat tagihan berdasarkan periode,
   status, anggota, kategori, atau jenis iuran.
2. Gunakan status **Belum lunas**, **Belum bayar**, **Sebagian**, **Lunas**,
   atau **Void** sesuai kebutuhan pemeriksaan.
3. Buka **Ledger Simpanan** untuk melihat mutasi simpanan berdasarkan
   tanggal, scope, jenis simpanan, atau tipe mutasi.
4. Cocokkan rincian tagihan dengan transaksi ledger dan bukti yang
   tersedia sebelum mengambil tindakan.
5. Untuk penarikan simpanan atau koreksi transaksi, gunakan aksi yang
   tampil pada akun role Anda dan ikuti catatan auditnya.

## Pembagian pekerjaan berdasarkan role

- **Admin Koperasi**: mengelola tagihan dan pekerjaan iuran harian, serta
  membaca ledger sesuai akses yang diberikan.
- **Manajer Koperasi**: meninjau kondisi keuangan, penarikan, dan
  transaksi yang memerlukan rekonsiliasi atau koreksi.
- **Pengurus Koperasi**: meninjau transaksi dan mengambil keputusan pada
  kasus yang berada dalam kewenangan tata kelola.

## Hasil yang diharapkan

- Status tagihan sesuai dengan kondisi transaksi yang dapat diverifikasi.
- Mutasi simpanan dapat ditelusuri melalui ledger.
- Koreksi atau penarikan tidak dilakukan tanpa pemeriksaan dan role yang
  sesuai.

## Kondisi gagal

- Tagihan belum terlihat → periksa cakupan periode dan filter status.
- Ledger kosong → periksa rentang tanggal dan scope transaksi.
- Aksi koreksi tidak tersedia → teruskan kepada Manajer atau Pengurus
  sesuai kewenangan.

## Hal yang tidak boleh dilakukan

- Mengubah status pembayaran hanya untuk menutup selisih.
- Menganggap tagihan **Sebagian** sebagai sudah lunas.
- Menjalankan koreksi tanpa alasan dan pemeriksaan pendukung.

## Handoff

- Verifikasi bukti pembayaran → **Antrean Verifikasi Pembayaran**.
- Anomali ledger atau penarikan → Manajer Koperasi.
- Keputusan tata kelola atau koreksi berisiko tinggi → Pengurus Koperasi.

## Prosedur terkait

- **Antrean Verifikasi Pembayaran** untuk pembayaran yang menunggu
  verifikasi.
- **Pemantauan Keuangan Harian** untuk pemeriksaan tingkat manajerial.
