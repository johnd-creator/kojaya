---
title: Persetujuan Akhir Pinjaman oleh Pengurus
slug: pengurus-loan-approval
summary: Kewenangan approval akhir dan pencatatan keputusan rapat.
category: Pengurus Koperasi · Tata Kelola
module: loans
roles:
  - pengurus_koperasi
permissions:
  - approve_cooperative_loan
permission_mode: all
route_names:
  - cooperative.loans.index
  - cooperative.loans.show
  - cooperative.loans.approve
  - cooperative.loans.disburse
  - cooperative.loans.reject
  - audit-logs
risk_level: high
screenshot_entries:
  - pengurus-loan-approval-desktop
related_articles:
  - manajer-loan-review
  - pengurus-shu-and-governance
last_reviewed_commit: 20c86960
status: published
sort_order: 10
---

# Persetujuan Akhir Pinjaman oleh Pengurus

Pengurus Koperasi adalah peran tertinggi di koperasi
(setingkat System Admin di lingkup koperasi, lihat
`app/Enums/RoleExperience.php`). Permission
`approve_cooperative_loan` menjadi kunci masuk ke endpoint
`POST` `route('cooperative.loans.approve')`.

## Alur approval

1. Buka **Pinjaman** → `route('cooperative.loans.index')` dengan
   filter `status=MANAGER_APPROVED`.
2. Buka detail → `route('cooperative.loans.show')` (read-only).
3. Keputusan:
   - **Setujui** → `route('cooperative.loans.approve')`; status
     `APPROVED`; trigger pencairan via
     `route('cooperative.loans.disburse')` dan penjadwalan
     angsuran.
   - **Tolak** → `route('cooperative.loans.reject')`; status
     `REJECTED` final.
4. Tambahkan catatan di `loans.notes` bila diperlukan
   (lihat `App\Models\Loan`).

> **Catatan:** saat ini aplikasi tidak menyimpan kuorum atau
> referensi rapat pleno. Tidak ada model `ApprovalMinute` atau
> `RatMinute` di codebase; keputusan hanya dicatat sebagai baris
> `AuditLog` dan field `notes` pada Loan.

## Pencatatan

- `AuditLog` otomatis merekam setiap keputusan (lihat
  `tests/Feature/AuditLogTest.php`).
- Laporan triwulan: aggregate dari `Loan` dengan
  status `APPROVED` diekspor lewat
  `route('cooperative.reports.index')`.
