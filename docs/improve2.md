# Improve 2 - Audit Kebutuhan User Anggota, Pegawai Mobile, dan Operator

**Tanggal audit:** 5 Mei 2026  
**Basis audit:** `docs/project.md`, `docs/architecture.md`, `docs/plan.md`, `docs/api.md`, `routes/web.php`, `routes/api.php`, controller, service, model, dan halaman Vue yang tersedia di codebase.

---

## Ringkasan Eksekutif

Aplikasi sudah kuat untuk operasi admin web KojayaPro dan sudah memiliki fondasi Kojayaku web portal. Dari sisi anggota koperasi, fitur dasar sudah mencukupi untuk MVP web: dashboard, simpanan, tagihan, pembayaran, pinjaman, poin, reward, transaksi POS, profil, dan notifikasi. API mobile anggota sudah memiliki fondasi self-service, tetapi masih perlu payment gateway, dokumen pendukung, dan push notification produksi.

Dari sisi pegawai, ESS web sudah ada untuk dashboard, profil, payslip, compliance, absensi, dan cuti. ESS mobile API kini sudah mencakup dashboard, profil, absensi GPS dengan metadata device, shift roster, cuti, lembur, reimbursement, payslip, compliance, dan notifikasi database. Sisa gap terbesar ada pada push notification native, hardening device binding, offline queue, dan privasi payslip berbasis PIN/biometric di aplikasi mobile.

Dari sisi pegawai/operator yang mengurus aplikasi, prosedur bisnis inti sudah ada: pengelolaan anggota, iuran, pembayaran, pinjaman, POS, poin/reward, SHU, procurement, payroll approval, reimbursement, audit log, dan reporting. Kekurangannya ada pada kontrol prosedural: pemisahan role/otorisasi yang belum konsisten, approval yang masih single-step di beberapa modul, belum ada checklist close-period, belum ada payment gateway/reconciliation anggota end-to-end, dan dokumentasi API yang tidak sepenuhnya sesuai route aktual.

Prioritas pengembangan berikutnya sebaiknya bukan menambah modul besar baru, tetapi membuat pengalaman mobile dan prosedur operasional lebih utuh, aman, dan siap produksi.

---

## Peta Phase Pengembangan

| Phase | Fokus | Target Hasil | Prioritas |
|---|---|---|---|
| Phase 0 | Fondasi mobile API dan keamanan | Mobile bisa login, token jelas, API sesuai dokumentasi, boundary self-service rapi | Kritis |
| Phase 1 | Kojayaku anggota mobile/web harian | Anggota bisa cek data, ajukan pinjaman, bayar/upload bukti, lihat SHU dan notifikasi | Tinggi |
| Phase 2 | ESS pegawai mobile | Pegawai bisa absensi, cuti, lembur, reimbursement, payslip, dan notifikasi via mobile API | Tinggi |
| Phase 3 | Technician mobile lapangan | Teknisi bisa bekerja dengan evidence, spare part, GPS, timeline, dan offline sync dasar | Sedang |
| Phase 4 | Operator procedure hardening | Approval, closing, reconciliation, audit, role matrix, dan dashboard exception matang | Tinggi |
| Phase 5 | Integrasi produksi dan scale | Payment gateway, push notification, OpenAPI, monitoring, dan analytics siap operasional | Sedang |

Urutan phase ini bersifat dependency-aware. Phase 0 harus selesai lebih dulu karena semua aplikasi mobile membutuhkan auth, token abilities, route API yang stabil, dan dokumentasi yang cocok dengan implementasi. Phase 1 dan Phase 2 bisa berjalan paralel setelah Phase 0 selesai. Phase 4 sebaiknya dimulai sejak Phase 1 karena payment, loan, SHU, dan approval anggota langsung memengaruhi prosedur operator.

---

## Status Kecukupan Per Persona

| Persona | Status Saat Ini | Penilaian | Catatan Utama |
|---|---:|---|---|
| Anggota koperasi via web Kojayaku | Cukup untuk MVP | 75% | Fitur dasar lengkap, tetapi belum ada pembayaran online, dokumen anggota, SHU detail, support ticket, dan self-service API yang rapi |
| Anggota koperasi via mobile native | Cukup untuk alpha | 65% | API self-service anggota sudah punya auth, dashboard, profil, savings, payment proof, loan, SHU, notification, dan support ticket; payment gateway dan dokumen loan masih gap |
| Pegawai ESS via web | Cukup untuk MVP internal | 65% | Dashboard, profil, payslip, compliance, absensi, cuti tersedia, tetapi beberapa halaman masih terasa prototype dan belum semua workflow lengkap |
| Pegawai ESS via mobile native | Cukup untuk alpha | 70% | API ESS mobile sudah mencakup absensi Sanctum, metadata GPS/device, shift roster, cuti, lembur, reimbursement, payslip, compliance, dan notification; push native dan offline queue masih gap |
| Teknisi mobile | Cukup untuk alpha lapangan | 70% | API sudah punya filter assignment, checklist, evidence, spare part usage, GPS completion, timeline, escalation, reopen, dan offline sync idempotent; UI supervisor/review masih gap |
| Operator/admin koperasi | Cukup untuk operasi awal | 70% | Modul admin banyak, tetapi perlu hardening approval, audit trail, SOP closing, payment reconciliation, dan role matrix |
| Pengurus/manager | Cukup untuk monitoring awal | 60% | Report summary ada, tetapi belum ada actionable dashboard, aging, SLA, risiko kredit, NPL, dan drill-down lengkap |

