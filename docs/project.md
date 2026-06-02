# KojayaPro & Kojayaku - Project Overview

## 📋 Project Information

**Platform Names:**
- **KojayaPro** - Sistem ERP, POS, Inventori, Akuntansi, Simpan Pinjam
- **Kojayaku** - Aplikasi Anggota untuk cek simpanan, pinjaman, poin, transaksi

**Type:** Integrated Cooperative Management System
**Version:** 1.0.0
**Status:** Active Development
**Start Date:** February 2026
**Repository:** https://github.com/johnd-creator/kojaya.git

---

## 🎯 Project Purpose

### **KojayaPro - Sistem ERP Koperasi (Admin/Staff)**

Sistem admin terintegrasi untuk **pengelolaan operasional koperasi lengkap**:

1. **ERP & HRM** - Manajemen karyawan, absensi GPS, cuti, lembur, payroll
2. **Akuntansi & Keuangan** - Invoice, pembayaran, jurnal umum, neraca
3. **POS & Inventori** - Produk kasir, transaksi, manajemen stok
4. **Simpan Pinjam** - Pengajuan pinjaman, persetujuan, angsuran, bunga
5. **Approval Workflow** - Sistem persetujuan multi-level untuk semua transaksi
6. **Laporan Terintegrasi** - Laporan keuangan, operasional, compliance

### **Kojayaku - Aplikasi Anggota (Mobile/Web)**

Aplikasi untuk **anggota koperasi** guna cek dan kelola:

1. **Simpanan** - Cek saldo simpanan, riwayat setoran, bunga
2. **Pinjaman** - Ajukan pinjaman, cek status angsuran, riwayat pinjaman
3. **Poin & Reward** - Cek poin transaksi, tukar poin dengan reward
4. **Transaksi** - Riwayat transaksi di toko koperasi
5. **Profil Anggota** - Update profil, cek status keanggotaan
6. **Notifikasi** - Notifikasi pembayaran, jatuh tempo, dll.

---

## 🌟 Target Users

### **KojayaPro Users (Admin/Staff):**
- **Pengurus Koperasi** - Managing seluruh operasional koperasi
- **Manager HR** - Manajemen karyawan, payroll, absensi
- **Staff Keuangan** - Akuntansi, pembayaran, laporan keuangan
- **Manager Inventori** - Manajemen stok, produk, gudang
- **Kasir/Admin POS** - Transaksi kasir, manajemen penjualan
- **Admin Simpan Pinjam** - Persetujuan pinjaman, manajemen angsuran
- **Project Manager** - Monitoring proyek dan tim
- **Supervisor Maintenance** - Work order dan asset management

### **Kojayaku Users (Anggota Koperasi):**
- **Anggota Koperasi** - Cek simpanan, ajukan pinjaman, lihat poin
- **Calon Anggota** - Daftar anggota, cek status persetujuan
- **Pemegang Saham** - Cek saldo investasi, riwayat keuntungan
- **Pengguna Toko** - Cek transaksi belanja, poin reward

---

## 🏢 Business Context

### **Dual-Platform Architecture:**

KojayaPro dan Kojayaku adalah **dua aplikasi terpisah** yang saling terintegrasi:

