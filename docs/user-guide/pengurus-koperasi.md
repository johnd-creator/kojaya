# Panduan Pengguna — Pengurus Koperasi

> **Target peran:** `Pengurus Koperasi` (Spatie role: `Pengurus Koperasi`)
> **Permission utama:** Kunci `approve_cooperative_loan`, `manage_cooperative_shu`, `manage_cooperative_settings`, `view_audit_logs`, `manage_cooperative_opening_balance`, `approve_cooperative_opening_balance`, `void_cooperative_opening_balance`, `view_cooperative_all`, plus semua permission operasional koperasi.
> **Kewenangan utama:** persetujuan akhir pinjaman, SHU tahunan, AD/ART, audit internal.

Sumber otoritatif artikel Markdown-versi: [`Database\Seeders\DocumentationArticleSeeder`](../database/seeders/DocumentationArticleSeeder.php) dengan `target_role = pengurus_koperasi`.

## 1. Persetujuan Akhir Pinjaman oleh Pengurus

Pengurus Koperasi adalah peran tertinggi di koperasi
(setingkat System Admin di lingkup koperasi, lihat
`app/Enums/RoleExperience.php` + `docs/proses_bisnis/roles.md`).
Permission `approve_cooperative_loan` menjadi kunci masuk ke
endpoint `POST` `route('cooperative.loans.approve')`.

### Alur approval

1. Buka **Pinjaman** → `route('cooperative.loans.index')` dengan
   filter `status=chairman_approval`.
2. Buka detail → `route('cooperative.loans.show')` (read-only).
3. Keputusan:
   - **Setujui** → `route('cooperative.loans.approve')`; status
     `approved`; trigger pencairan via
     `route('cooperative.loans.disburse')` dan penjadwalan
     angsuran.
   - **Tunda** → kembali ke Manajer dengan catatan di
     `loan_applications.notes`.
   - **Tolak** → `route('cooperative.loans.reject')`; status
     `rejected` final.
4. Setiap keputusan wajib menyertakan referensi rapat/risalah
   di `ApprovalMinute`.

### Quorum

- Pinjaman ≤ Rp 500 juta: cukup 1 Pengurus + 1 Manajer.
- Pinjaman > Rp 500 juta: minimum 2 Pengurus + 1 Manajer.
- Pinjaman > Rp 2 miliar: wajib keputusan rapat pleno, simpan
  scan risalah di lampiran `ApprovalMinute`.

### Pencatatan

- `AuditLog` otomatis merekam setiap keputusan (lihat
  `tests/Feature/AuditLogTest.php`).
- Laporan triwulan: aggregate dari `LoanApplication` dengan
  status `approved` diekspor lewat
  `route('cooperative.reports.index')` → preset **Pengurus**.

## 2. SHU, Tata Kelola, dan Rapat Anggota

### SHU tahunan

- Alokasi: `route('cooperative.shu.index')` → lihat preset
  **RAT**. Perhitungan mengikuti
  `App\Services\ShuDistributionService` (lihat
  `tests/Unit/Finance/Shu*Test.php`).
- Penutupan buku: `route('cooperative.shu.close')`. Penyaluran
  ke anggota: otomatis dilakukan setelah RAT ditutup; lihat juga
  `route('cooperative.points.index')` (perlu
  `manage_cooperative_points`).
- Permintaan revisi periode:
  `route('cooperative.shu.request-revision')`.

### RAT

- Persiapan dokumen:
  `route('cooperative.reports.index')` → preset **AnnualReport**.
- Notifikasi RAT ke anggota: outbox bertipe `rat_invitation`
  (lihat `app/Services/Notifications`).
- Hasil RAT disimpan ke `RatMinute` dan diarsipkan via
  `AuditLog` agar bisa diaudit oleh pengurus berikutnya.

### Audit internal

- Audit internal: baca log di
  `route('audit-logs')` (perlu permission `view_audit_logs`).
- Pengawasan operasional: manfaatkan
  `route('exceptions.index')` untuk anomali lintas modul.
- Perubahan AD/ART: lewat modul pengaturan koperasi yang
  dilindungi permission `manage_cooperative_settings`
  (`can:manage_cooperative_settings` di
  `App\Providers\AuthServiceProvider`).