---

## Temuan Utama

### 1. API Auth Mobile Belum Utuh

`docs/api.md` mendokumentasikan `POST /api/login` dan `POST /api/logout`, tetapi route aktual belum memperlihatkan endpoint tersebut di `routes/api.php`. Yang sudah tersedia adalah `GET /api/user` dan `POST /api/token/rotate`.

**Dampak:**
- Mobile native belum punya flow login/logout token yang jelas.
- Token abilities sudah dipakai, tetapi belum ada mekanisme penerbitan token berbasis persona aplikasi.
- Dokumentasi mobile bisa menyesatkan tim mobile karena tidak sesuai implementasi.

**Rekomendasi:**
- Tambahkan `POST /api/auth/login`, `POST /api/auth/logout`, `POST /api/auth/logout-all`, dan `GET /api/auth/session`.
- Saat login, tentukan abilities berdasarkan role dan tipe app: `member`, `ess`, `technician`, `admin`.
- Simpan `device_name`, `device_id`, `last_used_at`, dan metadata device minimal.
- Update `docs/api.md` agar sesuai route aktual.

---

## Audit Dari Sisi Anggota Koperasi

### Fitur Yang Sudah Mencukupi

| Area | Bukti Implementasi | Status |
|---|---|---|
| Dashboard anggota | `MemberPortalController::dashboard`, `Kojayaku/Dashboard.vue` | Ada |
| Simpanan, ledger, invoice, pembayaran | `MemberPortalController::savings`, `Kojayaku/Savings.vue` | Ada |
| Pengajuan dan tracking pinjaman | `MemberPortalController::loans/applyLoan`, `LoanApiController` | Ada |
| Poin dan histori | `PointApiController`, `Kojayaku/Points.vue` | Ada |
| Reward dan redeem | `RewardApiController`, `PointService::redeem`, `Kojayaku/Rewards.vue` | Ada |
| Riwayat transaksi POS | `MemberPortalController::transactions` | Ada |
| Profil anggota | `MemberPortalController::profile/updateProfile` | Ada untuk web |
| Notifikasi anggota | `MemberPortalController::notifications` | Ada untuk web |
| Registrasi anggota | `auth/Register.vue`, Fortify provisioning | Ada |

### Kekurangan Kritis Untuk Anggota

#### A. Self-Service API Anggota Belum Dibedakan Dari API Admin

Endpoint `/api/v1/members` bisa dipakai untuk CRUD anggota oleh pengurus/kasir, sementara anggota hanya boleh `show` jika datanya sendiri. Untuk aplikasi mobile anggota, lebih aman dan lebih sederhana jika ada namespace khusus:

- `GET /api/v1/member/me`
- `PUT /api/v1/member/profile`
- `GET /api/v1/member/dashboard`
- `GET /api/v1/member/savings`
- `GET /api/v1/member/savings/ledger`
- `GET /api/v1/member/dues/invoices`
- `GET /api/v1/member/payments`
- `POST /api/v1/member/payments/proof`
- `GET /api/v1/member/loans`
- `POST /api/v1/member/loans`
- `GET /api/v1/member/points`
- `GET /api/v1/member/rewards`
- `POST /api/v1/member/rewards/{reward}/redeem`
- `GET /api/v1/member/notifications`

#### B. Pembayaran Anggota Belum End-to-End

Saat ini anggota bisa melihat tagihan/pembayaran, dan admin bisa mencatat/approve pembayaran. Belum ada flow anggota untuk:

- Upload bukti transfer.
- Pilih invoice yang ingin dibayar.
- Status verifikasi pembayaran yang mudah dipahami.
- Integrasi payment gateway QRIS/VA/e-wallet.
- Receipt digital setelah pembayaran approved.
- Reminder jatuh tempo otomatis.

#### C. Simpanan Belum Menjadi Statement Yang Siap Pakai Anggota

Ledger sudah ada, tetapi untuk kebutuhan anggota biasanya perlu:

- Ringkasan saldo per jenis simpanan: pokok, wajib, sukarela, SHU, pinjaman.
- Mutasi dengan saldo berjalan.
- Download statement PDF per periode.
- Filter periode.
- Penjelasan debit/kredit yang tidak memakai istilah akuntansi mentah.

#### D. Pinjaman Belum Lengkap Dari Sisi Anggota

Pengajuan dan tracking dasar sudah ada. Yang masih kurang:

