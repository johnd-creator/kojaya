# Product Requirements Document (PRD)
## Kojayaku — Aplikasi Mobile Koperasi & HR

**Versi:** 1.0.0  
**Tanggal:** Mei 2026  
**Status:** Draft  
**Penulis:** Tim Produk Kojayaku

---

## 1. Ringkasan Eksekutif

Kojayaku adalah aplikasi mobile berbasis Flutter yang berfungsi sebagai client untuk backend Laravel sistem koperasi. Aplikasi ini melayani dua segmen pengguna utama: **Anggota Koperasi** yang membutuhkan akses ke layanan simpan-pinjam, dan **Pegawai** yang membutuhkan alat bantu manajemen kehadiran, lembur, dan administrasi HR.

### 1.1 Tujuan Produk

- Memberikan akses layanan koperasi secara digital, kapan saja dan di mana saja
- Menggantikan proses manual absensi dan pengajuan HR dengan sistem digital yang terintegrasi
- Meningkatkan transparansi data keuangan anggota koperasi
- Mengurangi beban administratif pegawai dan staf HR

### 1.2 Target Pengguna

| Segmen | Deskripsi | Kebutuhan Utama |
|--------|-----------|-----------------|
| Anggota Koperasi | Anggota aktif koperasi yang ingin memantau simpan-pinjam | Cek saldo, riwayat transaksi, pengajuan pinjaman |
| Pegawai | Karyawan aktif perusahaan/koperasi | Absensi, lembur, cuti, slip gaji |
| Pegawai + Anggota | Karyawan yang juga merupakan anggota koperasi | Semua fitur di atas |

---

## 2. Konteks & Latar Belakang

### 2.1 Masalah yang Diselesaikan

**Sisi Anggota:**
- Anggota harus datang ke kantor untuk mengecek saldo dan riwayat transaksi
- Proses pengajuan pinjaman masih manual, lama, dan tidak transparan
- Tidak ada notifikasi real-time untuk jatuh tempo angsuran

**Sisi Pegawai:**
- Absensi masih menggunakan fingerprint fisik atau kertas — rentan manipulasi
- Pengajuan lembur dan cuti melalui form kertas, lambat diproses
- Slip gaji harus diambil fisik setiap bulan

### 2.2 Solusi yang Ditawarkan

Aplikasi mobile terpusat yang terhubung langsung ke backend Laravel koperasi, memberikan akses real-time ke semua layanan dengan autentikasi berbasis peran (role-based access).

---

## 3. Ruang Lingkup Produk

### 3.1 Dalam Scope (MVP)

#### Modul Autentikasi
- Login dengan username/email dan password
- Sanctum Bearer Token management (30-day expiration with scoped abilities)
- Biometric login (fingerprint/face ID)
- Logout dan session management

#### Modul Anggota Koperasi
- Dashboard: ringkasan saldo simpanan, total pinjaman aktif ⚠️ *API belum tersedia*
- Riwayat transaksi: simpanan, penarikan, setoran ⚠️ *API belum tersedia*
- Pengajuan pinjaman baru dengan upload dokumen ⚠️ *API belum tersedia*
- Tracking status pengajuan pinjaman ⚠️ *API belum tersedia*
- Jadwal dan riwayat angsuran ⚠️ *API belum tersedia*
- Notifikasi jatuh tempo angsuran ⚠️ *API belum tersedia*

#### Modul Pegawai / HR
- Absensi masuk/keluar dengan validasi GPS dan foto selfie ⚠️ *API belum tersedia*
- Pengajuan lembur: tanggal, jam, alasan, dan approval flow ⚠️ *API belum tersedia*
- Pengajuan cuti: jenis cuti, durasi, alasan, sisa cuti ⚠️ *API belum tersedia*
- Pengajuan izin tidak masuk ⚠️ *API belum tersedia*
- Lihat dan unduh slip gaji (PDF) ⚠️ *API belum tersedia*
- Jadwal kerja dan shift ⚠️ *API belum tersedia*
- Pengumuman internal perusahaan ⚠️ *API belum tersedia*

