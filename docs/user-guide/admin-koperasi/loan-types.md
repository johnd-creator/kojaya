---
title: Mengelola Jenis Pinjaman
slug: admin-koperasi-loan-types
summary: Cara membuat, memperbarui, dan menonaktifkan jenis pinjaman untuk anggota.
category: Admin Koperasi · Pinjaman
module: loans
roles:
  - admin_koperasi
permissions:
  - manage_cooperative_loan_types
permission_mode: all
route_names:
  - cooperative.loan-types.index
  - cooperative.loan-types.store
  - cooperative.loan-types.update
  - cooperative.loan-types.destroy
risk_level: medium
screenshot_entries:
  - admin-koperasi-loan-types-desktop
related_articles:
  - admin-koperasi-operational-dashboard
last_reviewed_commit: 20c86960
status: published
sort_order: 20
---

# Mengelola Jenis Pinjaman

## Tujuan

Memandu Admin Koperasi dalam membuat dan memperbarui jenis
pinjaman (produk kredit) yang ditawarkan kepada anggota, termasuk
aturan plafon, bunga, dan tenor.

## Kapan digunakan

- Koperasi meluncurkan produk pinjaman baru.
- Aturan bunga, biaya admin, atau denda perlu disesuaikan.
- Jenis pinjaman yang sudah tidak digunakan perlu dinonaktifkan
  atau dihapus.

## Prasyarat

- Sudah login sebagai Admin Koperasi.
- Memiliki hak akses untuk mengelola jenis pinjaman.
- Keputusan perubahan produk pinjaman sudah disetujui Pengurus
  Koperasi.

## Langkah penggunaan

1. Buka menu **Pinjaman** lalu pilih **Jenis Pinjaman**.
2. Untuk membuat jenis pinjaman baru, tekan **Buat** dan isikan
   semua kolom wajib: kode jenis, nama, deskripsi, suku bunga,
   biaya admin, denda per hari, jumlah minimum dan maksimum,
   tenor minimum dan maksimum, serta status aktif/nonaktif.
3. Tekan **Simpan**. Jenis pinjaman baru akan muncul di daftar dan
   dapat dipilih anggota saat mengajukan pinjaman.
4. Untuk memperbarui jenis pinjaman, buka baris yang dimaksud dan
   tekan **Ubah**. Ubah nilai yang perlu disesuaikan, lalu
   simpan.
5. Untuk menghapus jenis pinjaman, tekan **Hapus** pada baris
   yang dimaksud. Sistem akan menolak jika jenis pinjaman
   tersebut sudah pernah dipakai oleh aplikasi anggota mana pun.

## Hasil yang diharapkan

- Daftar jenis pinjaman menampilkan produk terbaru sesuai
  perubahan.
- Perubahan aturan bunga, biaya, atau plafon langsung berlaku
  untuk aplikasi baru.
- Jenis pinjaman yang dinonaktifkan tidak lagi muncul di pilihan
  anggota.

## Status yang mungkin muncul

- **Aktif**: jenis pinjaman dapat dipilih anggota.
- **Nonaktif**: jenis pinjaman tidak muncul di pilihan anggota
  tetapi data historisnya tetap ada.
- **Tidak dapat dihapus**: jenis pinjaman sudah pernah dipakai
  oleh aplikasi; gunakan penonaktifan sebagai gantinya.

## Kondisi gagal

- Kode jenis bentrok dengan yang sudah ada → gunakan kode lain.
- Nilai di luar rentang yang diizinkan (misal bunga lebih dari
  100%) → sistem menolak.
- Penghapusan ditolak → gunakan penonaktifan.

## Hal yang tidak boleh dilakukan

- Mengubah aturan pinjaman yang sudah berjalan tanpa
  mengomunikasikan keanggotaan.
- Menghapus jenis pinjaman yang pernah dipakai, karena akan
  memutus jejak audit aplikasi.
- Mengubah suku bunga atau biaya admin tanpa keputusan Pengurus.

## Handoff

- Perubahan besar (misal peluncuran produk baru) →
  dokumentasikan keputusan pada notulen Pengurus dan
  beritahukan ke anggota melalui kanal komunikasi koperasi.
- Pertanyaan anggota tentang perubahan aturan → arahkan ke
  Admin Koperasi.
- Dampak terhadap pinjaman aktif → konsultasikan dengan Manajer
  Koperasi.

## Prosedur terkait

- **Dashboard Operasional Admin Koperasi** untuk menemukan
  menu Jenis Pinjaman.
- **Tinjauan Aplikasi Pinjaman oleh Manajer** untuk proses
  setelah aturan produk ditetapkan.