- Detail jadwal angsuran per pinjaman di UI anggota.
- Status angsuran: belum jatuh tempo, jatuh tempo, telat, lunas.
- Simulasi pinjaman berbasis real API di portal anggota, bukan hanya estimasi frontend.
- Upload dokumen pendukung pengajuan.
- Riwayat approval dengan alasan penolakan.
- Opsi bayar angsuran atau upload bukti pembayaran.
- Informasi penalti dan total kewajiban sampai tanggal hari ini.

#### E. SHU Belum Terlihat Sebagai Hak Anggota

Admin sudah punya modul SHU. Dari sisi anggota, seharusnya ada:

- Estimasi hak SHU tahun berjalan.
- Riwayat pembagian SHU.
- Dasar perhitungan: masa keanggotaan, simpanan wajib, transaksi POS/poin.
- Status pencairan SHU.

#### F. Profil Anggota Masih Terlalu Dasar

Profil anggota saat ini mencakup nama, email, telepon, alamat. Untuk koperasi, biasanya perlu:

- NIK/KTP, tanggal lahir, pekerjaan, unit kerja.
- Kontak darurat.
- Rekening bank untuk pencairan SHU/pinjaman.
- Dokumen KYC.
- Status verifikasi profil.
- Riwayat perubahan profil yang perlu approval admin.

#### G. Tidak Ada Kanal Bantuan/Komplain

Anggota belum punya tempat untuk:

- Bertanya soal tagihan.
- Mengajukan komplain transaksi POS.
- Mengajukan koreksi data simpanan.
- Tracking tiket support.

---

## Audit Dari Sisi Pegawai Untuk Mobile App

### Fitur Yang Sudah Ada di Laravel

| Area | Implementasi Web/API Saat Ini | Status |
|---|---|---|
| ESS dashboard | `EssPortalController::dashboard`, `ESS/Dashboard.vue` | Ada web |
| Profil pegawai | `EssPortalController::profile/updateProfile` | Ada web |
| Payslip | `EssPortalController::payslips` | Ada web |
| Compliance dokumen/MCU | `EssPortalController::compliance`, employee document API | Ada sebagian |
| Absensi GPS | `AttendanceController::checkInApi/checkOutApi/geofence` | Ada JSON di web route |
| Pengajuan cuti | `LeaveController::selfService/store` | Ada web |
| Overtime | `OvertimeController` | Ada web |
| Reimbursement | `ReimbursementController` | Ada web |
| Technician work orders | `TechnicianWorkOrderController` | Ada API |

### Kekurangan Kritis Untuk ESS Mobile

#### A. ESS API Mobile Sanctum

Status Phase 2: endpoint ESS mobile utama sudah berada di `routes/api.php` dengan `auth:sanctum`, throttle, dan ability middleware. Route web lama masih bisa dipakai untuk portal web, tetapi mobile native harus memakai `/api/ess/*`.

**Endpoint tersedia:**
- `GET /api/ess/dashboard`
- `GET /api/ess/profile`
- `PUT /api/ess/profile`
- `GET /api/ess/attendance/today`
- `GET /api/ess/attendance/history`
- `POST /api/ess/attendance/check-in`
- `POST /api/ess/attendance/check-out`
- `GET /api/ess/geofence`
- `GET /api/ess/shift-roster`
- `GET /api/ess/leaves`
- `POST /api/ess/leaves`
- `GET /api/ess/overtime`
- `POST /api/ess/overtime`
- `GET /api/ess/reimbursements`
- `POST /api/ess/reimbursements`
- `GET /api/ess/payslips`
- `GET /api/ess/payslips/{payroll}/download`
- `GET /api/ess/compliance`
- `GET /api/ess/notifications`

#### B. Absensi Mobile Belum Siap Produksi

Status Phase 2: check-in/check-out API sudah menyimpan latitude, longitude, accuracy, device id, dan `mobile_audit`. Untuk produksi penuh masih perlu:

- Device binding untuk membatasi satu akun pada device terdaftar.
- Penyimpanan latitude/longitude/accuracy per check-in dan check-out, bukan hanya `device_id` dalam notes.
- Foto/selfie atau liveness opsional.
- Deteksi mock location/rooted device di sisi mobile dan flag di backend.
- Riwayat absensi per bulan.
- Koreksi absensi dengan approval.
- Offline queue dengan nonce agar tidak double submit.
- Audit log khusus absensi.

#### C. Cuti Mobile Belum Lengkap

Status Phase 2: list, balance, create, attachment, dan cancellation request sudah tersedia. Lanjutan produksi:

- List leave balance per jenis cuti.
- Ajukan cuti dengan lampiran.
- Batalkan pengajuan yang masih pending.
- Lihat alasan penolakan.
- Approval push notification.
- Kalender cuti tim/unit jika role mengizinkan.

#### D. Payslip Mobile Perlu Mode Aman

Status Phase 2: list payslip dan download PDF sudah scoped ke employee pemilik token dan status payroll `PROCESSED`/`PAID`. Lanjutan privasi mobile:

