---
title: Memantau Cockpit Operasional Koperasi
slug: manajer-operator-cockpit
summary: Cara menggunakan ringkasan operasional, inbox persetujuan, pengecualian, dan ekspor untuk pemantauan Manajer.
category: Manajer Koperasi · Operasional
module: reports
roles:
  - manajer_koperasi
permissions:
  - view_cooperative_report
permission_mode: all
route_names:
  - cooperative.operator.dashboard
  - cooperative.operator.approval-inbox
  - cooperative.operator.exceptions
  - cooperative.operator.analytics
  - cooperative.operator.export
risk_level: high
screenshot_entries: []
related_articles:
  - manajer-financial-monitoring
  - manajer-loan-review
  - cooperative-dues-and-ledger
last_reviewed_commit: 999684c5f72029bd52ea8dced11203cac344c2d1
status: published
sort_order: 15
---

# Memantau Cockpit Operasional Koperasi

## Tujuan

Memberikan alur ringkas bagi Manajer Koperasi untuk memantau pekerjaan
operasional dan menentukan pengecekan lanjutan.

## Kapan digunakan

- Awal hari atau saat meninjau kondisi operasional.
- Ada pekerjaan yang menunggu perhatian.
- Perlu melihat pengecualian atau mengekspor data untuk pemeriksaan.

## Langkah penggunaan

1. Buka **Dashboard Operator** untuk melihat ringkasan operasional.
2. Periksa **Inbox Persetujuan** untuk pekerjaan yang memerlukan
   tindak lanjut.
3. Buka **Pengecualian** untuk melihat kondisi yang perlu diperiksa lebih
   lanjut.
4. Gunakan **Analitik** untuk membaca ringkasan berdasarkan data yang
   tersedia.
5. Jika diperlukan, gunakan ekspor dengan periode atau tipe data yang
   sesuai untuk pemeriksaan lanjutan.

## Hasil yang diharapkan

- Pekerjaan prioritas dapat ditemukan dari ringkasan operasional.
- Pengecualian memiliki tindak lanjut yang jelas.
- Data ekspor memiliki periode dan tipe yang sesuai dengan pemeriksaan.

## Kondisi gagal

- Ringkasan belum menampilkan data → periksa periode atau tunggu data
  deferred selesai dimuat.
- Pengecualian tidak dapat dijelaskan → buka modul sumber, seperti
  pembayaran, ledger, atau pinjaman.
- Ekspor tidak sesuai → periksa kembali tipe dan periode sebelum meminta
  ekspor ulang.

## Hal yang tidak boleh dilakukan

- Menganggap angka ringkasan sebagai bukti akhir tanpa memeriksa modul
  sumber.
- Mengabaikan pengecualian yang berulang.
- Mengekspor data tanpa tujuan pemeriksaan yang jelas.

## Handoff

- Aplikasi pinjaman → **Tinjauan Aplikasi Pinjaman oleh Manajer**.
- Anomali ledger atau pembayaran → **Mengelola Iuran dan Membaca Ledger
  Simpanan**.
- Keputusan yang memerlukan kewenangan strategis → Pengurus Koperasi.

## Prosedur terkait

- **Pemantauan Keuangan Harian** untuk pemeriksaan keuangan detail.
- **Tinjauan Aplikasi Pinjaman oleh Manajer** untuk inbox pinjaman.
