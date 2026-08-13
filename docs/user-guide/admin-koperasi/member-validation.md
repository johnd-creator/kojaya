---
title: Mengelola dan Memvalidasi Data Anggota
slug: admin-koperasi-member-validation
summary: Cara menemukan data anggota, memverifikasi pendaftaran, dan menindaklanjuti perubahan status keanggotaan.
category: Admin Koperasi · Keanggotaan
module: membership
roles:
  - admin_koperasi
permissions:
  - view_cooperative_member
  - manage_cooperative_member
  - validate_cooperative_member
  - review_cooperative_resignation
permission_mode: any
route_names:
  - cooperative.members.index
  - cooperative.members.create
  - cooperative.members.store
  - cooperative.members.show
  - cooperative.members.edit
  - cooperative.members.update
  - cooperative.members.validate
  - cooperative.members.resignations.index
  - cooperative.members.resignations.process
risk_level: high
screenshot_entries: []
related_articles:
  - admin-koperasi-operational-dashboard
  - anggota-onboarding-and-access
last_reviewed_commit: 999684c5f72029bd52ea8dced11203cac344c2d1
status: published
sort_order: 15
---

# Mengelola dan Memvalidasi Data Anggota

## Tujuan

Memberikan alur kerja Admin Koperasi untuk mencari data anggota,
memeriksa pendaftaran, dan menindaklanjuti status yang memerlukan aksi.

## Kapan digunakan

- Ada pendaftaran anggota baru yang menunggu verifikasi.
- Data anggota perlu diperbaiki atau diperbarui.
- Ada permintaan pengunduran diri yang perlu diproses.

## Prasyarat

- Sudah login sebagai Admin Koperasi.
- Memiliki akses ke modul Keanggotaan.
- Periksa data sesuai dokumen atau informasi yang menjadi dasar
  pendaftaran.

## Langkah penggunaan

1. Buka **Keanggotaan** lalu pilih **Data Anggota**.
2. Gunakan pencarian dan filter status atau status validasi untuk
   menemukan data yang akan diperiksa.
3. Buka detail anggota dan periksa identitas, kontak, status, serta
   ringkasan simpanan yang tersedia.
4. Untuk pendaftaran yang menunggu verifikasi, gunakan aksi verifikasi
   Admin setelah data dinyatakan sesuai.
5. Jika data belum lengkap, gunakan alur perubahan data yang tersedia
   atau teruskan permintaan perbaikan kepada anggota.
6. Untuk permintaan pengunduran diri, buka **Pengunduran Diri**, periksa
   status pengajuan, lalu proses sesuai kewenangan dan hasil pemeriksaan.

## Hasil yang diharapkan

- Data anggota dapat ditemukan dengan filter yang tepat.
- Pendaftaran yang valid diteruskan ke tahap approval Pengurus.
- Data yang belum sesuai tidak diproses sebagai valid sebelum klarifikasi.
- Permintaan pengunduran diri memiliki status dan catatan tindak lanjut.

## Status yang mungkin muncul

- **Menunggu Validasi**: data menunggu pemeriksaan Admin.
- **Menunggu Approval**: verifikasi Admin selesai dan menunggu Pengurus.
- **Perlu Revisi**: data harus diperbaiki sebelum diproses kembali.
- **Aktif** atau **Nonaktif**: status keanggotaan saat ini.

## Hal yang tidak boleh dilakukan

- Menyetujui final keanggotaan apabila aksi tersebut merupakan kewenangan
  Pengurus Koperasi.
- Mengubah data tanpa memeriksa sumber informasinya.
- Memproses pengunduran diri tanpa memeriksa status dan catatannya.

## Handoff

- Data yang sudah diverifikasi Admin → Pengurus Koperasi untuk approval
  final.
- Data yang perlu diperbaiki → anggota melalui proses onboarding.
- Kasus yang memerlukan keputusan kebijakan → Pengurus Koperasi.

## Prosedur terkait

- **Dashboard Operasional Admin Koperasi** untuk menemukan menu
  Keanggotaan.
- **Menyelesaikan Onboarding dan Memahami Akses Anggota** untuk konteks
  dari sisi anggota.
