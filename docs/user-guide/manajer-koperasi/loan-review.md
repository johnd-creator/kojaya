---
title: Tinjauan Aplikasi Pinjaman oleh Manajer
slug: manajer-loan-review
summary: Proses tinjauan aplikasi pinjaman, keputusan setujui atau tolak, dan jalur eskalasi ke Pengurus.
category: Manajer Koperasi · Pinjaman
module: loans
roles:
  - manajer_koperasi
permissions:
  - review_cooperative_loan
permission_mode: all
route_names:
  - cooperative.loans.index
  - cooperative.loans.show
  - cooperative.loans.review
  - cooperative.loans.approve
  - cooperative.loans.reject
  - cooperative.ledger.index
risk_level: medium
screenshot_entries:
  - manajer-loan-review-desktop
related_articles:
  - manajer-financial-monitoring
  - pengurus-loan-approval
last_reviewed_commit: 20c86960
status: published
sort_order: 10
---

# Tinjauan Aplikasi Pinjaman oleh Manajer

## Tujuan

Memandu Manajer Koperasi dalam meninjau aplikasi pinjaman
anggota yang masuk, memutuskan apakah aplikasi dapat diteruskan
ke Pengurus atau ditolak, dan menurunkan risiko portofolio
koperasi.

## Kapan digunakan

- Ada aplikasi pinjaman anggota yang menunggu tinjauan.
- Ingin menelaah profil pemohon sebelum memberi keputusan.
- Ingin menolak aplikasi yang tidak layak.

## Prasyarat

- Sudah login sebagai Manajer Koperasi.
- Memiliki hak akses untuk meninjau pinjaman.
- Pemeriksaan saldo simpanan anggota sudah dilakukan
  menggunakan pembukuan terkini.

## Langkah penggunaan

1. Buka menu **Pinjaman** dan pilih daftar pinjaman. Sistem
   menampilkan semua aplikasi.
2. Saring daftar dengan filter **Diajukan** untuk melihat
   aplikasi yang menunggu tinjauan Manajer.
3. Buka detail aplikasi untuk membaca data pemohon.
4. Telaah aspek-aspek berikut sebelum memutuskan:
   - **Kelayakan pribadi**: profil pekerjaan, lama keanggotaan,
     dan konsistensi data.
   - **Kemampuan bayar**: besaran simpanan, gaji atau
     penghasilan, dan beban angsuran berjalan.
   - **Agunan**: ada atau tidaknya jaminan sesuai kebijakan
     koperasi.
   - **Tujuan pinjaman**: kesesuaian dengan jenis pinjaman
     yang diajukan.
   - **Kondisi pasar**: tren angsuran macet di periode
     terakhir.
5. Setelah telaah, pilih keputusan:
   - **Teruskan ke Pengurus** jika aplikasi dianggap layak.
     Status aplikasi berubah menjadi **Disetujui Manajer** dan
     menunggu keputusan akhir Pengurus Koperasi.
   - **Tolak** jika aplikasi tidak memenuhi syarat. Status
     aplikasi berubah menjadi **Ditolak** dan anggota menerima
     notifikasi.
6. Untuk pinjaman yang data-datanya belum lengkap, minta
   Admin Koperasi melengkapi dokumen sebelum ditinjau ulang.

## Hasil yang diharapkan

- Aplikasi yang layak diteruskan ke Pengurus Koperasi.
- Aplikasi yang tidak layak ditolak dengan alasan yang jelas.
- Tidak ada aplikasi yang menggantung tanpa keputusan.

## Status yang mungkin muncul

- **Diajukan**: aplikasi baru, menunggu tinjauan Manajer.
- **Disetujui Manajer**: sudah disetujui Manajer, menunggu
  keputusan Pengurus.
- **Ditolak**: ditolak pada tahap tinjauan Manajer.
- **Data belum lengkap**: aplikasi menunggu dokumen tambahan
  dari Admin Koperasi.

## Kondisi gagal

- Data pekerjaan tidak tersedia → minta Admin Koperasi
  melengkapi.
- Agunan tidak sesuai kebijakan → arahkan ke Manajer lain
  untuk tinjauan kedua atau tolak.
- Pemohon memiliki pinjaman macet aktif → tolak dengan
  alasan.

## Hal yang tidak boleh dilakukan

- Menyetujui aplikasi tanpa telaah yang memadai.
- Menolak tanpa alasan yang terdokumentasi.
- Memproses aplikasi di luar wewenang.
- Mengubah data simpanan anggota untuk memuluskan aplikasi.

## Handoff

- Aplikasi yang sudah disetujui Manajer → teruskan ke
  **Pengurus Koperasi** untuk keputusan akhir.
- Aplikasi dengan data belum lengkap → koordinasikan dengan
  Admin Koperasi.
- Temuan risiko yang bersifat lintas anggota → laporkan ke
  Pengurus Koperasi.

## Prosedur terkait

- **Mengajukan dan Melacak Pinjaman** untuk konteks
  anggota.
- **Persetujuan Akhir Pinjaman oleh Pengurus** untuk tahap
  keputusan akhir.
- **Pemantauan Keuangan Harian** untuk rekonsiliasi simpanan.
