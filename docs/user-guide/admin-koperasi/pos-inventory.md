---
title: Operasi Harian POS, Inventori, dan Setoran Kasir
slug: admin-koperasi-pos-inventory
summary: Alur shift, penjualan, retur, penyetoran kas, dan opname stok.
category: Admin Koperasi · Operasional
module: pos
roles:
  - admin_koperasi
permissions:
  - access_cooperative_pos
  - manage_pos_products
permission_mode: all
route_names:
  - cooperative.pos.index
  - cooperative.pos.shifts.index
  - cooperative.pos.shifts.open
  - cooperative.pos.shifts.close
  - cooperative.pos.transactions.index
  - cooperative.pos.transactions.store
  - cooperative.pos.coffee-orders.index
  - cooperative.pos.coffee-orders.update-status
  - cooperative.pos.void-requests.index
  - cooperative.pos.void-requests.process
  - cooperative.pos.returns.create
  - cooperative.pos.returns.store
  - cooperative.pos.credit.create
  - cooperative.pos.credit.store
  - cooperative.pos.closings.index
  - cooperative.pos.closings.close
  - cooperative.pos.inventory.counts.index
  - cooperative.pos.inventory.counts.create
  - cooperative.pos.inventory.counts.show
  - cooperative.pos.inventory.receipts.index
  - cooperative.pos.inventory.receipts.create
  - cooperative.pos.inventory.transfers.index
  - cooperative.pos.inventory.transfers.create
  - cooperative.pos.reports.index
  - cooperative.reports.index
  - cooperative.shu.index
risk_level: medium
screenshot_entries:
  - admin-koperasi-pos-inventory-desktop
related_articles:
  - admin-koperasi-payment-queue
last_reviewed_commit: 20c86960
status: published
sort_order: 30
---

# Operasi Harian POS, Inventori, dan Setoran Kasir

## Tujuan

Memandu Admin Koperasi dalam menjalankan operasional harian
koperasi: buka-tutup shift kasir, catat penjualan, kelola
pesanan khusus, retur, setoran kas, dan opname stok.

## Kapan digunakan

- Memulai shift kasir di pagi hari.
- Menjual barang, menerima pesanan, atau memproses retur.
- Mengelola opname stok, penerimaan barang, atau transfer
  gudang.
- Menutup shift di akhir hari dan merekam setoran kasir.

## Prasyarat

- Sudah login sebagai Admin Koperasi.
- Memiliki hak akses untuk POS dan/atau inventori.
- Saldo kas awal telah ditentukan oleh prosedur internal
  koperasi.

## Langkah penggunaan

### POS

1. Buka menu **POS**. Sistem menampilkan daftar shift.
2. Tekan **Buka Shift** untuk memulai shift kasir, lalu catat
   saldo awal.
3. Untuk mencatat penjualan, tekan **Transaksi Baru**, pilih
   produk, tentukan jumlah, lalu tekan **Simpan**. Pantau
   daftar transaksi pada menu **Daftar Transaksi**.
4. Untuk pesanan khusus (misalnya pesanan kopi), buka **Pesanan
   Kopi** dan ubah status pesanan ketika siap saji, selesai,
   atau dibatalkan.
5. Untuk transaksi yang perlu dibatalkan, buka **Permintaan
   Void**, pilih transaksi, dan proses sesuai prosedur
   pembatalan.
6. Untuk retur, buka **Retur**, pilih transaksi asal, isi
   alasan, lalu simpan retur.
7. Untuk kredit/angsuran anggota dari POS, buka **Kredit/Store
   Account**, pilih anggota, isi nominal, lalu simpan.

### Setoran kasir

1. Setelah shift berakhir, buka **Tutup Shift** untuk menutup
   shift kasir dan menghasilkan ringkasan penjualan.
2. Buka menu **Penyetoran Kasir** untuk merekam setoran harian.
3. Tekan **Tutup Setoran** untuk mengunci setoran hari tersebut.
   Akses laporan setoran memerlukan hak akses tambahan.

### Inventori

1. Untuk opname stok, buka **Stok Opname**, pilih periode, lalu
   catat hasil perhitungan fisik. Sistem akan menampilkan
   selisih.
2. Untuk penerimaan barang, buka **Penerimaan Barang**, isi
   supplier, produk, dan jumlah, lalu simpan.
3. Untuk transfer antar gudang, buka **Transfer Gudang**, isi
   gudang asal, gudang tujuan, produk, dan jumlah, lalu simpan.

## Hasil yang diharapkan

- Shift kasir tercatat rapi dengan saldo awal, total penjualan,
  dan saldo akhir yang seimbang.
- Stok barang di sistem sesuai dengan stok fisik.
- Setoran harian sudah direkam dan ditutup.

## Status yang mungkin muncul

- **Shift terbuka**: shift kasir sedang berjalan.
- **Shift tertutup**: shift sudah ditutup.
- **Permintaan void menunggu**: ada permintaan pembatalan yang
  perlu ditinjau.
- **Selisih stok**: opname menemukan perbedaan antara stok
  sistem dan fisik.

## Kondisi gagal

- Produk tidak ditemukan di POS → pastikan produk sudah
  diaktifkan di modul **Produk**.
- Void ditolak → transaksi tidak memenuhi kriteria pembatalan.
- Setoran tidak bisa ditutup → cek apakah seluruh transaksi
  sudah direkonsiliasi.

## Hal yang tidak boleh dilakukan

- Membuka shift tanpa menentukan saldo awal.
- Menutup shift tanpa menutup setoran harian.
- Memodifikasi stok di luar siklus opname/penerimaan.
- Memproses void di luar wewenang.

## Handoff

- Selisih setoran yang berulang → laporkan ke Manajer Koperasi.
- Permintaan void di luar wewenang → teruskan ke Manajer Koperasi.
- Opname dengan selisih besar → laporkan ke Pengurus Koperasi.

## Prosedur terkait

- **Dashboard Operasional Admin Koperasi** untuk menemukan menu
  POS dan Inventori.
- **Pemantauan Keuangan Harian** untuk rekonsiliasi Manajer.
