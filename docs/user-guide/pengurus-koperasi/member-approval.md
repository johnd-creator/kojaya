---
title: Approval Final Data Anggota
slug: pengurus-member-approval
summary: Cara meninjau hasil verifikasi Admin dan mengambil keputusan final atas data anggota.
category: Pengurus Koperasi · Keanggotaan
module: membership
roles:
  - pengurus_koperasi
permissions:
  - validate_cooperative_member
  - approve_cooperative_member
permission_mode: any
route_names:
  - cooperative.members.index
  - cooperative.members.show
  - cooperative.members.approve-final
  - cooperative.members.request-revision
  - cooperative.members.reject
risk_level: high
screenshot_entries: []
related_articles:
  - admin-koperasi-member-validation
  - anggota-onboarding-and-access
last_reviewed_commit: 999684c5f72029bd52ea8dced11203cac344c2d1
status: published
sort_order: 15
---

# Approval Final Data Anggota

## Tujuan

Menjelaskan cara Pengurus Koperasi meninjau data yang sudah diverifikasi
Admin dan mengambil keputusan final secara tercatat.

## Kapan digunakan

- Ada anggota dengan status menunggu approval final.
- Data anggota perlu dikembalikan untuk diperbaiki.
- Pengurus perlu menolak pendaftaran yang tidak memenuhi ketentuan.

## Prasyarat

- Sudah login sebagai Pengurus Koperasi.
- Data anggota sudah melewati verifikasi Admin Koperasi.
- Keputusan didasarkan pada informasi dan aturan koperasi yang berlaku.

## Langkah penggunaan

1. Buka **Data Anggota** dan gunakan filter status validasi.
2. Buka detail anggota yang menunggu approval.
3. Periksa data dan catatan verifikasi Admin.
4. Pilih salah satu tindakan yang tersedia:
   - **Approve Final** bila data memenuhi ketentuan.
   - **Request Revision** bila anggota perlu memperbaiki data.
   - **Reject** bila pendaftaran tidak dapat diterima.
5. Isi catatan keputusan bila diminta dan simpan tindakan.

## Hasil yang diharapkan

- Approval final mengaktifkan anggota sesuai proses aplikasi.
- Permintaan revisi mengembalikan data kepada anggota untuk diperbaiki.
- Penolakan memiliki status dan alasan yang dapat ditelusuri.

## Kondisi gagal

- Anggota belum berada pada status yang dapat diproses → periksa hasil
  verifikasi Admin.
- Data belum cukup untuk mengambil keputusan → minta revisi, jangan
  menyetujui dengan informasi yang belum lengkap.
- Aksi tidak tersedia → status atau kewenangan akun belum sesuai.

## Hal yang tidak boleh dilakukan

- Mengaktifkan anggota sebelum proses verifikasi selesai.
- Mengambil keputusan tanpa membaca catatan verifikasi.
- Menggunakan approval final untuk mengubah data anggota secara manual.

## Handoff

- Request Revision → anggota memperbarui onboarding.
- Approval final → Admin dapat menindaklanjuti kebutuhan operasional
  anggota aktif.
- Pertanyaan tentang pengajuan → Admin Koperasi.

## Prosedur terkait

- **Mengelola dan Memvalidasi Data Anggota** untuk proses verifikasi Admin.
- **Menyelesaikan Onboarding dan Memahami Akses Anggota** untuk konteks
  status dari sisi anggota.