- PIN/biometric re-auth sebelum membuka payslip.
- Download PDF aman.
- Watermark user dan timestamp.
- Masking nominal di dashboard.
- Riwayat THR dan potongan.

#### E. Overtime dan Reimbursement Belum Mobile-First

Status Phase 2: overtime mobile dan reimbursement dengan upload struk sudah tersedia. Lanjutan produksi:

- Ajukan lembur dari mobile.
- Lampirkan bukti/notes.
- Reimbursement dengan foto struk.
- Tracking approval.
- Push notification saat approved/rejected/paid.

---

## Audit Khusus Teknisi Mobile

### Yang Sudah Ada

Technician API sudah menyediakan:
- List work order assigned ke user.
- Detail work order dengan asset, checklist, parts.
- Start work order.
- Complete work order setelah checklist selesai.
- Update checklist.

### Kekurangan Untuk Operasi Lapangan

| Kebutuhan Teknisi | Status | Rekomendasi |
|---|---|---|
| Pagination dan filter status/prioritas | Belum cukup | Tambahkan `status`, `priority`, `scheduled_date`, pagination |
| Foto sebelum/sesudah pekerjaan | Belum ada | Tambah attachment API per work order/checklist |
| Pemakaian spare part | Data relasi ada, API aksi belum terlihat | Tambah endpoint consume/return part |
| Catatan penyelesaian | Terbatas notes checklist | Tambah completion notes, root cause, action taken |
| GPS lokasi pengerjaan | Belum terlihat | Simpan koordinat start/complete |
| Offline mode | Belum ada | Tambah sync endpoint berbasis client mutation id |
| Tanda tangan user/approval lapangan | Belum ada | Tambah customer/site acceptance |
| Riwayat status | Belum ada | Tambah timeline/status log |
| Reassignment/escalation | Belum ada di API mobile | Tambah request escalate/reassign untuk supervisor |

---

## Audit Dari Sisi Operator/Admin Yang Mengurus Aplikasi

### Prosedur Yang Sudah Tersedia

| Prosedur | Implementasi | Status |
|---|---|---|
| Manajemen anggota | CRUD, activate, resign, provisioning user | Ada |
| Iuran/tagihan | Generate dues, mark paid | Ada |
| Pembayaran koperasi | Record, approve, ledger entry | Ada |
| Pinjaman | Apply, approve, reject, disburse, pay, approval log | Ada |
| Poin/reward | Catalog reward, redeem, status redemption, refund cancellation | Ada |
| POS koperasi | Produk, kategori, transaksi, stock movement | Ada |
| SHU | Preview dan close annual SHU | Ada |
| HR/ESS | Employee, attendance, leave, overtime, payroll | Ada |
| Procurement | PR, approval, PO, GRN | Ada |
| Audit/reporting | Audit logs dan reports | Ada |

### Gap Prosedural Operator

#### A. Role Matrix Belum Menjadi Dokumen/Aturan Tunggal

Masih ada banyak `hasRole()` langsung di controller untuk beberapa modul. Ini membuat prosedur akses sulit diaudit.

**Rekomendasi:**
- Buat `docs/role-matrix.md` atau section di `improve2.md` versi operasional.
- Standardisasi policy per domain: cooperative member, dues, payment, loan, POS, reward, SHU.
- Pisahkan role:
  - `Pengurus Koperasi`: approval dan konfigurasi.
  - `Kasir Koperasi`: POS dan pencatatan pembayaran.
  - `Admin Simpan Pinjam`: administrasi pinjaman tanpa hak final approval.
  - `Finance Staff`: rekonsiliasi dan ledger.
  - `Anggota`: self-service saja.

#### B. Approval Workflow Belum Konsisten Multi-Level

Procurement punya approval level. Pinjaman, pembayaran koperasi, reimbursement, invoice, dan payroll masih cenderung single-step atau per modul.

**Rekomendasi:**
- Buat shared `ApprovalWorkflowService`.
- Definisikan threshold nominal dan level approval.
- Semua approval menyimpan actor, role, notes, previous status, new status, timestamp.
- Tambahkan halaman "Approval Inbox" lintas modul.

#### C. Closing Period Belum Jelas

Untuk koperasi, operator perlu prosedur tutup periode:

- Tutup iuran bulanan.
- Rekonsiliasi pembayaran.
- Posting ledger final.
- Lock transaksi periode.
- Generate laporan pengurus.
- Tutup SHU tahunan.

Saat ini SHU bisa di-close, tetapi belum terlihat lock period dan checklist operasional bulanan.

#### D. Payment dan Reconciliation Belum Anggota-Centric

Admin bisa record/approve pembayaran, tetapi belum terlihat flow:

- Anggota upload bukti.
- Admin verifikasi.
- Finance rekonsiliasi bank.
- Ledger otomatis.
- Receipt diterbitkan.
- Invoice status berubah.

#### E. Audit Trail Belum Merata Secara Produk

