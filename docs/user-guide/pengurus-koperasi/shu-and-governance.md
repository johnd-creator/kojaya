---
title: SHU, Tata Kelola, dan Audit Internal
slug: pengurus-shu-and-governance
summary: Siklus SHU tahunan dan peran Pengurus dalam tata kelola koperasi.
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
Usaha (SHU) tahunan, serta peran Pengurus dalam tata kelola
koperasi.

## Kapan digunakan

- Akhir tahun buku, menjelang penutupan periode SHU.
- Menyusun laporan keuangan untuk anggota.
- Mengelola distribusi poin anggota setelah SHU ditutup.

## Prasyarat

- Sudah login sebagai Pengurus Koperasi.
- Memiliki hak akses `manage_cooperative_shu` untuk menutup
  periode SHU atau mengelola poin.
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

### Distribusi poin anggota

1. Distribusi poin ke anggota dapat dilakukan melalui menu
   **Poin** setelah periode SHU ditutup.
2. Penambahan poin dilakukan sesuai dengan mekanisme yang
   tersedia di halaman **Poin**.

## Hasil yang diharapkan

- Periode SHU ditutup tepat waktu dan akurat.
- Distribusi poin ke anggota tercatat dengan benar.

## Status yang mungkin muncul

- **Periode SHU terbuka**: masih menerima koreksi data.
- **Periode SHU ditutup**: tidak dapat diubah tanpa revisi.
- **SHU dalam revisi**: periode dibuka kembali untuk
  koreksi.

## Kondisi gagal

- Periode SHU tidak bisa ditutup → selesaikan rekonsiliasi
  data terlebih dahulu.
- Permintaan revisi ditolak → tidak ada hak akses atau
  periode sudah final.

## Hal yang tidak boleh dilakukan

- Menutup periode SHU tanpa rekonsiliasi penuh.
- Mendistribusikan poin sebelum SHU ditutup.

## Handoff

- Penutupan SHU tepat waktu → koordinasikan dengan Admin
  Koperasi untuk rekonsiliasi data.

## Prosedur terkait

- **Persetujuan Akhir Pinjaman oleh Pengurus** untuk keputusan
  tertinggi pada pinjaman.
- **Pemantauan Keuangan Harian** untuk rekonsiliasi oleh
  Manajer.

## Catatan tentang jangkauan fitur

- **Log Audit** (`audit-logs`) saat ini hanya dapat diakses
  oleh peran dengan izin `view_audit_logs` dan belum termasuk
  dalam hak akses standar Pengurus Koperasi. Pengurus dapat
  meminta Admin Koperasi atau System Admin jika memerlukan
  penelusuran jejak perubahan.
- **Halaman pengecualian** (`exceptions.index`) membutuhkan
  izin `view_balance_sheet` dan saat ini bukan halaman yang
  dapat dibuka Pengurus Koperasi secara langsung.
- **Perubahan Anggaran Dasar / Anggaran Rumah Tangga
  (AD/ART)** belum tersedia sebagai workflow terstruktur di
  Kojaya. Pencatatan dan persetujuan AD/ART dilakukan di luar
  aplikasi sampai modul tersebut disediakan.
- **Pencatatan risalah RAT** belum tersedia sebagai workflow
  terstruktur di Kojaya. Hasil RAT saat ini disimpan di luar
  aplikasi (misalnya pada sistem arsip internal koperasi) dan
  belum dirujuk otomatis oleh aplikasi.
