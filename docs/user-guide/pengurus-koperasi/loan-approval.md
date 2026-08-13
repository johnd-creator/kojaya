---
title: Persetujuan Akhir Pinjaman oleh Pengurus
slug: pengurus-loan-approval
summary: Kewenangan approval akhir dan pencatatan keputusan untuk aplikasi pinjaman.
category: Pengurus Koperasi · Tata Kelola
module: loans
roles:
  - pengurus_koperasi
permissions:
  - approve_cooperative_loan
permission_mode: all
route_names:
  - cooperative.loans.index
  - cooperative.loans.show
  - cooperative.loans.approve
  - cooperative.loans.disburse
  - cooperative.loans.reject
risk_level: high
screenshot_entries:
  - pengurus-loan-approval-desktop
related_articles:
  - manajer-loan-review
  - pengurus-shu-and-governance
last_reviewed_commit: b20cd587
status: published
sort_order: 10
---

# Persetujuan Akhir Pinjaman oleh Pengurus

## Tujuan

Memandu Pengurus Koperasi dalam mengambil keputusan akhir atas
aplikasi pinjaman yang sudah direview Manajer, sehingga
pencairan hanya terjadi setelah persetujuan tertinggi di
lingkup koperasi.

## Kapan digunakan

- Ada aplikasi pinjaman dengan status **Direview Manajer**
  yang menunggu keputusan akhir.
- Ingin menolak aplikasi pada tahap akhir.
- Ingin menambahkan catatan keputusan pada aplikasi.

## Prasyarat

- Sudah login sebagai Pengurus Koperasi.
- Memiliki hak akses untuk menyetujui pinjaman.
- Aplikasi sudah berstatus **Direview Manajer**.

## Langkah penggunaan

1. Buka menu **Pinjaman** dan gunakan filter status
   **Direview Manajer**.
2. Buka detail aplikasi untuk membaca data pemohon dan
   ringkasan tinjauan Manajer Koperasi pada riwayat
   keputusan.
3. Pilih keputusan yang tersedia pada detail aplikasi:
   - **Setujui sebagai Pengurus** untuk menyetujui aplikasi.
     Status aplikasi berubah menjadi **Disetujui**. Pencairan
     belum terjadi sampai peran berwenang menjalankan
     tindakan **Cairkan Pinjaman**.
   - **Tolak** untuk menolak aplikasi pada tahap akhir. Status
     aplikasi berubah menjadi **Ditolak** dengan alasan
     penolakan yang Anda isi.
4. Catatan keputusan yang Anda isi tersimpan pada riwayat
   keputusan aplikasi dan dapat dilihat oleh peran
   koperasi lainnya pada halaman detail pinjaman.

## Hasil yang diharapkan

- Aplikasi yang disetujui melewati tahap akhir dengan status
  **Disetujui** dan menunggu pencairan.
- Aplikasi yang ditolak memiliki alasan penolakan yang
  tercatat pada riwayat keputusan.
- Setelah pencairan dijalankan, status aplikasi berubah
  menjadi **Aktif** dan jadwal angsuran tersedia pada detail
  pinjaman.

## Status yang mungkin muncul

- **Direview Manajer**: aplikasi menunggu keputusan akhir
  Pengurus.
- **Disetujui**: aplikasi disetujui final, menunggu pencairan.
- **Aktif**: aplikasi sudah dicairkan, angsuran berjalan.
- **Ditolak**: aplikasi ditolak pada tahap akhir.

## Kondisi gagal

- Manajer belum menyelesaikan tinjauan → minta Manajer
  menyelesaikan tinjauan terlebih dahulu sampai status
  menjadi **Direview Manajer**.
- Tombol **Setujui sebagai Pengurus** tidak muncul → aplikasi
  belum berstatus **Direview Manajer**, atau Anda belum login
  sebagai Pengurus Koperasi.

## Hal yang tidak boleh dilakukan

- Menyetujui aplikasi yang belum berstatus **Direview
  Manajer**.
- Menolak tanpa alasan yang terdokumentasi pada kolom
  penolakan.

## Handoff

- Aplikasi yang disetujui → peran berwenang menjalankan
  **Cairkan Pinjaman** pada detail aplikasi sampai status
  menjadi **Aktif**.
- Keputusan yang berdampak besar → dicatat pada riwayat
  keputusan aplikasi dan dapat dirujuk oleh Pengurus,
  Manajer, atau Admin Koperasi.

## Prosedur terkait

- **Tinjauan Aplikasi Pinjaman oleh Manajer** untuk proses
  sebelum keputusan akhir.
- **SHU, Tata Kelola, dan Audit Internal** untuk konteks
  Pengurus.
