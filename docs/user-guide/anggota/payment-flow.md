---
title: Alur Pembayaran Iuran Bulanan
slug: anggota-payment-flow
summary: Cara membayar iuran bulanan, memilih invoice yang benar, dan mengunggah bukti transfer.
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

# Alur Pembayaran Iuran Bulanan

## Tujuan

Membantu anggota membayar iuran bulanan tepat waktu melalui
aplikasi, baik lewat pembayaran otomatis maupun transfer manual
dengan bukti.

## Kapan digunakan

- Tagihan iuran bulanan sudah terbit.
- Ingin melihat status tagihan dan bukti bayar.
- Ingin mengganti metode pembayaran dari otomatis ke manual atau
  sebaliknya.

## Prasyarat

- Sudah login sebagai anggota.
- Profil keanggotaan aktif dan data finansial lengkap.
- Tagihan bulan berjalan sudah tersedia di daftar tagihan.

## Langkah penggunaan

1. Buka **Simpanan** atau **Dashboard** anggota.
2. Pilih tagihan dengan status **Menunggu Pembayaran**. Sistem
   hanya menampilkan tagihan yang belum dibayar, sehingga anggota
   tidak salah memilih bulan.
3. Klik **Bayar**.
4. Jika memilih pembayaran otomatis, lengkapi pembayaran di
   halaman bank virtual yang muncul. Gunakan kode bayar atau
   nomor Virtual Account yang ditampilkan.
5. Jika memilih transfer manual, klik **Unggah Bukti Transfer**
   dan lampirkan foto atau PDF bukti transfer dari bank.
6. Tunggu status berubah dari **Menunggu Pembayaran** menjadi
   **Lunas** setelah Admin Koperasi memverifikasi bukti.

## Hasil yang diharapkan

- Tagihan berubah status menjadi **Lunas**.
- Notifikasi berhasil diterima anggota.
- Histori transaksi di menu **Transaksi** menampilkan pembayaran
  baru.

## Status yang mungkin muncul

- **Menunggu Pembayaran**: tagihan terbit, belum dibayar.
- **Menunggu Verifikasi**: bukti sudah diunggah, menunggu
  persetujuan Admin Koperasi.
- **Lunas**: pembayaran diverifikasi Admin Koperasi.
- **Ditolak**: bukti tidak valid; anggota diminta unggah ulang.

## Kondisi gagal

- Bukti transfer tidak terbaca → pastikan foto tidak blur dan
  nominal terlihat jelas.
- Nominal transfer tidak sesuai tagihan → hubungi Admin Koperasi
  untuk klarifikasi selisih.
- Status tidak berubah setelah 1×24 jam → hubungi Admin Koperasi
  untuk cek antrean verifikasi.

## Hal yang tidak boleh dilakukan

- Mengunggah bukti transfer yang bukan milik sendiri.
- Memalsukan bukti pembayaran.
- Membayar iuran anggota lain tanpa konfirmasi Admin Koperasi.

## Handoff

- Bukti yang diunggah akan masuk ke **antrean verifikasi Admin
  Koperasi** dan diproses pada jam kerja.
- Jika bukti ditolak, anggota akan menerima notifikasi dan dapat
  mengunggah ulang.
- Pertanyaan teknis seputar tagihan → hubungi Admin Koperasi.

## Prosedur terkait

- **Mengenal Portal Anggota** untuk menemukan menu pembayaran.
- **Antrean Verifikasi Bukti Pembayaran** (panduan Admin
  Koperasi) menjelaskan bagaimana bukti diverifikasi.
