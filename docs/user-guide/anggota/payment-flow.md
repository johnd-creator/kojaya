---
title: Alur Pembayaran Iuran Bulanan
slug: anggota-payment-flow
summary: Cara membayar iuran bulanan melalui QRIS, Virtual Account, atau E-Wallet.
category: Anggota · Pembayaran
module: payments
roles:
  - anggota
permissions: []
permission_mode: all
route_names:
  - member.savings
  - member.payments.intent
  - member.payments.status
risk_level: medium
screenshot_entries:
  - anggota-payment-flow-desktop
related_articles:
  - anggota-portal-overview
last_reviewed_commit: b20cd587
status: published
sort_order: 20
---

# Alur Pembayaran Iuran Bulanan

## Tujuan

Membantu anggota membayar iuran bulanan tepat waktu melalui
aplikasi menggunakan kanal pembayaran digital.

## Kapan digunakan

- Tagihan iuran bulanan sudah terbit.
- Ingin membayar tagihan yang belum lunas.
- Ingin melihat status pembayaran terkini.

## Prasyarat

- Sudah login sebagai anggota.
- Profil keanggotaan aktif.
- Tagihan bulan berjalan sudah tersedia di daftar tagihan.

## Langkah penggunaan

1. Buka **Simpanan** dari menu portal anggota.
2. Halaman Simpanan menampilkan seluruh tagihan iuran
   wajib bulanan, baik yang sudah lunas maupun yang belum
   dibayar. Pilih tagihan yang berstatus belum dibayar
   atau dibayar sebagian. Tombol **Bayar** tersedia pada
   tagihan yang masih memiliki sisa pembayaran.

   ![Halaman pembayaran iuran pada portal anggota](/docs/user-guide/screens/desktop/anggota-payment-flow-desktop.png)

3. Klik **Bayar**.
4. Pilih kanal pembayaran yang tersedia:
   - **QRIS**: sistem menampilkan kode QR untuk dipindai
     menggunakan aplikasi e-wallet atau m-banking.
   - **Virtual Account**: sistem menampilkan nomor Virtual
     Account untuk transfer melalui ATM atau m-banking.
   - **E-Wallet**: sistem memberikan tautan untuk
     melanjutkan pembayaran melalui aplikasi dompet
     digital.
5. Selesaikan pembayaran sesuai kanal yang dipilih. Sistem
   memeriksa status pembayaran dan memperbarui tagihan
   ketika pembayaran berhasil.

## Hasil yang diharapkan

- Tagihan yang dibayar berubah status menjadi **Lunas**.
- Histori simpanan memperbarui saldo.

## Status yang mungkin muncul

Status tagihan di halaman Simpanan:

- **Belum dibayar**: tagihan belum memiliki pembayaran.
- **Dibayar sebagian**: tagihan memiliki pembayaran namun
  masih ada sisa.
- **Lunas**: tagihan telah dibayar penuh.

Status pembayaran dapat dilihat oleh Admin Koperasi pada
halaman Antrean Verifikasi Pembayaran.

## Kondisi gagal

- Kanal pembayaran tidak aktif → pilih kanal lain yang
  tersedia atau hubungi Admin Koperasi.
- Kode QR tidak muncul → pastikan koneksi internet stabil
  dan coba kembali.
- Status tidak berubah setelah pembayaran → hubungi Admin
  Koperasi dengan menyertakan nomor tagihan.

## Hal yang tidak boleh dilakukan

- Membayar iuran anggota lain tanpa konfirmasi Admin Koperasi.
- Membagikan kode QR atau nomor Virtual Account kepada pihak
  yang tidak berkepentingan.

## Handoff

- Pembayaran yang berhasil otomatis tercatat di pembukuan.
- Jika kanal pembayaran bermasalah, hubungi Admin Koperasi.

## Prosedur terkait

- **Mengenal Portal Anggota** untuk menemukan menu pembayaran.
- **Antrean Verifikasi Pembayaran** (panduan Admin Koperasi)
  menjelaskan bagaimana pembayaran diproses.
