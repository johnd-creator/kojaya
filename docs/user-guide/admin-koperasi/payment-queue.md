---
title: Antrean Verifikasi Pembayaran
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
last_reviewed_commit: b20cd587
status: published
sort_order: 40
---

# Antrean Verifikasi Pembayaran

## Tujuan

Memandu Admin Koperasi dalam memverifikasi pembayaran iuran
anggota yang masuk ke antrean, sehingga pembayaran dapat
berubah status menjadi **Terverifikasi** dan tercatat di
pembukuan.

## Kapan digunakan

- Ada pembayaran anggota yang berstatus **Menunggu Verifikasi**.
- Akan memproses banyak pembayaran sekaligus pada akhir hari.

## Prasyarat

- Sudah login sebagai Admin Koperasi.
- Memiliki hak akses untuk verifikasi pembayaran.

## Langkah penggunaan

1. Buka menu **Pembayaran**. Sistem menampilkan daftar
   pembayaran dengan kolom tanggal, anggota, jenis simpanan,
   metode, status, keterangan, dan nominal.
2. Gunakan filter status **Menunggu Verifikasi** untuk melihat
   pembayaran yang belum diproses.
3. Periksa data pembayaran pada setiap baris:
   - **Nominal**: sesuai dengan tagihan anggota.
   - **Metode**: kanal pembayaran yang digunakan anggota.
   - **Anggota**: nama dan data anggota yang membayar.
4. Jika pembayaran valid, tekan ikon centang (**Setujui**).
   Status berubah menjadi **Terverifikasi** dan pembayaran
   tercatat di pembukuan.
5. Untuk memproses banyak pembayaran sekaligus, centang
   baris-baris yang sudah diyakini valid, lalu gunakan
   **Approve Semua** untuk verifikasi massal.

## Hasil yang diharapkan

- Pembayaran yang valid berstatus **Terverifikasi**.
- Pembayaran yang belum dapat diverifikasi tetap berstatus
  **Menunggu Verifikasi** hingga klarifikasi selesai.
- Antrean verifikasi kosong pada akhir hari.

## Status yang mungkin muncul

- **Menunggu Verifikasi**: pembayaran baru masuk, belum
  diproses.
- **Terverifikasi**: pembayaran valid, tercatat di pembukuan.

## Kondisi gagal

- Nominal tidak sesuai → hubungi anggota untuk klarifikasi
  selisih.
- Pembayaran belum dapat diverifikasi → biarkan tetap
  berstatus **Menunggu Verifikasi** dan lakukan klarifikasi
  di luar aplikasi.

## Hal yang tidak boleh dilakukan

- Menyetujui pembayaran tanpa memeriksa nominal.
- Menyetujui pembayaran yang jelas tidak valid.
- Mengubah nominal tagihan agar pembayaran menjadi sesuai.
- Memproses pembayaran di luar wewenang tanpa delegasi.

## Handoff

- Pembayaran yang tidak bisa diputuskan → teruskan ke Manajer
  Koperasi.
- Selisih yang berulang dari anggota yang sama → laporkan ke
  Pengurus Koperasi.
- Antrean menumpuk karena sumber daya → koordinasikan dengan
  Manajer Koperasi untuk pembagian tugas.

## Catatan tentang jangkauan fitur

- Fitur penolakan pembayaran terstruktur belum tersedia pada
  halaman pembayaran Kojaya. Pembayaran yang belum dapat
  diverifikasi tetap berstatus **Menunggu Verifikasi**.

## Prosedur terkait

- **Alur Pembayaran Iuran Bulanan** untuk langkah yang
  dilakukan anggota.
- **Dashboard Operasional Admin Koperasi** untuk menemukan
  menu Antrean.
