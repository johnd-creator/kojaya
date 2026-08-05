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
7. Untuk membayar angsuran, gunakan metode pembayaran yang
   tersedia pada detail pinjaman. Metode yang ditampilkan
   mengikuti pilihan yang aktif di aplikasi (misalnya tunai,
   transfer bank, atau QRIS).
8. Pantau status pembayaran angsuran sampai aplikasi berubah ke
   status yang menandakan pinjaman selesai.

## Hasil yang diharapkan

- Aplikasi pinjaman tampil di daftar dengan status yang
  menandakan pengajuan sudah masuk.
- Status berganti ke salah satu tahap tinjauan sesuai proses
  koperasi.
- Setelah pencairan, angsuran tampil di jadwal angsuran dan
  dapat dibayar tepat waktu.

## Status yang mungkin muncul

Label status di bawah mengikuti nilai yang ditampilkan aplikasi.
Saat ini aplikasi menampilkan status dengan label dalam Bahasa
Indonesia yang setara dengan kode status internal:

- **APPLIED** (Diajukan): aplikasi baru, menunggu tinjauan
  Manajer Koperasi.
- **MANAGER_APPROVED** (Direview Manajer): sudah disetujui
  Manajer, menunggu keputusan Pengurus Koperasi.
- **APPROVED** (Disetujui): disetujui final, menunggu
  pencairan.
- **ACTIVE** (Aktif): sudah dicairkan, angsuran berjalan.
- **PAID_OFF** (Lunas): seluruh angsuran sudah dibayar.
- **DEFAULTED** (Macet): terdapat angsuran yang terlambat.
- **REJECTED** (Ditolak): ditolak pada salah satu tahap tinjauan.
- **WRITTEN_OFF** (Dihapusbukukan): pinjaman dihapus dari
  pembukuan karena kondisi tertentu.

## Kondisi gagal

- Plafon tidak cukup → kecilkan nominal atau perpanjang tenor.
- Angsuran tidak bisa dibayar → pastikan metode pembayaran
  aktif dan nominal sesuai jadwal.
- Status tidak berubah dalam beberapa waktu → hubungi Admin
  Koperasi dengan menyertakan nomor aplikasi pinjaman.

## Hal yang tidak boleh dilakukan

- Memberikan data palsu pada formulir pengajuan.
- Memalsukan dokumen pendukung pengajuan.
- Mengubah jadwal angsuran sendiri tanpa persetujuan koperasi.

## Handoff

- Peninjauan aplikasi pinjaman dilakukan **Manajer Koperasi**
  terlebih dahulu, lalu **Pengurus Koperasi** untuk keputusan
  akhir.
- Pencairan dilakukan oleh peran dengan hak akses pencairan
  setelah semua persetujuan terpenuhi.
- Pertanyaan seputar status atau penolakan → hubungi Admin
  Koperasi dengan menyertakan nomor aplikasi pinjaman.

## Prosedur terkait

- **Mengenal Portal Anggota** untuk menemukan menu pinjaman.
- **Tinjauan Aplikasi Pinjaman oleh Manajer** (panduan Manajer)
  menjelaskan bagaimana aplikasi ditinjau.
- **Persetujuan Akhir Pinjaman oleh Pengurus** (panduan Pengurus)
  menjelaskan tahap keputusan akhir.
