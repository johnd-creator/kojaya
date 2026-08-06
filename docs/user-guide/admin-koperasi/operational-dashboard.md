---
title: Dashboard Operasional Admin Koperasi
slug: admin-koperasi-operational-dashboard
summary: Pintasan ke modul anggota, simpan pinjam, POS, dan inventori.
category: Admin Koperasi · Memulai
module: dashboard
roles:
  - admin_koperasi
permissions: []
permission_mode: all
route_names:
  - cooperative.operator.dashboard
  - cooperative.members.index
  - cooperative.members.create
  - cooperative.members.edit
  - cooperative.members.show
  - cooperative.members.resignations.index
  - cooperative.members.opening-balance.show
  - cooperative.dues.index
  - cooperative.loans.index
  - cooperative.loans.create
  - cooperative.loans.show
  - cooperative.loans.calculator
  - cooperative.loan-types.index
  - cooperative.pos.index
  - cooperative.pos-products.index
  - cooperative.pos-categories.index
risk_level: low
screenshot_entries:
  - admin-koperasi-operational-dashboard-desktop
related_articles:
  - admin-koperasi-loan-types
  - admin-koperasi-pos-inventory
  - admin-koperasi-payment-queue
last_reviewed_commit: b20cd587
status: published
sort_order: 10
---

# Dashboard Operasional Admin Koperasi

## Tujuan

Memberikan Admin Koperasi peta menu operasional yang paling sering
digunakan setiap hari, sehingga perpindahan antar modul menjadi
lebih cepat.

## Kapan digunakan

- Mulai shift kerja dan ingin masuk ke modul yang akan dikerjakan.
- Ingin berpindah dari satu modul (misalnya POS) ke modul lain
  (misalnya Antrean Pembayaran) tanpa harus ke beranda.
- Ingin menemukan pintasan modul yang belum pernah diakses
  sebelumnya.

## Prasyarat

- Sudah login sebagai Admin Koperasi.
- Akun dalam kondisi aktif dan tidak terkunci.

## Langkah penggunaan

1. Masuk ke aplikasi sebagai Admin Koperasi. Sistem akan membuka
   halaman **Dashboard Operasional** sebagai halaman awal.
2. Pada halaman **Dashboard Operasional**, pilih modul yang
   ingin dikerjakan.
3. Untuk pekerjaan administrasi keanggotaan, buka bagian
   **Keanggotaan** dan pilih **Daftar Anggota** untuk melihat,
   menambah, atau memperbarui data anggota. Permintaan
   pengunduran diri dapat diproses di sub-menu **Pengunduran
   Diri**, dan saldo awal anggota baru di sub-menu **Saldo
   Pembuka**.
4. Untuk pekerjaan iuran, buka menu **Iuran** untuk melihat
   tagihan dan status pembayaran anggota.
5. Untuk pekerjaan pinjaman, buka menu **Pinjaman**. Di sini
   Admin dapat membuat pinjaman baru, melihat daftar pinjaman,
   membuka detail, atau menggunakan kalkulator plafon. Jenis
   pinjaman dikelola di sub-menu **Jenis Pinjaman**.
6. Untuk pekerjaan kasir, buka menu **POS**. Di sini Admin
   menjalankan shift kasir, transaksi penjualan, dan operasional
   produk. Sub-menu **Produk** dan **Kategori** digunakan untuk
   mengelola daftar barang yang dijual.
7. Kembali ke **Dashboard Operasional** kapan saja untuk
   berpindah modul.

## Hasil yang diharapkan

- Admin Koperasi dapat berpindah modul dalam beberapa detik
  tanpa harus menavigasi dari beranda.
- Setiap modul menampilkan status pekerjaan yang sedang
  berlangsung (jumlah antrean, pinjaman menunggu verifikasi,
  dsb.).

## Status yang mungkin muncul

- **Antrean verifikasi**: jumlah bukti pembayaran yang menunggu
  diproses.
- **Pinjaman menunggu**: jumlah pinjaman yang sudah diajukan
  anggota dan siap ditinjau Manajer.
- **Shift kasir terbuka**: indikator shift POS yang sedang
  berjalan.

## Kondisi gagal

- Modul tidak bisa dibuka → cek apakah akun Anda memiliki hak
  akses untuk modul tersebut.
- Pintasan tidak muncul → hubungi Pengurus Koperasi untuk
  pengaturan hak akses.
- Perubahan data tidak tersimpan → cek koneksi internet dan
  ulangi.

## Hal yang tidak boleh dilakukan

- Mengakses modul yang bukan tanggung jawabnya.
- Membiarkan sesi login terbuka tanpa pengawasan.
- Memodifikasi data anggota tanpa meninggalkan catatan alasan
  pada sistem.

## Handoff

- Verifikasi bukti pembayaran → **Antrean Verifikasi Bukti
  Pembayaran**.
- Permintaan perubahan jenis pinjaman → **Mengelola Jenis
  Pinjaman**.
- Operasional harian kasir → **Operasi Harian POS, Inventori,
  dan Setoran Kasir**.
- Peninjauan aplikasi pinjaman → teruskan ke **Manajer Koperasi**.

## Prosedur terkait

- **Mengelola Jenis Pinjaman** untuk aturan plafon dan tenor.
- **Antrean Verifikasi Bukti Pembayaran** untuk proses bukti
  transfer.
- **Operasi Harian POS, Inventori, dan Setoran Kasir** untuk
  panduan kasir.