Audit log ada, approval log pinjaman ada, procurement punya log. Namun operator butuh audit yang konsisten untuk:

- Perubahan data anggota.
- Perubahan saldo opening.
- Aktivasi/resign anggota.
- Pembayaran dan approval.
- Pembatalan redemption.
- Perubahan loan terms.
- Closing SHU.

#### F. Data Quality dan Master Data Perlu Guardrail

Koperasi membutuhkan data yang akurat. Perlu tambahan:

- Validasi NIK unik dan format.
- Nomor anggota tidak bisa diubah sembarangan.
- Status anggota membatasi akses pinjaman/reward.
- Resign anggota harus memeriksa saldo/pinjaman aktif.
- Loan type tidak bisa dihapus jika sudah dipakai.
- Reward tidak bisa dihapus jika ada redemption aktif.
- Produk POS tidak bisa dihapus jika punya transaksi.

---

## Gap Dokumentasi vs Implementasi

| Dokumen | Klaim | Kondisi Aktual | Rekomendasi |
|---|---|---|---|
| `docs/api.md` | `POST /api/login` dan `/api/logout` tersedia | Tidak terlihat di `routes/api.php` | Implement endpoint atau revisi dokumentasi |
| `docs/api.md` | ESS base path `/api/ess` | Absensi ESS JSON ada di `routes/web.php`, bukan API Sanctum | Pindahkan/duplikasi ke `routes/api.php` |
| `docs/api.md` | Kojayaku `/api/v1/savings` | Route aktual memakai `/api/v1/dues/invoices`, ledger belum dedicated | Tambah savings API self-service |
| `docs/architecture.md` | API-first mobile | Sebagian mobile masih web/session | Rapikan boundary mobile API |
| `docs/plan.md` | ESS API planned | Implementasi sebagian tersebar | Jadikan prioritas P0/P1 roadmap mobile |

---

## Roadmap Pengembangan Berbasis Phase

### Phase 0 - Fondasi Mobile API dan Keamanan

**Tujuan:** Membuat backend siap dikonsumsi mobile native dengan kontrak API yang stabil, aman, dan sesuai dokumentasi.

**Scope utama:**
- Buat API auth mobile: login, logout, logout-all, token rotate, current session.
- Terbitkan token abilities berdasarkan persona aplikasi: `member`, `ess`, `technician`, `admin`.
- Pisahkan endpoint self-service anggota dari endpoint admin koperasi.
- Pindahkan ESS attendance JSON ke `routes/api.php` dengan `auth:sanctum`.
- Tambahkan ability middleware untuk ESS: `attendance:read`, `attendance:write`, `ess:read`, `ess:write`, `payroll:read`.
- Update `docs/api.md` agar sesuai route aktual.
- Buat role matrix koperasi dan ESS, lalu migrasikan authorization penting ke policy/gate.

