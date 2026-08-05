---
title: SHU, Tata Kelola, dan Audit Internal
slug: pengurus-shu-and-governance
summary: Siklus SHU tahunan, audit internal, dan perubahan anggaran dasar.
category: Pengurus Koperasi · Tata Kelola
module: governance
roles:
  - pengurus_koperasi
permissions:
  - manage_cooperative_shu
permission_mode: all
route_names:
  - cooperative.shu.index
  - cooperative.shu.close
  - cooperative.shu.request-revision
  - cooperative.points.index
  - cooperative.reports.index
  - audit-logs
  - exceptions.index
risk_level: high
screenshot_entries:
  - pengurus-shu-and-governance-desktop
related_articles:
  - pengurus-loan-approval
last_reviewed_commit: 20c86960
status: published
sort_order: 20
---

# SHU, Tata Kelola, dan Audit Internal

## Tujuan

Memandu Pengurus Koperasi dalam mengelola siklus Sisa Hasil
Usaha (SHU) tahunan, melakukan audit internal, dan mengelola
perubahan anggaran dasar / anggaran rumah tangga (AD/ART)
koperasi.

## Kapan digunakan

- Akhir tahun buku, menjelang penutupan periode SHU.
- Menyusun laporan triwulan untuk anggota.
- Menangani permintaan audit internal atau eksternal.
- Memutuskan perubahan AD/ART.

## Prasyarat

- Sudah login sebagai Pengurus Koperasi.
- Memiliki hak akses untuk menutup periode SHU, mengelola
  poin, atau mengubah AD/ART.
- Data pembukuan tahunan sudah lengkap dan direkonsiliasi.

## Langkah penggunaan

### SHU tahunan

1. Buka menu **SHU** untuk melihat daftar periode SHU.
2. Periksa status setiap periode. Periode yang masih terbuka
   dapat menerima koreksi data.
3. Setelah seluruh data valid, tutup periode SHU dengan
   menekan **Tutup Periode**. Status berubah menjadi
   **Ditutup** dan tidak dapat diubah lagi.
4. Jika ditemukan ketidaksesuaian setelah periode ditutup,
   gunakan **Minta Revisi** untuk membuka kembali periode
   dengan catatan alasan revisi.
5. Distribusi poin ke anggota dapat dilakukan melalui menu
   **Poin** setelah periode SHU ditutup.

### Audit internal

1. Buka **Log Audit** untuk melihat jejak perubahan data
   penting, termasuk perubahan pinjaman, kas, dan keanggotaan.
2. Untuk mengawasi anomali lintas modul, buka **Pengecualian**
   untuk melihat peringatan otomatis.
3. Perubahan AD/ART dilakukan melalui modul **Pengaturan
   Koperasi** dengan hak akses khusus. Pastikan keputusan
   sudah disetujui dalam rapat pleno dan didokumentasikan.

## Hasil yang diharapkan

- Periode SHU ditutup tepat waktu dan akurat.
- Distribusi poin ke anggota tercatat dengan benar.
- Audit internal berjalan dengan jejak yang utuh.
- Perubahan AD/ART terdokumentasi dengan baik.

## Status yang mungkin muncul

- **Periode SHU terbuka**: masih menerima koreksi data.
- **Periode SHU ditutup**: tidak dapat diubah tanpa revisi.
- **SHU dalam revisi**: periode dibuka kembali untuk
  koreksi.
- **Audit log aktif**: pencatatan audit berjalan normal.

## Kondisi gagal

- Periode SHU tidak bisa ditutup → selesaikan rekonsiliasi
  data terlebih dahulu.
- Permintaan revisi ditolak → tidak ada hak akses atau
  periode sudah final.
- Log audit tidak menampilkan transaksi → hubungi Admin
  Koperasi untuk pengecekan teknis.

## Hal yang tidak boleh dilakukan

- Menutup periode SHU tanpa rekonsiliasi penuh.
- Mengubah AD/ART tanpa keputusan rapat pleno.
- Menghapus atau mengubah log audit.
- Mendistribusikan poin sebelum SHU ditutup.

## Handoff

- Penutupan SHU tepat waktu → koordinasikan dengan Admin
  Koperasi untuk rekonsiliasi data.
- Temuan audit → laporkan ke seluruh Pengurus dan, jika
  diminta, ke auditor eksternal.
- Perubahan AD/ART → sosialisasikan ke anggota melalui
  kanal komunikasi koperasi.

## Prosedur terkait

- **Persetujuan Akhir Pinjaman oleh Pengurus** untuk keputusan
  tertinggi pada pinjaman.
- **Pemantauan Keuangan Harian** untuk rekonsiliasi oleh
  Manajer.

## Catatan tentang RAT

Pencatatan risalah RAT belum tersedia sebagai workflow
terstruktur di Kojaya. Hasil RAT saat ini disimpan di luar
aplikasi (misalnya pada sistem arsip internal koperasi) dan
dirujuk secara manual. Pengurus dapat menggunakan modul
**Log Audit** untuk menelusuri keputusan yang diambil
pada periode terkait.
