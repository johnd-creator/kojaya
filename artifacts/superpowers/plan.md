# Plan: Project Mobilization & Safety

## Goal
Mengimplementasikan manajemen rekruitmen tim proyek (Mobilization) dan manajemen dokumen perizinan (Safety) pada fitur Projects sesuai PRD 4.2.

## Assumptions
- Modul dasar `Project`, `ProjectTask`, dan `ProjectTeam` sudah berjalan.
- Karyawan (Employees) sudah tersinkronisasi dan tersedia untuk di-assign ke dalam tim proyek.

## Plan

### Step 1: Mobilization Fields on ProjectTeam
- **Files**: `database/migrations/[timestamp]_add_mobilization_fields_to_project_team_table.php`, `app/Models/ProjectTeam.php`
- **Change**: Tambahkan kolom `status` (ENUM: RECRUITMENT, SCREENING, MCU, ONBOARDING, PLACED), `has_ppe` (boolean), dan `has_uniform` (boolean). Update `$fillable` di model.
- **Verify**: `php artisan migrate:status` dan jalankan Tinker `App\Models\ProjectTeam::first()`.

### Step 2: ProjectDocument Model & Migration
- **Files**: `database/migrations/[timestamp]_create_project_documents_table.php`, `app/Models/ProjectDocument.php`, `app/Models/Project.php`
- **Change**: Buat tabel untuk perizinan proyek dengan field `project_id`, `name`, `type` (SIKA, PERMIT, DRAWING, OTHER), `file_path`, `expiry_date`, `status` (VALID, EXPIRED). Tambahkan relasi `documents()` di model `Project`.
- **Verify**: `php artisan migrate:status`

### Step 3: Backend Logic for Teams & Documents
- **Files**: `app/Http/Controllers/ProjectTeamController.php`, `app/Http/Controllers/ProjectDocumentController.php` (new), `routes/web.php`
- **Change**: Tambahkan controller untuk mengelola upload file Sika/Permit (`store`, `destroy`). Update `ProjectTeamController` agar bisa update status & flag PPE. Daftarkan route baru.
- **Verify**: Pastikan route `/projects/{project}/documents` terdaftar via `php artisan route:list`.

### Step 4: Frontend UI Updates (Project Detail)
- **Files**: `resources/js/pages/Project/Show.vue`
- **Change**: 
  - Update tabel anggota tim untuk menampilkan Badge `status` dan icon check untuk APD (`has_ppe`, `has_uniform`).
  - Tambahkan tab/section "Documents" yang memuat Form Upload dan Tabel Dokumen dengan indikator `expiry_date`.
  - Tampilkan statistik manpower sederhana (Target vs Placed).
- **Verify**: Buka halaman detail Project di browser, verifikasi UI tab Teams dan Documents berfungsi.

## Risks & mitigations
- **File Storage**: Upload dokumen bisa memnuhi disk. Mitigasi: Validasi limit ukuran file (max 5MB) dan ekstensi (PDF/JPG) di controller.
- **Data Consistency**: Update status secara massal di UI bisa rawan jika tidak divalidasi. Mitigasi: Backend harus memvalidasi ID employee dan memastikan transisi status logis.

## Rollback plan
- Jika UI komponen atau API dokumen gagal, kembalikan `Project/Show.vue` ke versi sebelumnya (menggunakan Git) dan hapus record migrasi table dokumen.