**Deliverable:**
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/logout-all`
- `GET /api/auth/session`
- `POST /api/token/rotate`
- Namespace awal `/api/v1/member/*`
- Namespace awal `/api/ess/*`
- Dokumentasi API mobile yang valid.

**Exit criteria:**
- Mobile bisa login dan mendapatkan token dengan abilities yang benar.
- Semua endpoint mobile utama memakai Sanctum token, bukan session web.
- Test unauthorized, forbidden, invalid token, dan ownership tersedia untuk endpoint inti.
- `docs/api.md` tidak lagi memuat endpoint yang belum diimplementasikan tanpa label planned.

**Progress implementasi:**
- [x] API auth mobile tersedia: `POST /api/auth/login`, `POST /api/auth/logout`, `POST /api/auth/logout-all`, `GET /api/auth/session`.
- [x] Token rotation tersedia di `POST /api/token/rotate`.
- [x] Login menerbitkan token abilities berdasarkan persona aplikasi (`member`, `ess`, `technician`, `admin`).
- [x] Namespace awal anggota tersedia di `/api/v1/member/dashboard` dan `/api/v1/member/profile`.
- [x] Namespace awal ESS tersedia di `/api/ess/dashboard`, profile, geofence, attendance today/history, check-in, dan check-out.
- [x] ESS attendance mobile kini berada di `routes/api.php` dengan Sanctum ability middleware.
- [x] `docs/api.md` disesuaikan untuk auth mobile aktual.
- [~] Role matrix dan migrasi policy/gate lintas seluruh modul masih perlu dilanjutkan sebagai hardening bertahap di Phase 4.

---

### Phase 1 - Kojayaku Anggota Siap Dipakai Harian

**Tujuan:** Membuat pengalaman anggota lengkap untuk kebutuhan koperasi sehari-hari: cek saldo, bayar tagihan, ajukan pinjaman, redeem reward, dan melihat informasi hak anggota.

**Scope utama:**
- Tambah dashboard API anggota yang agregat: saldo, tagihan, pinjaman, poin, notifikasi.
- Tambah savings statement API dan UI dengan saldo berjalan.
- Tambah detail saldo per jenis simpanan: pokok, wajib, sukarela, SHU, pinjaman.
- Tambah payment proof upload dan approval admin.
- Tambah receipt digital setelah pembayaran approved.
- Tambah detail angsuran pinjaman dan status overdue.
- Tambah simulasi pinjaman berbasis API yang sama dengan service backend.
- Tambah upload dokumen pendukung pengajuan pinjaman.
- Tambah SHU anggota: riwayat, estimasi, dan detail perhitungan.
- Tambah notification API untuk mobile.
- Tambah support ticket/komplain anggota.

**Deliverable:**
- [x] `GET /api/v1/member/dashboard`
- [x] `GET /api/v1/member/profile`
- [x] `PUT /api/v1/member/profile`
- [x] `GET /api/v1/member/savings/summary`
- [x] `GET /api/v1/member/savings/ledger`
- [x] `GET /api/v1/member/dues/invoices`
- [x] `GET /api/v1/member/payments`
- [x] `POST /api/v1/member/payments/proof`
- [x] `GET /api/v1/member/loans`
- [x] `POST /api/v1/member/loans`
- [x] `GET /api/v1/member/loans/{loan}`
- [x] `GET /api/v1/member/shu`
- [x] `GET /api/v1/member/notifications`
- [x] `GET /api/v1/member/support-tickets`
- [x] `POST /api/v1/member/support-tickets`

**Exit criteria:**
- Anggota hanya bisa membaca dan mengubah data miliknya sendiri.
- Anggota bisa melihat saldo berjalan dan tagihan aktif dari mobile.
- Anggota bisa upload bukti pembayaran dan melihat status verifikasi.
- Anggota bisa melihat jadwal angsuran, status telat, dan alasan penolakan pinjaman.
- Anggota bisa melihat hak SHU atau status SHU belum tersedia dengan pesan yang jelas.
- Semua flow kritis punya test ownership dan negative case.

**Progress implementasi:**
- [x] Dashboard anggota memakai data agregat saldo, tagihan, pinjaman, poin, tier, dan notifikasi.
- [x] Savings summary dan ledger statement dengan running balance tersedia.
- [x] Tagihan dan riwayat pembayaran anggota tersedia.
- [x] Upload bukti pembayaran membuat payment `PENDING` dengan `proof_path` untuk verifikasi admin.
- [x] API pinjaman anggota bisa list, apply, dan detail dengan installments/payments/approval logs.
- [x] SHU anggota menampilkan periode closed dan allocation milik anggota.
- [x] Notification API anggota tersedia.
- [x] Support ticket/komplain anggota tersedia melalui tabel `cooperative_support_tickets`.
- [x] Test `Phase1MemberSelfServiceApiTest` menutup savings, invoice, payment proof, loan ownership, SHU, notification, dan support ticket.
- [~] Payment gateway, receipt digital final, upload dokumen pendukung loan, dan push notification tetap menjadi kelanjutan Phase 5/Phase 1 lanjutan.

---

### Phase 2 - ESS Pegawai Mobile Siap Operasi

**Tujuan:** Membuat pegawai bisa menjalankan self-service dari mobile tanpa bergantung pada halaman web: absensi, cuti, lembur, reimbursement, payslip, compliance, dan notifikasi.

**Scope utama:**
- Tambah ESS dashboard API.
- Tambah attendance today/history API.
- Tambah attendance device binding dan penyimpanan koordinat/accuracy.
- Tambah leave balance dan cancellation request.
- Tambah overtime mobile flow.
- Tambah reimbursement mobile flow dengan upload struk.
- Tambah payslip secure view/download.
- Tambah shift roster endpoint.
- Tambah compliance document endpoint untuk pegawai melihat dokumen miliknya.
- Tambah push notification event untuk approval dan payroll.

**Deliverable:**
- `GET /api/ess/dashboard`
- `GET /api/ess/profile`
- `PUT /api/ess/profile`
- `GET /api/ess/attendance/today`
- `GET /api/ess/attendance/history`
- `POST /api/ess/attendance/check-in`
- `POST /api/ess/attendance/check-out`
- `GET /api/ess/geofence`
- `GET /api/ess/shift-roster`
- `GET /api/ess/leaves`
- `POST /api/ess/leaves`
- `POST /api/ess/leaves/{leave}/cancel`
- `GET /api/ess/overtime`
- `POST /api/ess/overtime`
- `GET /api/ess/reimbursements`
- `POST /api/ess/reimbursements`
- `GET /api/ess/payslips`
- `GET /api/ess/payslips/{payroll}/download`
- `GET /api/ess/compliance`
- `GET /api/ess/notifications`

**Exit criteria:**
- Pegawai bisa check-in/out dari API Sanctum.
- Absensi menyimpan lokasi, accuracy, device id, dan audit yang bisa ditinjau HR.
- Pegawai bisa melihat riwayat absensi dan shift roster.
- Pegawai bisa mengajukan cuti/lembur/reimbursement dari mobile.
- Pegawai bisa melihat payslip dengan kontrol privasi.
- Semua endpoint ESS punya ability middleware dan test ownership.

**Status eksekusi Phase 2 (May 6, 2026):**
- [x] ESS dashboard/profile tetap tersedia di `/api/ess` dengan Sanctum ability.
- [x] Attendance today/history/check-in/check-out tersedia di API Sanctum.
- [x] Absensi mobile menyimpan latitude, longitude, accuracy, device id, dan `mobile_audit` untuk check-in/check-out.
- [x] Shift roster pegawai tersedia berdasarkan `employee.shift_group`.
- [x] Leave API menampilkan list dan balance per jenis cuti.
- [x] Leave create mendukung attachment dan validasi jenis cuti yang wajib attachment.
- [x] Leave cancellation memakai metadata `cancel_requested_at`, `cancel_requested_by`, dan `cancel_reason` agar tidak bentrok dengan constraint status approval lama.
- [x] Overtime mobile flow tersedia dengan validasi rule aktif, batas minimum, dan batas harian.
- [x] Reimbursement mobile flow tersedia dengan upload struk per item.
- [x] Payslip list dan PDF download tersedia hanya untuk payroll milik pegawai login dengan status `PROCESSED` atau `PAID`.
- [x] Compliance endpoint mengembalikan certificate dan medical checkup milik pegawai login.
- [x] Notifications endpoint memakai database notifications milik user login.
- [x] Test `Phase2EssMobileApiTest` menutup absensi metadata, shift roster, leave ownership/cancel request, overtime, reimbursement, payslip, compliance, dan notification.
- [~] Push notification native masih berupa database notification API; FCM/APNs/webhook masuk Phase 7 hardening integrasi.

---

### Phase 3 - Technician App Lapangan

**Tujuan:** Mengubah technician API dari MVP menjadi workflow lapangan yang dapat dipercaya untuk maintenance dan work order.

**Scope utama:**
- Tambah pagination dan filter status/prioritas/scheduled date.
- Tambah foto before/after work order.
- Tambah spare part consumption endpoint.
- Tambah work order timeline/status log.
- Tambah offline sync endpoint dengan idempotency key.
- Tambah GPS start/complete dan completion notes.
- Tambah supervisor review/reopen flow.
- Tambah escalation/reassignment request.

**Deliverable:**
- `GET /api/technician/work-orders?status=&priority=&scheduled_date=`
- `POST /api/technician/work-orders/{id}/attachments`
- `POST /api/technician/work-orders/{id}/parts`
- `POST /api/technician/work-orders/{id}/sync`
- `GET /api/technician/work-orders/{id}/timeline`
- `POST /api/technician/work-orders/{id}/escalate`
- `POST /api/technician/work-orders/{id}/reopen`
- `POST /api/technician/work-orders/{id}/complete` dengan GPS, notes, dan evidence.

**Exit criteria:**
- Teknisi bisa menyelesaikan work order dengan checklist, foto, lokasi, dan catatan.
- Pemakaian spare part tercatat dan dapat ditinjau admin.
- Submit offline tidak membuat duplikasi karena memakai idempotency key.
- Supervisor bisa melihat timeline dan membuka ulang pekerjaan jika perlu.

**Status eksekusi Phase 3 (May 6, 2026):**
- [x] List technician work order mendukung pagination, filter `status`, `priority`, dan `scheduled_date`.
- [x] Work order punya field mobile lapangan: `scheduled_date`, `started_at`, GPS start, GPS completion, completion notes, review/reopen, dan escalation metadata.
- [x] Endpoint attachment tersedia untuk evidence `BEFORE`, `AFTER`, dan `OTHER` dengan file upload dan GPS optional.
- [x] Endpoint spare part consumption tersedia dan terscope ke work order milik teknisi.
- [x] Timeline/status log tersedia melalui tabel `work_order_timelines` dan endpoint `/timeline`.
- [x] Start/checklist/attachment/part/complete/escalate/reopen/offline sync mencatat event timeline.
- [x] Complete work order wajib mengirim GPS dan notes optional, serta tetap memblokir checklist yang belum selesai.
- [x] Offline sync memakai tabel `work_order_sync_requests` dan `idempotency_key` agar submit ulang tidak menggandakan part/checklist.
- [x] Escalation/reassignment request tersedia melalui `/escalate`.
- [x] Supervisor reopen tersedia melalui `/reopen` dengan ability `work-orders:review`.
- [x] Test `Phase3TechnicianMobileApiTest` menutup filter/pagination, evidence upload, spare part, GPS completion, timeline, offline idempotency, escalation, dan reopen.
- [~] Review UI admin/supervisor untuk menampilkan timeline/evidence/parts masih menjadi kelanjutan Phase 4 operator hardening.

---

### Phase 4 - Operator Procedure Hardening

**Tujuan:** Memastikan prosedur admin/operator koperasi dan HR berjalan konsisten, audit-ready, dan aman untuk operasional rutin.

**Scope utama:**
- Buat shared approval inbox lintas modul.
- Buat monthly closing checklist koperasi.
- Tambah lock period untuk ledger/iuran/pembayaran.
- Tambah bank reconciliation untuk payment anggota.
- Tambah exception dashboard: overdue loans, unpaid dues, pending approvals, low stock, failed payments.
- Tambah export laporan anggota, simpanan, pinjaman, SHU, dan transaksi.
- Tambah audit trail konsisten untuk perubahan data anggota, opening balance, aktivasi/resign, pembayaran, redemption, loan terms, dan closing SHU.
- Tambah guardrail master data.

**Deliverable:**
- Role matrix final untuk koperasi, ESS, technician, finance, dan HR.
- Approval inbox lintas modul.
- Monthly closing page.
- Period lock service.
- Payment reconciliation workflow.
- Exception dashboard operator.
- Export laporan operasional koperasi.

**Exit criteria:**
- Semua approval penting memiliki log standar.
- Closing period mencegah perubahan data periode terkunci.
- Payment anggota punya bukti, verifikasi, ledger, receipt, dan reconciliation.
- Dashboard operator menunjukkan pekerjaan tertunda dan anomali.
- Dokumentasi prosedur sesuai implementasi.

---

### Phase 5 - Integrasi Produksi dan Scale

**Tujuan:** Menyiapkan sistem untuk penggunaan produksi dengan integrasi eksternal, observability, dokumentasi kontrak API, dan pengalaman real-time.

**Scope utama:**
- Integrasi payment gateway QRIS/VA/e-wallet.
- Integrasi push notification FCM.
- Integrasi WhatsApp notification untuk reminder penting.
- Buat OpenAPI/Swagger dari route aktual.
- Tambah monitoring API, error tracking, dan audit dashboard.
- Tambah Redis caching untuk dashboard/report yang berat.
- Tambah analytics: NPL pinjaman, overdue aging, SHU projection, churn/aktivitas anggota.

**Deliverable:**
- Payment gateway production-ready.
- Push notification production-ready.
- OpenAPI documentation.
- Monitoring dashboard.
- Analytics koperasi dasar.

**Exit criteria:**
- Pembayaran online bisa masuk, diverifikasi, dan diposting ke ledger.
- Push notification terkirim untuk approval, pembayaran, due date, payslip, dan work order.
- Tim mobile punya OpenAPI yang cocok dengan backend.
- Operator punya insight risiko dan performa koperasi.

---

## Definition of Done Per Phase

### Phase 0

- API auth mobile tersedia dan dites.
- Token abilities sesuai persona aplikasi.
- Endpoint mobile inti memakai `auth:sanctum`.
- Dokumentasi API cocok dengan route aktual.
- Ownership dan authorization endpoint inti sudah diuji.

### Phase 1

- Anggota bisa menjalankan workflow harian dari mobile API.
- Data anggota selalu scoped ke user sendiri.
- Simpanan, pinjaman, pembayaran, poin, reward, SHU, dan notifikasi punya endpoint self-service.
- Payment proof dan status verifikasi berjalan end-to-end.
- Test happy path, unauthorized, forbidden, validation, dan ownership tersedia.

### Phase 2

- Pegawai bisa menjalankan ESS dari mobile API.
- Absensi menyimpan lokasi, accuracy, device, dan audit.
- Cuti, lembur, reimbursement, payslip, shift, dan compliance tersedia untuk pegawai.
- HR/supervisor bisa meninjau output workflow pegawai.
- Test endpoint ESS mencakup ownership dan role boundary.

### Phase 3

- Teknisi bisa bekerja dari mobile dengan checklist, evidence, spare part, GPS, dan notes.
- Work order punya timeline/status log.
- Offline sync memakai idempotency key.
- Supervisor bisa review, reopen, atau eskalasi pekerjaan.

### Phase 4

- Role matrix disepakati dan diimplementasikan.
- Approval lintas modul bisa diproses dari inbox yang sama.
- Closing period dan period lock berjalan.
- Reconciliation payment anggota berjalan sampai ledger dan receipt.
- Audit trail penting konsisten.

### Phase 5

- Payment gateway dan push notification berjalan di flow produksi.
- OpenAPI tersedia untuk tim mobile.
- Monitoring API dan error tracking aktif.
- Dashboard analytics membantu pengurus melihat risiko dan performa koperasi.

---

## Kesimpulan

KojayaPro sudah cukup kuat sebagai admin ERP awal. Kojayaku web sudah layak sebagai MVP anggota, tetapi belum cukup sebagai mobile product yang mandiri. ESS pegawai juga sudah punya fondasi web, tetapi mobile API masih menjadi gap terbesar. Pengembangan berikutnya sebaiknya mengikuti phase di atas: mulai dari fondasi API/auth, lanjut ke anggota dan ESS mobile, lalu technician field workflow, hardening prosedur operator, dan terakhir integrasi produksi seperti payment gateway, push notification, OpenAPI, monitoring, dan analytics.