#### Modul Profil & Pengaturan
- Data profil pengguna
- Ubah password
- Preferensi notifikasi
- Tentang aplikasi & versi

### 3.2 Di Luar Scope (v1.0)

- Fitur chat/pesan antar pengguna
- Modul admin/backoffice dalam aplikasi
- Fitur pembayaran terintegrasi (e-wallet, transfer bank)
- Multi-bahasa (v1.0 hanya Bahasa Indonesia)
- Apple Watch / wearable support

### 3.3 Backlog Kandidat (v2.0+)

- E-payment angsuran pinjaman langsung dari app
- Fitur rekruitmen internal (lihat lowongan, lamar posisi)
- Pelatihan & e-learning
- Reward & gamifikasi kehadiran
- Laporan keuangan anggota dalam bentuk grafik interaktif

---

## 4. User Stories & Acceptance Criteria

### 4.1 Autentikasi

**US-001: Login**
> Sebagai pengguna, saya ingin login menggunakan username dan password agar bisa mengakses fitur sesuai peran saya.

**Acceptance Criteria:**
- [ ] Tampil form login dengan field username/email dan password
- [ ] Validasi field tidak boleh kosong
- [ ] Tampil pesan error jika kredensial salah
- [ ] Setelah login berhasil, token disimpan secara aman (secure storage)
- [ ] Redirect ke dashboard sesuai peran (anggota/pegawai)
- [ ] Token kadaluarsa di-refresh otomatis tanpa logout paksa

**US-002: Biometric Login**
> Sebagai pengguna yang sudah login sebelumnya, saya ingin menggunakan sidik jari atau wajah untuk login lebih cepat.

### 4.2 Modul Anggota

**US-010: Lihat Dashboard**
> Sebagai anggota, saya ingin melihat ringkasan keuangan saya di halaman utama.

**Acceptance Criteria:**
- [ ] Menampilkan total saldo simpanan (wajib, sukarela, berjangka)
- [ ] Menampilkan total sisa pinjaman aktif
- [ ] Menampilkan angsuran berikutnya beserta tanggal jatuh tempo
- [ ] Data di-load dari API dengan loading indicator

**US-011: Riwayat Transaksi**
> Sebagai anggota, saya ingin melihat semua riwayat transaksi simpan-pinjam saya.

**Acceptance Criteria:**
- [ ] List transaksi dengan pagination (lazy load)
- [ ] Filter berdasarkan jenis transaksi dan rentang tanggal
- [ ] Setiap item menampilkan tanggal, jenis, jumlah, dan keterangan
- [ ] Bisa tap detail setiap transaksi

**US-012: Pengajuan Pinjaman**
> Sebagai anggota, saya ingin mengajukan pinjaman baru melalui aplikasi.

**Acceptance Criteria:**
- [ ] Form pengajuan: jumlah, tenor, tujuan pinjaman
- [ ] Upload dokumen pendukung (foto KTP, slip gaji, dll)
- [ ] Estimasi cicilan ditampilkan sebelum submit
- [ ] Konfirmasi sebelum pengajuan dikirim
- [ ] Notifikasi push saat status berubah (diproses, disetujui, ditolak)

### 4.3 Modul Pegawai

**US-020: Absensi Masuk/Keluar**
> Sebagai pegawai, saya ingin melakukan absensi dari aplikasi dengan validasi lokasi.

**Acceptance Criteria:**
- [ ] Tombol "Masuk" tersedia antara jam 06:00–09:00, "Keluar" antara jam 15:00–20:00
- [ ] Validasi radius GPS dari titik kantor (configurable, default 100m)
- [ ] Wajib mengambil foto selfie sebelum absensi berhasil
- [ ] Timestamp, koordinat GPS, dan foto dikirim ke server
- [ ] Status absensi hari ini ditampilkan di dashboard
- [ ] Handle offline: simpan data lokal dan sync saat online

