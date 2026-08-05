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
- Ingin menolak aplikasi yang tidak layak.

## Prasyarat

- Sudah login sebagai Manajer Koperasi.
- Memiliki hak akses untuk meninjau pinjaman.
- Aplikasi sudah berstatus **APPLIED** (diajukan).

## Langkah penggunaan

1. Buka menu **Pinjaman** dan pilih daftar pinjaman.
2. Gunakan filter status untuk melihat aplikasi yang menunggu
   tinjauan Manajer (status `APPLIED`).
3. Buka detail aplikasi untuk membaca data yang tersedia pada
   halaman detail:
   - identitas anggota (nama, nomor anggota),
   - jenis pinjaman,
   - nilai pokok, bunga, tenor, angsuran, total,
   - sisa outstanding,
   - tujuan pinjaman,
   - catatan dari pengajuan,
   - jadwal angsuran,
   - daftar pembayaran yang tercatat,
   - riwayat keputusan (jika ada).
4. Telaah data tersebut sebelum memberi keputusan.
5. Pilih keputusan yang tersedia pada detail aplikasi:
   - **Catat review Manajer** untuk meneruskan aplikasi ke
     tahap berikutnya. Status aplikasi berubah menjadi
     `MANAGER_APPROVED` dan keputusan tercatat pada log
     keputusan aplikasi dengan catatan yang Anda isi.
   - **Tolak** jika aplikasi tidak memenuhi syarat. Status
     aplikasi berubah menjadi `REJECTED` dengan alasan
     penolakan yang harus Anda isi.

## Hasil yang diharapkan

- Aplikasi yang layak diteruskan ke Pengurus Koperasi.
- Aplikasi yang tidak layak ditolak dengan alasan yang jelas.
- Tidak ada aplikasi yang menggantung tanpa keputusan.

## Status yang mungkin muncul

Label di bawah adalah nilai yang ditampilkan aplikasi untuk
status pinjaman:

- **APPLIED**: aplikasi baru, menunggu tinjauan Manajer.
- **MANAGER_APPROVED**: sudah direview Manajer, menunggu
  keputusan Pengurus.
- **REJECTED**: ditolak pada tahap tinjauan.

## Kondisi gagal

- Angsuran tidak bisa dibayar oleh anggota → minta anggota
  untuk menyelesaikan tunggakan atau hubungi Admin Koperasi
  untuk penjadwalan ulang.
- Catatan review tidak tersimpan → coba ulangi; jika tetap
  gagal, hubungi Admin Koperasi untuk pengecekan teknis.

## Hal yang tidak boleh dilakukan

- Menyetujui aplikasi tanpa telaah catatan dan data pada
  detail aplikasi.
- Menolak tanpa alasan yang terdokumentasi pada kolom
  penolakan.
- Memproses aplikasi di luar wewenang Manajer Koperasi.

## Handoff

- Aplikasi yang sudah disetujui Manajer → status menjadi
  `MANAGER_APPROVED` dan menunggu keputusan akhir **Pengurus
  Koperasi**.
- Temuan risiko yang bersifat lintas anggota → laporkan ke
  Pengurus Koperasi.

## Prosedur terkait

- **Mengajukan dan Melacak Pinjaman** untuk konteks anggota.
- **Persetujuan Akhir Pinjaman oleh Pengurus** untuk tahap
  keputusan akhir.
- **Pemantauan Keuangan Harian** untuk rekonsiliasi simpanan.
