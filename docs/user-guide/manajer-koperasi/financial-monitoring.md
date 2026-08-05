---
title: Pemantauan Keuangan Harian
slug: manajer-financial-monitoring
summary: Cara membaca ringkasan simpan pinjam, pinjaman macet, dan status pencairan.
category: Manajer Koperasi · Keuangan
module: reports
roles:
  - manajer_koperasi
permissions:
  - view_cooperative_report
permission_mode: all
route_names:
  - cooperative.operator.dashboard
  - cooperative.payments.index
  - cooperative.pos.closings.index
  - cooperative.ledger.index
  - cooperative.loans.index
  - cooperative.loans.disburse
  - cooperative.reports.index
risk_level: low
screenshot_entries:
  - manajer-financial-monitoring-desktop
related_articles:
  - manajer-loan-review
last_reviewed_commit: 20c86960
status: published
sort_order: 20
---

# Pemantauan Keuangan Harian

## Tujuan

Memberikan Manajer Koperasi cara cepat untuk membaca kondisi
keuangan harian koperasi, menemukan anomali, dan memicu
tindakan korektif sebelum masalah membesar.

## Kapan digunakan

- Awal hari kerja untuk melihat posisi kas dan pinjaman
  kemarin.
- Menjelang rapat internal untuk menyiapkan angka ringkas.
- Saat menerima laporan anomali dari Admin Koperasi.

## Prasyarat

- Sudah login sebagai Manajer Koperasi.
- Memiliki hak akses untuk membaca laporan keuangan.

## Langkah penggunaan

1. Buka **Dashboard Operasional**. Widget Manajer menampilkan
   ringkasan harian.
2. Periksa indikator **Pinjaman Macet** pada pojok kanan
   atas. Jika angkanya naik, buka daftar pinjaman dengan
   filter **Macet** untuk melihat anggota yang menunggak.
3. Periksa **Angsuran Tertunda**. Jika ada, tugaskan Admin
   Koperasi untuk menindaklanjuti melalui menu **Pembayaran**.
4. Cocokan setoran kasir kemarin pada menu **Penyetoran
   Kasir** dengan pembukuan pada menu **Buku Besar**.
5. Periksa pinjaman yang sudah disetujui Pengurus namun
   belum dicairkan. Hubungi Admin Koperasi untuk menanyakan
   status transfer.
6. Permasalahan hukum (misalnya sengketa atau audit eksternal)
   dicatat dan diteruskan ke Pengurus Koperasi melalui
   laporan triwulan.

## Hasil yang diharapkan

- Manajer memahami posisi kas, piutang, dan pencairan setiap
  pagi.
- Anomali ditemukan lebih awal dan ditindaklanjuti pada hari
  yang sama.
- Rekonsiliasi kas dan pembukuan selesai sebelum tutup buku
  harian.

## Status yang mungkin muncul

- **Pinjaman macet naik**: indikator perubahan mingguan.
- **Angsuran tertunda**: daftar angsuran yang belum dibayar
  setelah jatuh tempo.
- **Pinjaman belum dicairkan**: daftar pinjaman dengan
  keputusan **Disetujui** namun belum ada pencatatan
  pencairan.
- **Selisih setoran**: perbedaan antara setoran kasir dan
  pembukuan.

## Kondisi gagal

- Buku besar tidak bisa dibuka → hubungi Admin Koperasi
  untuk sinkronisasi.
- Pencairan tidak bergerak lebih dari 3 hari → hubungi
  Admin Koperasi untuk eskalasi.
- Pinjaman macet tidak turun → susun rencana tindak lanjut
  di rapat internal.

## Hal yang tidak boleh dilakukan

- Mengubah catatan pembukuan tanpa otorisasi.
- Menutup hari dengan selisih yang belum jelas penyebabnya.
- Memindahkan tanggung jawab tindak lanjut ke peran di bawah
  tanpa instruksi tertulis.

## Handoff

- Anomali yang berulang → laporkan ke Pengurus Koperasi.
- Tindak lanjut yang membutuhkan kebijakan → usulkan ke
  Pengurus melalui rapat triwulan.
- Permintaan data dari auditor → koordinasikan dengan
  Pengurus Koperasi.

## Prosedur terkait

- **Tinjauan Aplikasi Pinjaman oleh Manajer** untuk proses
  aplikasi.
- **SHU, Tata Kelola, dan Audit Internal** untuk konteks
  Pengurus.