**US-021: Pengajuan Lembur**
> Sebagai pegawai, saya ingin mengajukan lembur dan mengetahui status persetujuannya.

**Acceptance Criteria:**
- [ ] Form: tanggal, jam mulai, jam selesai, alasan lembur
- [ ] Tidak bisa mengajukan lembur untuk tanggal yang sudah lewat (>2 hari)
- [ ] Status: Menunggu → Disetujui / Ditolak
- [ ] Notifikasi push saat ada perubahan status
- [ ] Riwayat pengajuan lembur bisa difilter per bulan

**US-022: Pengajuan Cuti**
> Sebagai pegawai, saya ingin mengajukan cuti dan melihat sisa jatah cuti saya.

**Acceptance Criteria:**
- [ ] Tampil sisa cuti tahunan di bagian atas
- [ ] Form: jenis cuti, tanggal mulai, tanggal selesai, alasan
- [ ] Validasi: tidak boleh overlap dengan cuti yang sudah disetujui
- [ ] Kalkulasi otomatis jumlah hari yang diajukan (exclude weekend/libur)
- [ ] Status approval real-time

**US-023: Slip Gaji**
> Sebagai pegawai, saya ingin melihat dan mengunduh slip gaji saya.

**Acceptance Criteria:**
- [ ] List slip gaji per bulan, tersedia 12 bulan terakhir
- [ ] Preview PDF langsung di dalam aplikasi
- [ ] Tombol download simpan ke galeri/penyimpanan lokal
- [ ] Data slip bersifat privat, hanya bisa diakses pemilik akun

---

## 5. Persyaratan Non-Fungsional

### 5.1 Performa
- Waktu load halaman utama: < 2 detik pada koneksi 4G
- Waktu response API maksimal: 3 detik
- Ukuran APK release: < 30 MB
- Aplikasi bisa berjalan di Android 6.0 (API 23) ke atas dan iOS 13+

### 5.2 Keamanan
- Semua komunikasi menggunakan HTTPS/TLS 1.2+
- Token autentikasi disimpan di Flutter Secure Storage (tidak di SharedPreferences)
- Foto absensi di-upload langsung ke server, tidak disimpan permanen di perangkat
- Session otomatis expire setelah 8 jam tidak aktif
- PIN/biometrik wajib diaktifkan untuk akses slip gaji

### 5.3 Ketersediaan & Offline
- Fitur dashboard anggota dapat menampilkan data cache saat offline
- Absensi dapat dilakukan offline dan di-sync saat koneksi tersedia
- Aplikasi tidak crash saat tidak ada koneksi internet

### 5.4 Aksesibilitas
- Ukuran teks mengikuti pengaturan sistem (dynamic type)
- Kontras warna memenuhi WCAG AA
- Semua tombol aksi utama minimal 48×48 dp

### 5.5 Privasi
- Tidak ada data pengguna yang dikirim ke pihak ketiga tanpa consent
- Data lokasi hanya digunakan saat absensi, tidak dilacak sepanjang hari
- Sesuai dengan regulasi perlindungan data yang berlaku

---

## 6. Desain & UX Guidelines

### 6.1 Prinsip Desain
- **Clarity first:** Informasi keuangan harus mudah dibaca sekilas
- **Aksesibel:** Dapat digunakan oleh pengguna semua usia, termasuk yang tidak tech-savvy
- **Konsisten:** Pola interaksi yang sama di seluruh aplikasi
- **Informatif:** Selalu berikan feedback visual untuk setiap aksi pengguna

### 6.2 Navigasi Utama
- Bottom navigation bar dengan 4–5 item utama per role
- Role Anggota: Beranda, Transaksi, Pinjaman, Profil
- Role Pegawai: Beranda, Absensi, Pengajuan, Info, Profil
- Role Gabungan: Beranda, Koperasi, HR, Info, Profil