```
┌─────────────────────────────────────────────────────┐
│              KojayaPro (Admin/Staff)                  │
│  ┌─────────────────────────────────────────────────┐  │
│  │  ERP • POS • Inventori • Akuntansi                │  │
│  │  • Simpan Pinjam • Approval • Laporan              │  │
│  └─────────────────────────────────────────────────┘  │
│                      ↕ API                           │
│                    Database Shared                     │
└─────────────────────────────────────────────────────┘
                      ↕
┌─────────────────────────────────────────────────────┐
│              Kojayaku (Anggota)                      │
│  ┌─────────────────────────────────────────────────┐  │
│  │  • Profil • Simpanan • Pinjaman                 │  │
│  │  • Poin • Transaksi • Notifikasi                 │  │
│  └─────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

### **Industry Sector:**
- **Koperasi Simpan Pinjam & Serba Usaha** - Primary focus
- **Multi-purpose Cooperative** - Trading, services, savings
- **Geographic:** Indonesia - Full Indonesian compliance

### **Compliance Requirements:**
- **BPJS** - Ketenagakerjaan sosial Indonesia
- **PPh21** - Pajak penghasilan orang pribadi
- **eFaktur** - Faktur pajak elektronik
- **OJK** - Otoritas Jasa Keuangan (untuk simpan pinjam)
- **Work Safety** - MCU (Medical Check Up) compliance

---

## 💼 Key Business Problems Solved

### **KojayaPro (Sistem Admin):**

### 1. **Manual Payroll Calculation**
- **Before:** Manual spreadsheet-based payroll, manual BPJS & PPh21 calculation
- **After:** Automated payroll dengan BPJS, PPh21, overtime calculation
- **Impact:** 90% reduction in payroll processing time

### 2. **Scattered Financial Data**
- **Before:** Data keuangan tersebar di Excel, tidak terintegrasi
- **After:** Sistem akuntansi terintegrasi dengan laporan real-time
- **Impact:** Single source of truth, laporan instan

### 3. **Manual Inventory & POS**
- **Before:** Stok dicatat manual, kesalahan sering terjadi
- **After:** Digital POS dengan auto stock deduction, real-time inventory
- **Impact:** 100% akurasi inventory, prevent kehilangan stok

### 4. **Proses Simpan Pinjam Manual**
- **Before:** Pengajuan kertas, tracking manual, potensi human error
- **After:** Sistem pengajuan online dengan approval workflow, auto-calc angsuran
- **Impact:** Transparansi penuh, proses lebih cepat (hari vs mingguan)

### 5. **Approval Workflow Terpusat**
- **Before:** Email/manual approval, sulit track status
- **After:** Sistem approval terpusat dengan multi-level approval
- **Impact:** 100% traceability, kontrol internal lebih baik

### 6. **Laporan Terfragmentasi**
- **Before:** Laporan dari berbagai sumber, sulit dikonsolidasi
- **After:** Dashboard laporan terintegrasi, export otomatis PDF/Excel
- **Impact:** Pengambilan keputusan lebih cepat dengan data akurat

### **Kojayaku (Aplikasi Anggota):**

### 7. **Kurang Transparansi Simpanan**
- **Before:** Anggota harus datang ke kantor untuk cek saldo simpanan
- **After:** Akses realtime ke saldo simpanan, riwayat, bunga via Kojayaku
- **Impact:** Peningkatan trust anggota, self-service data

### 8. **Proses Pinjaman Rumit**
- **Before:** Formulir kertas, harus datang, proses lama
- **After:** Ajukan pinjaman online via Kojayaku, tracking real-time
- **Impact:** Akses lebih mudah, peningkatan layanan

### 9. **Poin & Reward Tidak Jelas**
- **Before:** Tidak ada program loyalitas, anggota tidak termotivasi
- **After:** Sistem poin dari transaksi, bisa ditukar reward
- **Impact:** Peningkatan transaksi, engagement lebih tinggi

---

## 📊 Project Scope

### **KojayaPro (Sistem Admin) - In Scope ✅:**
- ✅ **ERP & HRM Modules** - Employee, attendance, leaves, overtime
- ✅ **Payroll & Gaji** - Payroll calculation, BPJS, PPh21, THR
- ✅ **POS & Inventori** - Produk, kasir, stok, gudang
- ✅ **Akuntansi** - Invoice, pembayaran, jurnal umum, neraca
- ✅ **Simpan Pinjam** - Pengajuan, persetujuan, angsuran, bunga
- ✅ **Approval System** - Workflow persetujuan multi-level
- ✅ **Laporan** - Keuangan, operasional, simpan pinjam, compliance
- ✅ **Web Admin Panel** - Vue 3 + Inertia.js interface
- ✅ **API Terintegrasi** - 100+ endpoints untuk mobile

### **Kojayaku (Aplikasi Anggota) - Completed ✅:**
- ✅ **Web App** - Responsive web (Vue 3 + Inertia.js)
- ✅ **Profil Anggota** - Data diri, status keanggotaan, dokumen
- ✅ **Simpanan** - Cek saldo, riwayat, bunga, penarikan simpanan
- ✅ **Pinjaman** - Ajukan online, tracking status, riwayat angsuran, restrukturisasi
- ✅ **Poin & Reward** - Cek poin transaksi, tukar poin, reward catalog
- ✅ **Transaksi** - Riwayat belanja di toko koperasi, retur/refund
- ✅ **Notifikasi** - Push notifikasi, WhatsApp notification untuk pembayaran, jatuh tempo
- ✅ **Onboarding** - Flow onboarding untuk anggota baru

### **Integrasi:**
- Kojayaku mengakses API KojayaPro untuk data simpanan, pinjaman, transaksi
- Satu database terpusat untuk kedua sistem
- Role-based access control antara admin dan anggota

---

## 🎨 Design Philosophy

### **KojayaPro (Admin/Staff System):**
1. **Compliance First** - Indonesian regulations built-in (BPJS, PPh21, eFaktur)
2. **Operator-Friendly** - Intuitive UI untuk staff koperasi (bukan tech-savvy)
3. **Comprehensive Dashboard** - Single source of truth untuk seluruh operasional
4. **Audit-Ready** - Complete audit trail untuk semua transaksi keuangan
5. **Approval-Driven** - Multi-level approval workflow untuk kontrol internal
6. **Scalable** - Handle multiple koperasi entities dalam satu sistem

### **Kojayaku (Member App):**
1. **Simple & Friendly** - UI yang mudah dipahami anggota koperasi (beragam usia)
2. **Mobile-First** - Optimal untuk smartphone (Android/iOS)
3. **Real-Time Info** - Data simpanan & pinjaman up-to-date
4. **Transparency** - Riwayat transaksi & keuntungan yang jelas
5. **Gamification** - Poin & reward system untuk engagement
6. **Self-Service** - Anggota bisa mandiri tanpa harus ke kantor

### **Shared Principles:**
- **Data Integration** - Single database untuk kedua sistem
- **API-First** - Kojayaku mengakses KojayaPro via API
- **Secure** - Enkripsi data sensitif, role-based access
- **Performant** - Cepat dan responsif untuk user experience baik

### UX Approach:
- **Modern Dashboard** - Clean, data-rich interface
- **Mobile-First Forms** - Touch-friendly for field operations
- **Progressive Disclosure** - Show relevant info based on user role
- **Consistent Patterns** - Reusable components across modules
- **Fast Performance** - Optimized queries, pagination, caching

---

## 🔄 Development Approach

### Methodology:
- **Agile** - Iterative development with regular releases
- **Test-Driven** - PHPUnit tests for critical business logic
- **Code Quality** - Pint for formatting, ESLint for frontend
- **Documentation** - Comprehensive docs for maintainability

### Team Structure (Ideal):
- Backend Developer (Laravel) - 1-2 persons
- Frontend Developer (Vue 3) - 1 person
- Mobile Developer (Android) - 1-2 persons (future)
- UI/UX Designer - 1 person
- QA Tester - 1 person
- Project Manager - 1 person

---

## 📈 Success Metrics

### Technical KPIs:
- API Response Time < 500ms (95th percentile)
- Page Load Time < 2s
- 99.9% Uptime
- Test Coverage > 70% for critical modules

### Business KPIs:
- Payroll Processing Time: 1 day (vs 7 days manual)
- Attendance Accuracy: > 99%
- Procurement Cycle Time: 3 days (vs 14 days)
- User Adoption: > 80% active users

---

## 🚀 Current Status

### Completed:
- ✅ Core ERP/HRM modules (100%)
- ✅ Web admin panel (100%)
- ✅ API infrastructure (100%)
- ✅ Authentication & authorization (100%)
- ✅ Reporting system (100%)

### In Progress:
- 🔄 Native mobile app development (0% - planned)
- 🔄 Payment gateway production validation (foundation ready)

### Technical Debt:
- ~~API documentation (OpenAPI/Swagger) needed~~ ✅ OpenAPI snapshot available at `docs/openapi.json`
- ~~Token expiration for Sanctum~~ ✅ Configured with 30-day expiration
- ~~Rate limiting for API endpoints~~ ✅ 3-tier throttle implemented
- ~~Automated testing coverage expansion~~ ✅ 108 test files, 53 factories
- Vendor status enum (SUSPENDED/BLACKLISTED) — pending
- Loan status enum (WrittenOff) — pending
- API Resource for Savings — pending

---

## 📱 Kojayaku - Aplikasi Anggota Koperasi

### **Platform Overview:**
**Kojayaku** adalah aplikasi **member-facing** untuk anggota koperasi guna:
- Cek saldo simpanan dan riwayat transaksi
- Ajukan pinjaman secara online
- Cek poin dan tukar dengan reward
- Lihat riwayat transaksi belanja
- Update profil anggota
- Terima notifikasi penting

### **Target Users Kojayaku:**
- **Anggota Aktif** - Cek simpanan, ajukan pinjaman
- **Calon Anggota** - Daftar online, upload dokumen
- **Pemegang Saham** - Cek investasi, SHU
- **Pengguna Toko** - Cek transaksi, poin belanja
- **Pengurus** (view-only) - Monitoring operasional

### **Fitur Utama Kojayaku:**

#### **💰 Simpanan**
- Real-time saldo simpanan
- Riwayat setoran simpanan
- Sertifikat deposit/SHU
- Bunga simpanan (实时更新)
- Export riwayat simpanan (PDF)

#### **💸 Pinjaman**
- Ajukan pinjaman online
- Tracking status pengajuan (pending → approved → rejected → disbursed)
- Cicilan & angsuran real-time
- Kalkulator simulasi cicilan
- Notifikasi jatuh tempo
- Riwayat pinjaman

#### **🎁 Poin & Reward**
- Cek poin dari transaksi
- Katalog reward (barang, diskon, layanan)
- Tukar poin dengan reward
- Riwayat poin masuk/keluar

#### **🛒 Transaksi**
- Riwayat belanja di toko koperasi
- Detail transaksi (tanggal, items, total)
- Struk belanja digital
- Filter berdasarkan tanggal, kategori

#### **👤 Profil**
- Data diri anggota
- Status keanggotaan
- Upload dokumen (KTP, KK, dll)
- Cek status keanggotaan (aktif/non-aktif)
- Update kontak & alamat

#### **🔔 Notifikasi**
- Pembayaran diterima
- Pinjaman disetujui/ditolak
- Jatuh tempo pembayaran
- SHU dibagikan
- Info promo dan event koperasi

---

## 🔄 Integrasi KojayaPro ↔ Kojayaku

### **API Integration:**
```
Kojayaku → KojayaPro (via API):
- GET /api/v1/members/{id} - Cek profil anggota
- GET /api/v1/dues/invoices - Cek tagihan iuran
- GET /api/v1/savings/ledger - Cek saldo simpanan
- POST /api/v1/loans/apply - Ajukan pinjaman
- GET /api/v1/transactions - Riwayat transaksi
- POST /api/v1/rewards/redeem - Tukar poin

KojayaPro → Kojayaku:
- Push notifikasi via Firebase/WhatsApp API
- Update status pinjaman
- Inform tagihan baru
- Notifikasi pembayaran diterima
```

### **Shared Database:**
- **cooperative_members** - Data anggota (shared)
- **cooperative_savings** - Simpanan anggota (Kojayaku read-only)
- **loans** - Data pinjaman (Kojayaku view status)
- **pos_transactions** - Transaksi toko (Kojayaku read history)
- **points** - Poin anggota (Kojayaku claim)

---

## 📚 Related Documentation

- **[Architecture](./architecture.md)** - System design & tech stack details
- **[API Reference](./api.md)** - Complete API documentation
- **[Development Plan](./plan.md)** - Roadmap & sprint planning
- **[Decisions Log](./decisions.md)** - Architecture decision records
- **[Development Log](./log.md)** - Chronological development history

---

## 📞 Contact & Support

**Project Lead:** Johnd Creator
**Email:** fauzi.ardiyanto@gmail.com
**GitHub:** https://github.com/johnd-creator/kojaya

**Development Environment:**
- Local: http://localhost:8000
- Production: (to be configured)

---

*Last Updated: May 17, 2026*
