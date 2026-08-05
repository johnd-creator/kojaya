---
title: Antrean Verifikasi Bukti Pembayaran
slug: admin-koperasi-payment-queue
summary: Cara memverifikasi pembayaran iuran anggota yang masuk ke antrean.
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

## Tujuan

Memandu Admin Koperasi dalam memverifikasi bukti transfer iuran
yang diunggah anggota, sehingga pembayaran dapat berubah status
menjadi **Terverifikasi** dan tercatat di pembukuan.

## Kapan digunakan

- Ada bukti transfer anggota yang menunggu verifikasi pada
  antrean.
- Akan memproses banyak bukti sekaligus pada akhir hari.
- Bukti sebelumnya ditolak dan anggota diminta mengunggah ulang.

## Prasyarat

- Sudah login sebagai Admin Koperasi.
- Memiliki hak akses untuk verifikasi pembayaran.
- Bukti transfer yang diunggah anggota sudah tersedia di
  antrean.

## Langkah penggunaan

1. Buka menu **Pembayaran** dan pilih sub-menu **Antrean
   Verifikasi**. Sistem menampilkan daftar bukti yang menunggu
   verifikasi.
2. Buka setiap baris untuk melihat bukti transfer yang diunggah
   anggota.
3. Periksa kesesuaian data pembayaran:
   - **Nominal**: angka pembayaran harus sama dengan
     nominal tagihan.
   - **Nama pengirim**: sesuai dengan nama anggota.
4. Jika pembayaran valid, tekan **Setujui**. Status berubah
   menjadi **Terverifikasi** dan pembayaran tercatat di
   pembukuan.
5. Jika pembayaran tidak valid, berikan keterangan dan
   tolak. Anggota dapat membayar ulang.
6. Untuk memproses banyak pembayaran sekaligus (misalnya pada
   akhir hari), gunakan **Verifikasi Massal**. Centang hanya
   pembayaran yang sudah diyakini valid, lalu setujui.

## Hasil yang diharapkan

- Pembayaran yang valid ditandai **Terverifikasi**.
- Pembayaran yang tidak valid ditandai dan anggota dapat
  membayar ulang.
- Antrean verifikasi kosong pada akhir hari.

## Status yang mungkin muncul

Status di bawah menggunakan label yang tampil pada aplikasi:

- **Menunggu Verifikasi**: pembayaran baru masuk, belum
  diproses.
- **Terverifikasi**: pembayaran valid, tercatat di
  pembukuan.

## Kondisi gagal

- Nominal tidak sesuai → hubungi anggota untuk klarifikasi
  selisih.
- Pembayaran tidak dapat diverifikasi → berikan keterangan
  dan minta anggota membayar ulang.

## Hal yang tidak boleh dilakukan

- Menyetujui pembayaran tanpa memeriksa nominal.
- Menyetujui pembayaran yang jelas tidak valid.
- Mengubah nominal tagihan agar pembayaran menjadi "sesuai".
- Memproses pembayaran di luar wewenang tanpa delegasi.

## Handoff

- Pembayaran yang tidak bisa diputuskan → teruskan ke Manajer
  Koperasi.
- Selisih yang berulang dari anggota yang sama → laporkan ke
  Pengurus Koperasi.
- Antrean menumpuk karena sumber daya → koordinasikan dengan
  Manajer Koperasi untuk pembagian tugas.

## Prosedur terkait

- **Alur Pembayaran Iuran Bulanan** untuk langkah yang dilakukan
  anggota.
- **Dashboard Operasional Admin Koperasi** untuk menemukan
  menu Antrean.