### 6.3 Branding
- Nama: Kojayaku
- Tone: Profesional namun ramah, terpercaya
- Palet warna: Dominan biru korporat dengan aksen hijau (kemakmuran/keuangan)

---

## 7. Metrik Keberhasilan

| Metrik | Target 3 Bulan | Target 6 Bulan |
|--------|----------------|----------------|
| User adoption rate | 60% anggota aktif | 85% anggota aktif |
| DAU (Pegawai) | 70% pegawai absensi via app | 90% |
| Pengajuan manual (kantor) | Turun 50% | Turun 80% |
| Rating Play Store | ≥ 4.0 | ≥ 4.3 |
| Crash-free sessions | ≥ 99% | ≥ 99.5% |
| Waktu load rata-rata | < 2 detik | < 1.5 detik |

---

## 8. Risiko & Mitigasi

| Risiko | Probabilitas | Dampak | Mitigasi |
|--------|-------------|--------|----------|
| API backend belum stabil | Tinggi | Tinggi | Buat mock API dari awal, parallel development |
| Pengguna tidak familiar teknologi | Sedang | Sedang | Onboarding tutorial, UI yang sangat simpel |
| Sinyal GPS lemah di kantor | Sedang | Tinggi | Toleransi radius GPS, fallback ke WiFi positioning |
| Upload dokumen berukuran besar | Sedang | Sedang | Kompresi foto otomatis sebelum upload |
| Offline sync konflik data | Rendah | Tinggi | Timestamp-based conflict resolution |

---

## 9. Timeline & Milestone

| Fase | Durasi | Deliverable |
|------|--------|-------------|
| Fase 1: Setup & Fondasi | Minggu 1–2 | Project structure, auth flow, design system |
| Fase 2: Modul Anggota | Minggu 3–5 | Dashboard, transaksi, pengajuan pinjaman |
| Fase 3: Modul Pegawai | Minggu 6–9 | Absensi GPS, lembur, cuti, slip gaji |
| Fase 4: Polish & Deploy | Minggu 10–12 | Testing, CI/CD, release ke Play Store & App Store |

---

## 10. Dependensi

- Backend Laravel API harus menyediakan endpoint sesuai kontrak API yang disepakati
- Firebase project aktif untuk push notification (FCM)
- Google Maps API key untuk validasi GPS absensi
- Sertifikat SSL aktif di server backend
- Akun Google Play Developer dan Apple Developer terdaftar

---

## 11. Status Ketersediaan API Backend

### 11.1 API yang Sudah Tersedia (Siap Dikembangkan)

Fitur-fitur berikut **sudah memiliki endpoint API** di backend Laravel dan siap dikonsumsi oleh Flutter app:

| Modul | Endpoint | Kemampuan | Status |
|-------|----------|-----------|--------|
| Autentikasi | `POST /login`, `GET /api/user` | Login, profil user | ✅ Siap |
| Manajemen Anggota | `/api/v1/members` | CRUD, activate, resign | ✅ Siap |
| Iuran Koperasi | `/api/v1/dues/invoices`, `/api/v1/dues/generate` | List, generate | ✅ Siap |
| Pembayaran | `/api/v1/dues/payments` | Catat, approve | ✅ Siap |
| Kasir POS | `/api/v1/pos/products`, `/api/v1/pos/transactions` | Produk, transaksi | ✅ Siap |
| Laporan Koperasi | `/api/v1/reports/cooperative-summary`, `/api/v1/reports/sales` | Dashboard, penjualan | ✅ Siap |
| Work Order Teknisi | `/api/technician/work-orders` | List, start, complete, checklist | ✅ Siap |
| Sertifikasi Karyawan | `/api/employees/{id}/certificates` | CRUD, upload dokumen | ✅ Siap |
| Medical Checkup | `/api/employees/{id}/mcu` | CRUD, upload dokumen | ✅ Siap |
| Laporan Kepatuhan | `/api/reports/certificate-compliance`, `/api/reports/mcu-compliance` | Compliance rate | ✅ Siap |

