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
last_reviewed_commit: b20cd587
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
- Ingin melihat pratinjau alokasi SHU sebelum finalisasi.
- Ingin memantau laporan keuangan koperasi.

## Prasyarat

- Sudah login sebagai Pengurus Koperasi.
- Memiliki hak akses untuk mengelola SHU.
- Data pembukuan tahunan sudah lengkap dan direkonsiliasi.

## Langkah penggunaan

### Pratinjau dan tutup periode SHU

1. Buka menu **SHU** untuk melihat halaman periode SHU.
2. Masukkan nilai **Pool SHU Koperasi** dan **Pool Laba POS**
   sesuai data pembukuan tahun berjalan.
3. Tekan **Preview** untuk melihat pratinjau alokasi SHU per
   anggota. Pratinjau menampilkan bulan aktif keanggotaan,
   iuran wajib yang lunas, skor, dan estimasi alokasi.
4. Periksa pratinjau. Jika data sudah valid, tekan **Tutup**
   untuk menutup periode. Status berubah menjadi **Tertutup**
   dan alokasi final tersimpan.
5. Setelah periode ditutup, data alokasi tidak dapat diubah
   melalui halaman SHU.

### Laporan keuangan

1. Buka menu **Laporan** untuk melihat ringkasan keuangan
   koperasi.
2. Gunakan menu **Poin** untuk melihat data poin anggota.

## Hasil yang diharapkan

- Pratinjau alokasi SHU tersedia sebelum finalisasi.
- Periode SHU ditutup dengan alokasi final yang tersimpan.
- Data poin anggota dapat dipantau.

## Status yang mungkin muncul

- **Preview**: periode SHU belum difinalisasi, masih dapat
  disesuaikan.
- **Tertutup**: periode SHU sudah difinalisasi, alokasi
  tersimpan dan tidak dapat diubah melalui halaman SHU.

## Kondisi gagal

- Tombol **Tutup** tidak aktif → pastikan nilai pool sudah
  diisi dan pratinjau sudah dimuat.
- Penutupan gagal → pastikan periode belum ditutup sebelumnya
  dan data pembukuan sudah lengkap.

## Hal yang tidak boleh dilakukan

- Menutup periode SHU tanpa memeriksa pratinjau alokasi.
- Menutup periode sebelum seluruh iuran wajib tahun berjalan
  direkonsiliasi.

## Handoff

- Penutupan SHU tepat waktu → koordinasikan dengan Admin
  Koperasi untuk rekonsiliasi data.

## Prosedur terkait

- **Persetujuan Akhir Pinjaman oleh Pengurus** untuk keputusan
  tertinggi pada pinjaman.
- **Pemantauan Keuangan Harian** untuk rekonsiliasi oleh
  Manajer.

## Catatan tentang jangkauan fitur

- **Log Audit** belum termasuk dalam hak akses standar Pengurus
  Koperasi. Pengurus dapat meminta Admin Koperasi atau System
  Admin jika memerlukan penelusuran jejak perubahan.
- **Halaman pengecualian** belum dapat dibuka Pengurus Koperasi
  secara langsung.
- **Perubahan Anggaran Dasar / Anggaran Rumah Tangga
  (AD/ART)** belum tersedia sebagai workflow terstruktur di
  Kojaya.
- **Pencatatan risalah RAT** belum tersedia sebagai workflow
  terstruktur di Kojaya.
