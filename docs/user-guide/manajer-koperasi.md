# Panduan Pengguna — Manajer Koperasi

> **Target peran:** `Manajer Koperasi` (Spatie role: `Manajer Koperasi`)
> **Permission utama:** `view_cooperative_member`, `manage_cooperative_dues`, `manage_cooperative_payment`, `view_cooperative_loan`, `manage_cooperative_loan`, `review_cooperative_loan`, `access_cooperative_pos`, `view_cooperative_report`, `manage_cooperative_points`, `manage_cooperative_rewards`, `manage_cooperative_redemption`, `manage_cooperative_shu`, `manage_cooperative_loan_types`, `manage_pos_categories`, `manage_pos_products`, `view_pos_reports`, `manage_pos_shu`, `view_cooperative_ledger`, `manage_cooperative_ledger`, `manage_cooperative_opening_balance`, `review_cooperative_resignation`, `approve_pos_void`, `view_store_credit`, `manage_store_credit`, `manage_store_credit_limit`, `cashier_store_credit`, `approve_store_credit_transfer`, `adjust_store_credit`, `report_store_credit`.
> **Kewenangan utama:** tinjauan pertama aplikasi pinjaman (`review_cooperative_loan`),俯瞰 operasional, eskalasi ke Pengurus.

Sumber otoritatif artikel Markdown-versi: [`Database\Seeders\DocumentationArticleSeeder`](../database/seeders/DocumentationArticleSeeder.php) dengan `target_role = manajer_koperasi`.

## 1. Tinjauan Aplikasi Pinjaman oleh Manajer

Manajer Koperasi adalah peran tertinggi kedua di koperasi dengan
permission `review_cooperative_loan` (lihat
`app/Enums/PermissionEnum.php` + `RolePermissionSeeder`). Tinjauan
dipicu oleh `POST` ke `route('cooperative.loans.review')`.

### Alur review

1. Masuk ke **Pinjaman** → `route('cooperative.loans.index')`
   dengan filter `status=manager_review`.
2. Tinjau **5C**: Character, Capacity, Capital, Collateral,
   Condition. Data tersedia di `route('cooperative.loans.show')`.
3. Pilih keputusan lewat endpoint:
   - **Setujui untuk Pengurus** →
     `route('cooperative.loans.approve')` (jika Manajer juga
     memiliki permission `approve_cooperative_loan`) atau
     `route('cooperative.loans.review')` untuk meneruskan ke
     `chairman_approval`.
   - **Minta Revisi** → `route('cooperative.loans.review')` dengan
     catatan di `loan_applications.notes`.
   - **Tolak** → `route('cooperative.loans.reject')`; status
     `rejected` final.
4. Pastikan ledger simpanan terkini sebelum menyetujui; gunakan
   `route('cooperative.ledger.index')` sebagai referensi.

### Kuorum & batas waktu

Review harus selesai ≤ 3 hari kerja sejak `submitted`. SLA
dipantau lewat `LoanApplicationReviewWindow` (lihat
`App\Models\LoanApplication`).

## 2. Pemantauan Keuangan Harian

### Dashboard

- Ringkasan harian:
  `route('cooperative.operator.dashboard')` (widget Manajer).
- NPL: badge di pojok kanan atas widget.
- Angsuran tertunda: taut ke
  `route('cooperative.loans.index')` dengan filter
  `status=overdue`.

### Tindakan korektif

- Angsuran macet ≥ 30 hari → tugaskan Admin Koperasi untuk
  follow-up lewat `route('cooperative.payments.index')`.
- Setoran kasir harian → rekonsiliasi di
  `route('cooperative.pos.closings.index')`; cocokan dengan
  `route('cooperative.ledger.index')`.
- Pencairan yang belum cair di
  `route('cooperative.loans.index')` (filter `status=approved`)
  → hubungi Admin untuk status transfer lewat
  `route('cooperative.loans.disburse')`.

### Eskalasi

Permasalahan hukum (sengketa, audit eksternal) → teruskan ke
Pengurus via laporan triwulan pada
`route('cooperative.reports.index')`.