### 11.2 API yang Belum Tersedia (Perlu Dibangun di Backend)

Fitur-fitur berikut **belum memiliki endpoint API** dan perlu dikembangkan di backend terlebih dahulu:

| Modul | Fitur yang Dibutuhkan | Prioritas | Catatan |
|-------|----------------------|-----------|---------|
| **Token Issuance** | `POST /api/login` (mobile token creation) | P0 | Fortify default returns redirect; perlu endpoint JSON khusus |
| **Simpanan Anggota** | `GET /api/v1/members/{id}/savings`, list saldo per jenis | P0 | Model `CooperativeLedgerEntry` ada, tapi tidak ada endpoint khusus simpanan |
| **Pinjaman Anggota** | `GET/POST /api/v1/loans`, `GET /api/v1/loans/{id}/installments` | P1 | Tidak ada model Loan atau endpoint sama sekali |
| **Absensi (ESS)** | `POST /api/ess/clock-in`, `POST /api/ess/clock-out`, `GET /api/ess/attendance-history` | P1 | Model `Attendance` ada, form request `AttendanceApiLocationRequest` ada, tapi tidak ada ESS controller |
| **Pengajuan Lembur** | `GET/POST /api/ess/overtime-requests` | P1 | Model `OvertimeRequest` + `StoreOvertimeRequest` form ada, tapi tidak ada ESS endpoint |
| **Pengajuan Cuti** | `GET/POST /api/ess/leaves` | P1 | Model `Leave` + `StoreLeaveRequest` form ada, tapi tidak ada ESS endpoint |
| **Slip Gaji** | `GET /api/ess/payrolls`, `GET /api/ess/payrolls/{id}/pdf` | P2 | Model `Payroll` ada, tapi tidak ada ESS endpoint untuk akses sendiri |
| **Profil Update** | `PUT /api/user/profile`, `PUT /api/user/password` | P2 | Fortify menangani ini via web; perlu API wrapper |
| **Push Notification** | `POST /api/user/fcm-token`, `GET /api/notifications` | P2 | `NotificationPreference` model ada, tapi tidak ada FCM integration |
| **Pengumuman** | `GET /api/ess/announcements` | P3 | Tidak ada model Announcement |

### 11.3 Rekomendasi Urutan Pengembangan

**Fase 1 — Fondasi + Cooperative (Backend sudah siap):**
1. Token issuance endpoint (`POST /api/login`)
2. Flutter auth flow (login → token → profile)
3. Cooperative member management (CRUD, activate, resign)
4. Cooperative dues & payments
5. POS kasir (products + transactions)
6. Cooperative reports dashboard

**Fase 2 — Teknisi & Compliance (Backend sudah siap):**
1. Technician work orders (list, start, complete, checklists)
2. Employee certificates (CRUD + upload)
3. Medical checkups (CRUD + upload)
4. Compliance reports

**Fase 3 — ESS (Perlu backend baru):**
1. Backend: Buat `EssController` dengan endpoint clock-in/out, overtime, leave
2. Flutter: Absensi GPS + foto selfie + offline sync
3. Flutter: Pengajuan lembur & cuti
4. Flutter: Slip gaji viewer

**Fase 4 — Simpanan & Pinjaman (Perlu backend baru):**
1. Backend: Buat model `CooperativeLoan` + migration + controller + API
2. Backend: Buat endpoint simpanan dari ledger entries yang sudah ada
3. Flutter: Dashboard simpanan, pinjaman, angsuran

---

*Dokumen ini bersifat living document dan akan diperbarui seiring perkembangan proyek.*
