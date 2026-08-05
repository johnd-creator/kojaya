---
title: Mengajukan dan Melacak Pinjaman
slug: anggota-loan-flow
summary: Cara mengajukan pinjaman baru dan membaca status aplikasi hingga pencairan.
category: Anggota · Pinjaman
module: loans
roles:
  - anggota
permissions: []
permission_mode: all
route_names:
  - member.loans
  - member.loans.store
  - member.loans.installments.payment-intent
  - member.loans.payment-intents.status
risk_level: medium
screenshot_entries:
  - anggota-loan-flow-desktop
  - anggota-loan-flow-mobile
related_articles:
  - anggota-portal-overview
last_reviewed_commit: 20c86960
status: published
sort_order: 30
---

# Mengajukan dan Melacak Pinjaman

## Tujuan

Membantu anggota mengajukan pinjaman baru dan memantau setiap
tahapan sampai pencairan, tanpa harus datang ke kantor koperasi.

## Kapan digunakan

- Ingin mengajukan pinjaman untuk kebutuhan konsumtif, produktif,
  atau darurat.
- Ingin melihat status aplikasi pinjaman yang sedang berjalan.
- Ingin membayar angsuran yang sudah jatuh tempo.

## Prasyarat

- Sudah login sebagai anggota.
- Status keanggotaan aktif.
- Tidak memiliki pinjaman macet yang belum diselesaikan.
- Data profil dan pekerjaan sudah lengkap.

## Langkah penggunaan

1. Buka menu **Pinjaman** pada portal anggota.
2. Pilih tab **Simulasi & Ajukan**.
3. Isi jenis pinjaman, jumlah yang diinginkan, tenor (lama
   pinjaman), tanggal angsuran pertama, tujuan pinjaman, dan
   catatan tambahan bila ada.
4. Tekan **Kirim Pengajuan**.
5. Setelah pengajuan terkirim, buka tab **Daftar Pinjaman &
   Riwayat** untuk memantau status.
6. Setelah pinjaman disetujui dan dicairkan, buka detail pinjaman
   untuk membayar angsuran.
7. Untuk membayar angsuran, tekan **Bayar Angsuran** pada detail
   pinjaman, lalu selesaikan pembayaran di halaman bank virtual
   yang muncul.
8. Pantau status pembayaran angsuran sampai berubah menjadi
   **Lunas**.

## Hasil yang diharapkan

- Aplikasi pinjaman tampil di daftar dengan status **Diajukan**.
- Status berganti ke **Ditolak**, **Disetujui Manajer**, atau
  **Disetujui Pengurus** sesuai proses tinjauan.
- Setelah pencairan, angsuran tampil di jadwal angsuran dan
  dapat dibayar tepat waktu.

## Status yang mungkin muncul

- **Diajukan**: aplikasi baru, menunggu tinjauan Manajer Koperasi.
- **Disetujui Manajer**: sudah disetujui Manajer, menunggu
  keputusan Pengurus Koperasi.
- **Disetujui**: disetujui final, sedang dijadwalkan pencairan.
- **Ditolak**: ditolak pada salah satu tahap tinjauan.
- **Aktif**: sudah dicairkan, angsuran berjalan.
- **Lunas**: seluruh angsuran sudah dibayar.
- **Macet**: terdapat angsuran yang terlambat bayar.
- **Dihapusbukukan**: pinjaman dihapus dari pembukuan karena
  kondisi tertentu.

## Kondisi gagal

- Plafon tidak cukup → kecilkan nominal atau perpanjang tenor.
- Data pekerjaan tidak lengkap → lengkapi dulu di menu **Profil
  Saya**.
- Angsuran tidak bisa dibayar → pastikan metode pembayaran aktif
  dan nominal sesuai jadwal.
- Status tidak berubah setelah beberapa hari kerja → hubungi
  Admin Koperasi.

## Hal yang tidak boleh dilakukan

- Mengajukan lebih dari satu pinjaman dalam waktu bersamaan
  tanpa konfirmasi.
- Memberikan data pekerjaan atau penghasilan palsu.
- Memalsukan dokumen pendukung pengajuan.
- Mengubah jadwal angsuran sendiri tanpa persetujuan.

## Handoff

- Peninjauan aplikasi pinjaman dilakukan **Manajer Koperasi**
  terlebih dahulu, lalu **Pengurus Koperasi** untuk keputusan
  akhir.
- Pencairan dilakukan oleh Admin Koperasi setelah semua persetujuan
  terpenuhi.
- Pertanyaan seputar status atau penolakan → hubungi Admin
  Koperasi dengan menyertakan nomor aplikasi pinjaman.

## Prosedur terkait

- **Mengenal Portal Anggota** untuk menemukan menu pinjaman.
- **Tinjauan Aplikasi Pinjaman oleh Manajer** (panduan Manajer)
  menjelaskan bagaimana aplikasi ditinjau.
- **Persetujuan Akhir Pinjaman oleh Pengurus** (panduan Pengurus)
  menjelaskan tahap keputusan akhir.
