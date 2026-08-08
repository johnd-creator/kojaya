---
title: Mengelola Saldo Awal Anggota
slug: admin-koperasi-opening-balance
summary: Cara menyiapkan, memeriksa, dan mengirim saldo awal anggota untuk proses posting.
category: Admin Koperasi · Keuangan
module: savings
roles:
  - admin_koperasi
permissions:
  - manage_cooperative_opening_balance
permission_mode: all
route_names:
  - cooperative.members.opening-balance.show
  - cooperative.members.opening-balance.preview
  - cooperative.members.opening-balance.store
  - cooperative.opening-balances.post
  - cooperative.opening-balances.void
risk_level: high
screenshot_entries: []
related_articles:
  - admin-koperasi-member-validation
  - admin-koperasi-operational-dashboard
last_reviewed_commit: 999684c5f72029bd52ea8dced11203cac344c2d1
status: published
sort_order: 25
---

# Mengelola Saldo Awal Anggota

## Tujuan

Membantu Admin Koperasi mencatat saldo awal anggota secara terstruktur
sebelum saldo tersebut diproses ke pembukuan.

## Kapan digunakan

- Data anggota memerlukan pencatatan saldo awal.
- Saldo awal perlu diperiksa sebelum dikirim untuk diposting.
- Batch saldo awal perlu ditindaklanjuti berdasarkan statusnya.

## Prasyarat

- Anggota sudah tersedia pada modul Keanggotaan.
- Sudah login dengan akses pengelolaan saldo awal.
- Siapkan rincian saldo dan sumber data yang akan dicatat.

## Langkah penggunaan

1. Buka detail anggota dari **Data Anggota**.
2. Pilih **Saldo Awal** untuk membuka wizard saldo awal.
3. Masukkan rincian saldo sesuai kategori dan sumber yang tersedia.
4. Gunakan **Preview** untuk memeriksa hasil sebelum menyimpan.
5. Simpan sebagai batch saldo awal dan periksa status batch pada daftar.
6. Jika batch siap diproses, teruskan kepada role yang memiliki
   kewenangan posting atau approval sesuai kebijakan koperasi.

## Hasil yang diharapkan

- Rincian saldo awal tersimpan dalam batch yang dapat ditelusuri.
- Hasil preview sesuai dengan data yang akan diposting.
- Batch memiliki status yang jelas, seperti **Draft**, **Posted**, atau
  **Void**.

## Kondisi gagal

- Anggota sudah berstatus mengundurkan diri → periksa status anggota
  sebelum membuat batch.
- Preview tidak sesuai → jangan kirim batch; periksa rincian sumber dan
  kategori saldo.
- Aksi posting atau pembatalan tidak tersedia → aksi tersebut memerlukan
  kewenangan role lain.

## Hal yang tidak boleh dilakukan

- Memposting batch tanpa memeriksa preview.
- Menggunakan saldo awal untuk memperbaiki transaksi berjalan tanpa
  prosedur koreksi yang sesuai.
- Menganggap batch **Draft** sudah masuk pembukuan.

## Handoff

- Batch siap posting → role dengan kewenangan approval saldo awal.
- Koreksi atau pembatalan batch → Pengurus Koperasi atau role yang
  memiliki kewenangan tersebut.

## Prosedur terkait

- **Mengelola dan Memvalidasi Data Anggota** untuk membuka detail anggota.
- **Dashboard Operasional Admin Koperasi** untuk peta menu operasional.
