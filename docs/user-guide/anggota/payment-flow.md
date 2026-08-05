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
last_reviewed_commit: 20c86960
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
- Profil keanggotaan aktif dan data finansial lengkap.
- Tagihan bulan berjalan sudah tersedia di daftar tagihan.

## Langkah penggunaan

1. Buka **Simpanan** dari menu portal anggota.
2. Pilih tagihan dengan status yang menandakan tagihan belum
   dibayar. Sistem hanya menampilkan tagihan yang belum
   dibayar, sehingga anggota tidak salah memilih bulan.
3. Klik **Bayar**.
4. Pilih kanal pembayaran yang tersedia:
   - **QRIS**: sistem menampilkan kode QR untuk dipindai
     menggunakan aplikasi e-wallet atau m-banking apa pun.
   - **Virtual Account**: sistem menampilkan nomor Virtual
     Account untuk transfer melalui ATM atau m-banking.
   - **E-Wallet**: sistem memberikan tautan untuk melanjutkan
     pembayaran melalui aplikasi dompet digital.
5. Selesaikan pembayaran sesuai kanal yang dipilih. Sistem
   secara otomatis memeriksa status pembayaran dan memperbarui
   tagihan ketika pembayaran berhasil.

## Hasil yang diharapkan

- Tagihan berubah status menjadi **Terverifikasi**.
- Histori transaksi di menu **Transaksi** menampilkan
  pembayaran baru.

## Status yang mungkin muncul

- **Menunggu Pembayaran**: tagihan belum dibayar, kanal
  pembayaran belum dipilih.
- **Menunggu Verifikasi**: pembayaran sedang diproses oleh
  sistem pembayaran.
- **Terverifikasi**: pembayaran berhasil dan tercatat di
  pembukuan.

## Kondisi gagal

- Kanal pembayaran tidak aktif → sistem dapat mengalihkan
  otomatis ke Virtual Account. Coba gunakan kanal lain bila
  tetap gagal.
- Kode QR tidak muncul → pastikan koneksi internet stabil dan
  coba kembali.
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
- **Antrean Verifikasi Bukti Pembayaran** (panduan Admin
  Koperasi) menjelaskan bagaimana pembayaran diproses.
