# In-App User Guide — Content Correction Audit

Audit terhadap seluruh artikel dokumentasi pengguna pada branch
`feature/in-app-user-guide`. Setiap klaim dalam artikel
diverifikasi terhadap source code aktual: route, controller,
Form Request, policy, permission, service, enum status, dan UI.

## 1. Ringkasan keputusan

| # | Klaim lama | Keputusan | Bukti |
|---|---|---|---|
| 1 | Anggota menggunakan `cooperative.loans.create` untuk mengajukan pinjaman | **Dihapus** — Anggota hanya dapat mengakses `member.loans.store`. | `routes/web.php:152`, `MemberPortalController.php:481` |
| 2 | Status pinjaman: `submitted`, `manager_review`, `chairman_approval` | **Dihapus** — enum aktual pada `app/Enums/LoanStatus.php`: APPLIED, MANAGER_APPROVED, APPROVED, REJECTED, ACTIVE, PAID_OFF, DEFAULTED, WRITTEN_OFF. Artikel sekarang menggunakan label Bahasa Indonesia. | `app/Enums/LoanStatus.php` |
| 3 | Anggota bisa membatalkan aplikasi | **Gap** — tidak ada route `member.loans.cancel`. | `routes/web.php` |
| 4 | Bukti pembayaran manual (unggah foto) | **Dihapus** — UI anggota menggunakan `MidtransPaymentDialog` dengan kanal QRIS, Virtual Account, dan E-Wallet. | `resources/js/components/Kojayaku/MidtransPaymentDialog.vue:172-192`, `resources/js/pages/Kojayaku/Savings.vue:483` |
| 5 | Metode pembayaran angsuran: "tunai, transfer bank, atau QRIS" | **Diperbaiki** — kanal aktual adalah QRIS, Virtual Account, E-Wallet. | `MemberPortalController.php:648`, `MidtransPaymentDialog.vue:172-192` |
| 6 | SLA "diproses maksimal 1x24 jam" / "diproses pada jam kerja" | **Dihapus** — tidak ada implementasi atau konfigurasi SLA. | N/A |
| 7 | SLA "Pencairan tidak bergerak lebih dari 3 hari" | **Dihapus** — tidak ada SLA pencairan. | N/A |
| 8 | Manajer melakukan "final approval" | **Dihapus** — Manajer hanya melakukan review (`review_cooperative_loan`), bukan approval final. | `LoanController.php:140-147`, `RolePermissionSeeder.php` |
| 9 | "Penjadwalan ulang" pinjaman oleh Admin | **Dihapus** — tidak ada fitur reschedule. | `LoanController.php` |
| 10 | Catatan approval Pengurus "dapat dilihat oleh anggota" | **Diperbaiki** — anggota hanya melihat `loan.notes` dan `rejection_reason`, bukan `ApprovalLog` detail. | `resources/js/pages/Kojayaku/Loans.vue:1428-1436`, `MemberPortalController.php:481` |
| 11 | Permission internal ditampilkan kepada pengguna | **Dihapus dari body** — permission hanya di frontmatter, tidak di body artikel pengguna. | Semua artikel |
| 12 | Tombol "Tutup Periode" SHU | **Diperbaiki** — tombol aktual adalah "Tutup". | `resources/js/pages/Cooperative/Shu/Index.vue:221` |
| 13 | Tombol "Minta Revisi" SHU pada UI | **Dihapus** — tidak ada tombol revisi pada UI SHU. Route `shu.request-revision` ada tetapi API-only. | `resources/js/pages/Cooperative/Shu/Index.vue` |
| 14 | Distribusi poin otomatis setelah SHU ditutup | **Dihapus** — `AnnualShuDistributionService` melakukan alokasi SHU berdasarkan skor (bulan aktif + iuran wajib), bukan distribusi poin. | `app/Services/Cooperative/AnnualShuDistributionService.php` |
| 15 | Pengurus dapat membuka Log Audit | **Gap** — Pengurus tidak memiliki `view_audit_logs`. | `RolePermissionSeeder.php` |
| 16 | Kuorum pinjaman, risalah RAT, AD/ART | **Gap** — tidak ada model `ApprovalMinute`/`RatMinute`. | Codebase search |
| 17 | "laporan triwulan", "audit eksternal", "rapat triwulan" | **Dihapus** — tidak ada fitur triwulan atau audit eksternal. | N/A |
| 18 | Status pembayaran VOID/REJECTED | **Dihapus** — CooperativePayment hanya memiliki PENDING dan APPROVED. | `CooperativePaymentController.php:34,108,135` |
| 19 | "Tanggal transfer idealnya kurang dari 7 hari" | **Dihapus** — tidak ada aturan tanggal pada implementasi. | `CooperativePaymentController.php` |
| 20 | Contextual help menggunakan route POST (`member.payments.intent`) | **Diperbaiki** — diganti ke `member.savings` (GET page). | `resources/docs/user-guide/contextual-help.json` |

