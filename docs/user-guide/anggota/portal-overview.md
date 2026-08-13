---
title: Mengenal Portal Anggota
slug: anggota-portal-overview
summary: Peta singkat menu anggota, ringkasan simpan pinjam, dan alur notifikasi pembayaran.
category: Anggota · Memulai
module: portal
roles:
  - anggota
permissions: []
permission_mode: all
route_names:
  - member.dashboard
  - member.profile
  - member.savings
  - member.loans
  - member.points
  - member.rewards
  - member.transactions
  - member.store-account
  - member.notifications
  - member.onboarding
risk_level: low
screenshot_entries:
  - anggota-portal-overview-desktop
related_articles:
  - anggota-payment-flow
  - anggota-loan-flow
last_reviewed_commit: b20cd587
status: published
sort_order: 10
---

# Mengenal Portal Anggota

## Tujuan

Memberikan gambaran umum menu yang tersedia untuk anggota setelah
login, sehingga anggota dapat langsung menemukan fitur simpan pinjam,
pembayaran, dan notifikasi.

## Kapan digunakan

- Baru pertama kali login ke aplikasi.
- Ingin tahu letak menu tertentu (misalnya pinjaman atau iuran).
- Ingin memantau poin, rewards, dan histori transaksi.

## Prasyarat

- Sudah terdaftar sebagai anggota koperasi.
- Sudah menyelesaikan verifikasi email.
- Profil dasar sudah lengkap (nama, nomor identitas, kontak).

## Langkah penggunaan

1. Masuk ke aplikasi menggunakan akun anggota.
2. Pada halaman utama, Anda akan melihat **Dashboard** yang
   menampilkan ringkasan simpanan pokok, simpanan wajib, status
   keanggotaan, dan pintasan ke fitur finansial.
3. Buka **Profil Saya** untuk memperbarui data diri, keluarga, dan
   dokumen keanggotaan.
4. Buka **Simpanan** untuk melihat histori simpanan pokok, wajib,
   dan sukarela.
5. Buka **Pinjaman** untuk melihat daftar pinjaman aktif, angsuran
   berjalan, dan kalkulator plafon.
6. Buka **Poin & Rewards** untuk melihat poin yang terkumpul dan
   katalog hadiah.
7. Buka **Transaksi** atau **Store Account** untuk melihat histori
   transaksi.
8. Buka **Notifikasi** untuk melihat informasi tagihan, status
   pinjaman, dan pengumuman koperasi.
9. Setelah login pertama kali, sistem akan meminta Anda melengkapi
   data onboarding. Selesaikan onboarding agar semua fitur finansial
   dapat diakses.

## Hasil yang diharapkan

- Anggota memahami letak setiap menu tanpa harus bertanya.
- Anggota dapat langsung menuju fitur yang dibutuhkan dari
  dashboard.
- Onboarding selesai dan anggota masuk kategori "aktif" sehingga
  fitur finansial tampil penuh.

## Status yang mungkin muncul

- **Onboarding belum lengkap**: menu finansial belum bisa dibuka.
- **Akun non-aktif**: keanggotaan ditangguhkan, hubungi Admin
  Koperasi.
- **Email belum diverifikasi**: sistem meminta verifikasi sebelum
  login berhasil.

## Kondisi gagal

- Lupa kata sandi → gunakan menu **Lupa Sandi** di halaman login.
- Data profil tidak bisa disimpan → cek koneksi internet dan
  pastikan semua kolom wajib terisi.
- Onboarding macet di satu langkah → hubungi Admin Koperasi untuk
  bantuan.

## Hal yang tidak boleh dilakukan

- Memberikan kredensial login kepada orang lain.
- Mengubah data diri milik anggota lain.
- Memalsukan dokumen pendukung yang diminta saat onboarding.

## Handoff

- Jika anggota baru bingung → arahkan ke Admin Koperasi.
- Jika status keanggotaan non-aktif → Admin Koperasi akan
  mengaktifkan kembali setelah verifikasi data.
- Untuk pertanyaan seputar pinjaman → gunakan prosedur
  **Mengajukan dan Melacak Pinjaman**.

## Prosedur terkait

- **Alur Pembayaran Iuran via Midtrans** untuk langkah bayar iuran.
- **Mengajukan dan Melacak Pinjaman** untuk langkah ajukan
  pinjaman.
