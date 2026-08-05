---
title: Antrean Verifikasi Bukti Pembayaran
slug: admin-koperasi-payment-queue
summary: Cara memproses bukti transfer anggota yang masuk ke antrean verifikasi.
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
menjadi **Lunas** dan tercatat di pembukuan.

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
3. Periksa tiga hal utama:
   - **Kesesuaian nominal**: angka pada bukti transfer harus
     sama dengan nominal tagihan.
   - **Tanggal transfer**: tidak terlalu lama (idealnya kurang
     dari 7 hari).
   - **Nama pengirim**: sesuai dengan nama anggota.
4. Jika bukti valid, tekan **Setujui**. Status berubah menjadi
   **Lunas** dan anggota menerima notifikasi.
5. Jika bukti tidak valid, tekan **Tolak** dan berikan alasan
   singkat. Anggota akan diminta mengunggah ulang.
6. Untuk memproses banyak bukti sekaligus (misalnya pada akhir
   hari), gunakan **Verifikasi Massal**. Centang hanya bukti
   yang sudah diyakini valid, lalu setujui.

## Hasil yang diharapkan

- Bukti yang valid ditandai **Lunas**.
- Bukti yang tidak valid ditandai **Ditolak** dengan alasan
  yang jelas.
- Antrean verifikasi kosong pada akhir hari.

## Status yang mungkin muncul

- **Menunggu Verifikasi**: bukti baru diunggah, belum diproses.
- **Disetujui**: bukti valid, pembayaran Lunas.
- **Ditolak**: bukti tidak valid; anggota dapat unggah ulang.
- **Diproses Massal**: bukti sedang dalam proses verifikasi
  sekaligus.

## Kondisi gagal

- Bukti tidak terbaca → minta anggota unggah ulang.
- Nominal tidak sesuai → hubungi anggota untuk klarifikasi atau
  selisih.
- Tanggal transfer terlalu lama → tolak dengan alasan dan minta
  anggota membayar bulan berjalan.

## Hal yang tidak boleh dilakukan

- Menyetujui bukti tanpa memeriksa nominal dan tanggal.
- Menyetujui bukti yang jelas tidak valid.
- Mengubah nominal tagihan agar bukti menjadi "sesuai".
- Memproses bukti di luar wewenang tanpa delegasi.

## Handoff

- Bukti yang tidak bisa diputuskan → teruskan ke Manajer Koperasi.
- Selisih yang berulang dari anggota yang sama → laporkan ke
  Pengurus Koperasi.
- Antrean menumpuk karena sumber daya → koordinasikan dengan
  Manajer Koperasi untuk pembagian tugas.

## Prosedur terkait

- **Alur Pembayaran Iuran Bulanan** untuk langkah yang dilakukan
  anggota.
- **Dashboard Operasional Admin Koperasi** untuk menemukan
  menu Antrean.
