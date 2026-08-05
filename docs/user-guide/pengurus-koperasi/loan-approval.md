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
last_reviewed_commit: 20c86960
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

- Ada aplikasi pinjaman dengan status `MANAGER_APPROVED` yang
  menunggu keputusan akhir.
- Ingin menolak aplikasi pada tahap akhir.
- Ingin menambahkan catatan keputusan pada aplikasi.

## Prasyarat

- Sudah login sebagai Pengurus Koperasi.
- Memiliki hak akses untuk menyetujui pinjaman.
- Aplikasi sudah berstatus `MANAGER_APPROVED`.

## Langkah penggunaan

1. Buka menu **Pinjaman** dan gunakan filter status
   `MANAGER_APPROVED`.
2. Buka detail aplikasi untuk membaca data pemohon dan
   ringkasan tinjauan Manajer Koperasi pada log keputusan.
3. Pilih keputusan yang tersedia pada detail aplikasi:
   - **Setujui sebagai Pengurus** untuk menyetujui aplikasi.
     Status aplikasi berubah menjadi `APPROVED`. Pencairan
     belum terjadi sampai ada peran dengan hak akses
     pencairan (`manage_cooperative_loan`) menjalankan
     tindakan **Cairkan Pinjaman**.
   - **Tolak** untuk menolak aplikasi pada tahap akhir. Status
     aplikasi berubah menjadi `REJECTED` dengan alasan
     penolakan yang Anda isi.
4. Catatan keputusan yang Anda isi pada kolom catatan akan
   tersimpan dalam log keputusan aplikasi dan dapat dilihat
   oleh anggota serta peran koperasi lainnya.

## Hasil yang diharapkan

- Aplikasi yang disetujui melewati tahap akhir dengan status
  `APPROVED` dan menunggu pencairan.
- Aplikasi yang ditolak memiliki alasan penolakan yang
  tercatat pada log keputusan.
- Setelah pencairan dijalankan, status aplikasi berubah
  menjadi `ACTIVE` dan jadwal angsuran tersedia pada detail
  pinjaman.

## Status yang mungkin muncul

- **MANAGER_APPROVED**: aplikasi menunggu keputusan akhir
  Pengurus.
- **APPROVED**: aplikasi disetujui final, menunggu pencairan
  oleh peran dengan hak akses pencairan.
- **ACTIVE**: aplikasi sudah dicairkan, angsuran berjalan.
- **REJECTED**: aplikasi ditolak pada tahap akhir.

## Kondisi gagal

- Manajer belum menyelesaikan tinjauan → minta Manajer
  menyelesaikan tinjauan terlebih dahulu sampai status
  menjadi `MANAGER_APPROVED`.
- Tombol **Setujui sebagai Pengurus** tidak muncul → aplikasi
  belum berstatus `MANAGER_APPROVED`, atau Anda belum login
  sebagai Pengurus Koperasi dengan izin `approve_cooperative_loan`.

## Hal yang tidak boleh dilakukan

- Menyetujui aplikasi yang belum berstatus `MANAGER_APPROVED`.
- Menolak tanpa alasan yang terdokumentasi pada kolom
  penolakan.

## Handoff

- Aplikasi yang disetujui → peran dengan hak akses pencairan
  (`manage_cooperative_loan`) menjalankan **Cairkan Pinjaman**
  pada detail aplikasi sampai status menjadi `ACTIVE`.
- Keputusan yang berdampak besar → dicatat pada log keputusan
  aplikasi dan dapat dirujuk oleh Pengurus, Manajer, atau
  Admin Koperasi.

## Prosedur terkait

- **Tinjauan Aplikasi Pinjaman oleh Manajer** untuk proses
  sebelum keputusan akhir.
- **SHU, Tata Kelola, dan Audit Internal** untuk konteks
  Pengurus.
