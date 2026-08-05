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
  - audit-logs
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
aplikasi pinjaman yang sudah disetujui Manajer, sehingga
pencairan hanya terjadi setelah persetujuan tertinggi di
lingkup koperasi.

## Kapan digunakan

- Ada aplikasi pinjaman dengan status **Disetujui Manajer**
  yang menunggu keputusan akhir.
- Ingin menolak aplikasi pada tahap akhir.
- Ingin menambahkan catatan keputusan pada aplikasi.

## Prasyarat

- Sudah login sebagai Pengurus Koperasi.
- Memiliki hak akses untuk menyetujui pinjaman.
- Aplikasi sudah pernah ditinjau oleh Manajer Koperasi.

## Langkah penggunaan

1. Buka menu **Pinjaman** dan saring daftar dengan filter
   **Disetujui Manajer**.
2. Buka detail aplikasi untuk membaca data pemohon dan
   ringkasan tinjauan Manajer.
3. Telaah pertimbangan yang sudah diberikan Manajer, termasuk
   data pekerjaan, simpanan, agunan, dan rekomendasi.
4. Pilih keputusan:
   - **Setujui**: status aplikasi berubah menjadi
     **Disetujui** dan sistem menjadwalkan pencairan oleh
     Admin Koperasi. Jadwal angsuran akan ditampilkan pada
     detail pinjaman.
   - **Tolak**: status aplikasi berubah menjadi **Ditolak**
     dan anggota menerima notifikasi.
5. Jika perlu menambahkan catatan keputusan, gunakan kolom
   catatan yang tersedia pada detail aplikasi.

## Hasil yang diharapkan

- Aplikasi yang disetujui melewati tahap akhir dan
  dijadwalkan pencairan.
- Aplikasi yang ditolak memiliki alasan yang tercatat.
- Setiap keputusan tercatat dalam log audit aplikasi.

## Status yang mungkin muncul

- **Disetujui Manajer**: aplikasi menunggu keputusan
  Pengurus.
- **Disetujui**: aplikasi disetujui final, menunggu
  pencairan.
- **Ditolak**: aplikasi ditolak pada tahap akhir.
- **Sedang Dicairkan**: aplikasi disetujui dan Admin Koperasi
  sedang memproses pencairan.

## Kondisi gagal

- Manajer belum menyelesaikan tinjauan → minta Manajer
  menyelesaikan tinjauan terlebih dahulu.
- Agunan tidak sesuai kebijakan → minta tinjauan ulang oleh
  Manajer.
- Data pemohon berubah setelah tinjauan Manajer → koordinasikan
  dengan Admin Koperasi untuk memperbarui data.

## Hal yang tidak boleh dilakukan

- Menyetujui aplikasi yang belum ditinjau Manajer.
- Mengubah data aplikasi setelah disetujui Manajer tanpa
  pemberitahuan.
- Menyetujui tanpa melihat data keuangan terkini.
- Menolak tanpa alasan yang terdokumentasi.

## Handoff

- Aplikasi yang disetujui → Admin Koperasi mencairkan dan
  menjadwalkan angsuran.
- Keputusan yang berdampak besar → laporkan pada RAT
  melalui notulen eksternal.
- Temuan risiko portofolio → masukkan dalam laporan triwulan.

## Prosedur terkait

- **Tinjauan Aplikasi Pinjaman oleh Manajer** untuk proses
  sebelum keputusan akhir.
- **SHU, Tata Kelola, dan Audit Internal** untuk konteks
  Pengurus.
