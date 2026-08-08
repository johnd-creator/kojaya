---
title: Mengelola Saldo Toko Anggota
slug: cooperative-store-credit
summary: Panduan role-aware untuk melihat akun Saldo Toko, riwayat transaksi, transfer, dan laporan.
category: Koperasi · Saldo Toko
module: store-credit
roles:
  - admin_koperasi
  - manajer_koperasi
  - pengurus_koperasi
permissions:
  - view_store_credit
permission_mode: all
route_names:
  - cooperative.store-credit.index
  - cooperative.store-credit.show
  - cooperative.store-credit.transfers.index
  - cooperative.store-credit.transfers.process
  - cooperative.store-credit.transfers.proof
  - cooperative.store-credit.report
risk_level: high
screenshot_entries: []
related_articles:
  - admin-koperasi-pos-inventory
  - manajer-financial-monitoring
last_reviewed_commit: 999684c5f72029bd52ea8dced11203cac344c2d1
status: published
sort_order: 50
---

# Mengelola Saldo Toko Anggota

## Tujuan

Membantu role koperasi memantau akun Saldo Toko anggota dan memahami
perbedaan antara melihat akun, mengelola akun, memproses transfer, dan
membaca laporan.

## Kapan digunakan

- Ingin mencari akun Saldo Toko anggota.
- Perlu memeriksa status akun atau riwayat transaksi.
- Ada transfer pengisian saldo yang menunggu proses.
- Perlu melihat ringkasan laporan Saldo Toko.

## Langkah penggunaan

1. Buka **Saldo Toko** dan cari anggota atau status akun yang diperlukan.
2. Buka detail akun untuk melihat saldo, limit, status, dan mutasi.
3. Periksa bukti transfer pada antrean transfer bila tersedia.
4. Gunakan aksi yang tampil pada role Anda untuk memproses transfer,
   memperbarui akun, atau menindaklanjuti status.
5. Buka **Laporan Saldo Toko** untuk melihat ringkasan yang tersedia bagi
   role Anda.

## Pembagian pekerjaan berdasarkan role

- **Admin Koperasi**: pekerjaan operasional akun dan tindak lanjut yang
  tersedia pada menu Saldo Toko.
- **Manajer Koperasi**: meninjau saldo, transfer, limit, dan laporan
  sesuai kewenangannya.
- **Pengurus Koperasi**: meninjau cakupan yang lebih luas dan mengambil
  keputusan pada tindakan yang memerlukan kewenangan Pengurus.

Menu dan tombol dapat berbeda antar role. Ikuti aksi yang benar-benar
tampil pada akun Anda.

## Hasil yang diharapkan

- Status akun dan mutasi dapat ditelusuri.
- Transfer diproses berdasarkan bukti dan status yang tersedia.
- Laporan dibaca sesuai cakupan akses role.

## Kondisi gagal

- Akun tidak ditemukan → periksa nomor atau nama anggota.
- Bukti transfer tidak tersedia → jangan memproses transfer sebelum
  bukti dapat diverifikasi.
- Aksi tidak tampil → role Anda tidak memiliki kewenangan untuk aksi itu;
  teruskan kepada Manajer atau Pengurus.

## Hal yang tidak boleh dilakukan

- Mengubah saldo tanpa alasan dan catatan yang dapat ditelusuri.
- Memproses transfer tanpa memeriksa bukti.
- Menganggap laporan Saldo Toko sebagai pengganti ledger koperasi.

## Prosedur terkait

- **Operasi Harian POS, Inventori, dan Setoran Kasir** untuk transaksi POS.
- **Pemantauan Keuangan Harian** untuk pemantauan keuangan Manajer.