## 2. Status implementasi

### 2.1 Fitur yang diverifikasi dan didokumentasikan

- Pengajuan pinjaman anggota: `member.loans.store` → `MemberPortalController::applyLoan`
- Pembayaran iuran: QRIS/VA/E-Wallet via `MidtransPaymentDialog`
- Review pinjaman Manajer: `cooperative.loans.review` → `LoanController::review`
- Approval final Pengurus: `cooperative.loans.approve` → `LoanController::approve`
- Pencairan pinjaman: `cooperative.loans.disburse` → `LoanController::disburse`
- SHU: preview + tutup periode via `AnnualShuController`
- ApprovalLog tersimpan pada setiap aksi review/approve/reject/disburse

### 2.2 Gap yang dikonfirmasi

| Gap | Alasan |
|---|---|
| Pembatalan aplikasi oleh anggota | Route `member.loans.cancel` tidak ada |
| Reschedule pinjaman | Tidak ada fitur reschedule |
| Log Audit untuk Pengurus | `view_audit_logs` tidak diberikan kepada Pengurus |
| Halaman Pengecualian untuk Pengurus | `view_balance_sheet` tidak diberikan |
| Pencatatan AD/ART | Tidak ada modul terstruktur |
| Pencatatan risalah RAT | Tidak ada model `RatMinute` |
| Tombol revisi SHU pada UI | Route ada tetapi tidak ada tombol UI |
| Distribusi poin otomatis | Tidak ada mekanisme distribusi poin pasclose |

## 3. Tidak ada di codebase

- `app/Models/LoanApplication.php`
- `app/Models/ApprovalMinute.php`
- `app/Models/RatMinute.php`
- `app/Models/LoanApplicationReviewWindow.php`
- Tabel `loan_application_review_windows`
- Tabel `approval_minutes`
- Tabel `rat_minutes`
- Preset laporan "RAT", "AnnualReport", "Pengurus"
- Fitur reschedule/restructure pinjaman
- SLA review/pencairan yang terkonfigurasi

## 4. Dependency audit

| Dependency | Justifikasi |
|---|---|
| `symfony/yaml ^7.0` | Parsing YAML frontmatter artikel — `app/Documentation/Article.php:46` |
| `isomorphic-dompurify ^3.19.0` | Sanitasi HTML pada Markdown renderer — `resources/js/components/Documentation/markdown-renderer.ts:1` |
| `marked ^18.0.9` | Render Markdown ke HTML — `resources/js/components/Documentation/markdown-renderer.ts:2` |
| `@laravel/vite-plugin-wayfinder ^0.1.7` | Bump constraint agar cocok dengan versi terinstal — `vite.config.ts:1` |

## 5. Catatan pelaksanaan

- Audit dilakukan terhadap source code pada HEAD branch `feature/in-app-user-guide`.
- Setiap status, tombol, dan alur diverifikasi terhadap UI Vue aktual.
- Enum kode teknis (APPLIED, MANAGER_APPROVED, dll.) dihapus dari body artikel dan diganti dengan label Bahasa Indonesia yang tampil pada UI.
- Permission internal hanya disimpan di frontmatter, tidak ditampilkan di body artikel pengguna.
- Validator memeriksa: route validity, permission validity, contextual help GET-only, inventory summary auto-computation, screenshot manifest, markdown links, stale commits.
