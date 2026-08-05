# In-App User Guide — Content Correction Audit

Tujuan dokumen: mencatat hasil audit terhadap artikel yang sudah ada
pada commit `20c86960` (head branch `feature/in-app-user-guide`)
terhadap source code pada commit yang sama. Setiap klaim lama harus
mempunyai bukti implementasi sebelum dapat dipertahankan.

Berkas yang di-audit:

- `docs/user-guide/anggota.md`
- `docs/user-guide/admin-koperasi.md`
- `docs/user-guide/manajer-koperasi.md`
- `docs/user-guide/pengurus-koperasi.md`
- `database/seeders/DocumentationArticleSeeder.php`
- `app/Models/DocumentationArticle.php`
- `app/Http/Controllers/Documentation/DocumentationController.php`
- `app/Policies/DocumentationPolicy.php`
- `resources/js/pages/Documentation/Index.vue`
- `resources/js/pages/Documentation/Show.vue`

## 1. Ringkasan keputusan

| # | Klaim lama | Keputusan | Bukti |
|---|---|---|---|
| 1 | Anggota menggunakan `route('cooperative.loans.create')` untuk mengajukan pinjaman | **Hapus** — Anggota hanya dapat mengakses `member.loans.store`. `cooperative.loans.create` di-gate oleh `can:view_cooperative_loan` + `LoanPolicy::manage` yang tidak diizinkan Anggota. | `routes/web.php:288-299`, `app/Http/Controllers/Cooperative/LoanController.php:80-91`, `app/Policies/LoanPolicy.php:25-29` |
| 2 | Status aplikasi pinjaman anggota: `submitted`, `manager_review`, `chairman_approval`, `approved` | **Hapus `submitted/manager_review/chairman_approval`** — enum aktual pada `app/Enums/LoanStatus.php` adalah `APPLIED`, `MANAGER_APPROVED`, `APPROVED`, `REJECTED`, `ACTIVE`, `PAID_OFF`, `DEFAULTED`, `WRITTEN_OFF`. | `app/Enums/LoanStatus.php` |
| 3 | `route('cooperative.loans.disburse')` adalah endpoint pencairan standar | **Pertahankan** — route ada di `routes/web.php:297`. | `routes/web.php:297` |
| 4 | Anggota bisa membatalkan aplikasi via tombol **Batalkan** di `cooperative.loans.show` | **Hapus** — tidak ada route `member.loans.cancel` atau `cooperative.loans.cancel`; `MemberPortalController@applyLoan` (line 492) tidak memiliki logika pembatalan. | `MemberPortalController.php:492-508` |
| 5 | Bukti pembayaran manual anggota menuju `cooperative.dues.index` (via `cooperative.dues.mark-paid`) | **Hapus** — bukti pembayaran anggota melalui `member.payments.proof` menciptakan `CooperativePayment` berstatus `PENDING` yang diproses Admin di `cooperative.payments.index` → `cooperative.payments.approve` / `cooperative.payments.bulk-approve`. `cooperative.dues.mark-paid` adalah perekaman kasir, bukan antrean bukti. | `MemberPortalController.php:609-634`, `CooperativePaymentController.php:24-179`, `CooperativeDuesController.php:118-165` |
| 6 | `LoanApplicationReviewWindow` memantau SLA 3 hari kerja | **Hapus** — class/model `LoanApplicationReviewWindow` tidak ada di codebase. `App\Models\LoanApplication` juga tidak ada; entity aktual adalah `App\Models\Loan`. | `app/Models/Loan.php` (eksis), `app/Models/LoanApplication.php` (tidak ada) |
| 7 | Kuorum pinjaman: ≤ Rp 500 juta cukup 1+1, > Rp 500 juta butuh 2+1, > Rp 2 miliar wajib rapat pleno | **Hapus** — tidak ada model `ApprovalMinute`/`RatMinute`, tidak ada field quorum. Workflow di `route('cooperative.loans.approve')` adalah endpoint approval tunggal. | `app/Models/ApprovalMinute.php` (tidak ada), `app/Models/RatMinute.php` (tidak ada) |
| 8 | Risalah keputusan disimpan di `ApprovalMinute` | **Hapus** — field `notes` pada Loan adalah satu-satunya pencatatan. | `app/Models/Loan.php` (cari `notes`) |
| 9 | Preset laporan **RAT**, **AnnualReport**, **Pengurus** | **Hapus** — `CooperativeReportController` tidak memiliki mekanisme preset; hanya `index`, `summary`, `sales`, `nplAging`. | `app/Http/Controllers/Cooperative/CooperativeReportController.php` |
| 10 | `ShuDistributionService` adalah implementasi perhitungan SHU | **Pertahankan sebagai roadmap** — artikel Pengurus menjelaskan alur preset yang tidak ada. Ubah menjadi penjelasan langkah yang benar-benar ada di `AnnualShuController`. | `app/Http/Controllers/Cooperative/AnnualShuController.php` |
| 11 | `route('cooperative.shu.close')` menutup SHU | **Pertahankan** — route ada di `routes/web.php:320`. | `routes/web.php:320` |
| 12 | `route('cooperative.shu.request-revision')` | **Pertahankan** — route ada di `routes/web.php:321`. | `routes/web.php:321` |
| 13 | `route('audit-logs')` (nama eksak) | **Pertahankan** — `routes/web.php:34`. | `routes/web.php:34` |
| 14 | `route('exceptions.index')` | **Pertahankan** — `routes/web.php:484`. | `routes/web.php:484` |
| 15 | `LoanApplicationRequest` | **Hapus nama class** — aktualnya `StoreMemberLoanApplicationRequest` (untuk Anggota) atau `Cooperative\StoreLoanRequest` (untuk Admin). | `app/Http/Requests/StoreMemberLoanApplicationRequest.php`, `app/Http/Requests/Cooperative/StoreLoanRequest.php` |
| 16 | `LoanTypeRequest` | **Hapus nama class** — aktualnya `Cooperative\StoreLoanTypeRequest`. | `app/Http/Requests/Cooperative/StoreLoanTypeRequest.php` |
| 17 | `LoanTypePolicy` | **Hapus** — `LoanTypeController` menggunakan inline `authorizeLoanManagement()` bukan policy. | `app/Http/Controllers/Cooperative/LoanTypeController.php:56` |
| 18 | `route('cooperative.loan-types.store')` tidak memerlukan `cooperative.loan-types.create` | **Pertahankan** — sesuai dengan kode. | `app/Http/Controllers/Cooperative/LoanTypeController.php` |
| 19 | `manage_cooperative_loan_types` permission | **Pertahankan** — ada di `PermissionEnum::COOPERATIVE_LOAN_TYPES_MANAGE`. | `app/Enums/PermissionEnum.php` |
| 20 | `approve_cooperative_loan` (kunci Pengurus) | **Pertahankan** — `PermissionEnum::COOPERATIVE_LOAN_APPROVE`. | `app/Enums/PermissionEnum.php` |
| 21 | `manage_cooperative_shu` (kunci Pengurus) | **Pertahankan** — `PermissionEnum::COOPERATIVE_SHU_MANAGE`. | `app/Enums/PermissionEnum.php` |
| 22 | `view_audit_logs` | **Pertahankan** — `PermissionEnum::AUDIT_LOGS_VIEW`. | `app/Enums/PermissionEnum.php` |
| 23 | `manage_cooperative_settings` | **Pertahankan** — `PermissionEnum::COOPERATIVE_SETTINGS_MANAGE`. | `app/Enums/PermissionEnum.php` |
| 24 | `view_cooperative_all` | **Pertahankan** — `PermissionEnum::COOPERATIVE_VIEW_ALL`. | `app/Enums/PermissionEnum.php` |
| 25 | `manage_cooperative_opening_balance` Admin | **Pertahankan** — `PermissionEnum::COOPERATIVE_OPENING_BALANCE_MANAGE`. | `app/Enums/PermissionEnum.php` |
| 26 | `approve_cooperative_opening_balance` | **Pertahankan** — `PermissionEnum::COOPERATIVE_OPENING_BALANCE_APPROVE`. | `app/Enums/PermissionEnum.php` |
| 27 | `void_cooperative_opening_balance` | **Pertahankan** — `PermissionEnum::COOPERATIVE_OPENING_BALANCE_VOID`. | `app/Enums/PermissionEnum.php` |
| 28 | Markdown `prose` class applied to `whitespace-pre-line` | **Hapus/raw** — `{{ }}` Vue interpolation HTML-escape konten, namun tidak ada parser Markdown. Hasilnya adalah raw text. | `resources/js/pages/Documentation/Show.vue:76-81` |

## 2. Bukti source code spesifik

### 2.1 Loan workflow anggota

```php
// routes/web.php (lines 151-154)
Route::get('loans', [MemberPortalController::class, 'loans'])->name('loans');
Route::post('loans', [MemberPortalController::class, 'applyLoan'])
    ->name('loans.store');
Route::post('loans/installments/payment-intent', [MemberPortalController::class, 'createLoanPaymentIntent'])
    ->name('loans.installments.payment-intent');
Route::get('loans/payment-intents/{intent}/status', [MemberPortalController::class, 'loanPaymentIntentStatus'])
    ->name('loans.payment-intents.status');
```

```php
// app/Http/Controllers/MemberPortalController.php (lines 477-508)
public function loans(...)
public function applyLoan(StoreMemberLoanApplicationRequest $request)
{
    $member = $request->user()->cooperativeMember;
    $organizationId = $this->resolveOrganizationIdForMember($member);
    $loan = $this->loanService->apply(
        cooperativeMemberId: $member->getKey(),
        organizationId: $organizationId,
        payload: $request->validated(),
    );
    return back()->with('success', ...);
}
```

### 2.2 LoanStatus enum aktual

```php
// app/Enums/LoanStatus.php
case Applied = 'APPLIED';
case ManagerApproved = 'MANAGER_APPROVED';
case Approved = 'APPROVED';
case Rejected = 'REJECTED';
case Active = 'ACTIVE';
case PaidOff = 'PAID_OFF';
case Defaulted = 'DEFAULTED';
case WrittenOff = 'WRITTEN_OFF';
```

### 2.3 Bukti pembayaran anggota

```php
// app/Http/Controllers/MemberPortalController.php (lines 609-634)
public function uploadPaymentProof(UploadPaymentProofRequest $request)
{
    $payment = $this->cooperativePaymentService->recordMemberProof(
        member: $request->user()->cooperativeMember,
        invoice: $invoice,
        file: $request->file('proof'),
        // ...
    );
    // Co-operativePayment dengan status 'PENDING' tercipta di sini.
}
```

```php
// app/Http/Controllers/Cooperative/CooperativePaymentController.php (lines 24-179)
public function index(...)
public function approve(...)
public function bulkApprove(...)
```

### 2.4 Tidak ada satupun dari berikut di codebase

- `app/Models/LoanApplication.php`
- `app/Models/ApprovalMinute.php`
- `app/Models/RatMinute.php`
- `app/Models/LoanApplicationReviewWindow.php`
- `app/Http/Requests/LoanApplicationRequest.php`
- `app/Http/Requests/LoanTypeRequest.php`
- `app/Policies/LoanTypePolicy.php`
- Tabel `loan_application_review_windows`
- Tabel `approval_minutes`
- Tabel `rat_minutes`
- Preset laporan "RAT", "AnnualReport", "Pengurus"

## 3. Perubahan yang akan dilakukan

### 3.1 Teks baru

| Topik | Teks baru |
|---|---|
| Pinjaman anggota | Lihat `docs/user-guide/anggota/loans.md` (Markdown frontmatter-based artikel dengan workflow sesuai `member.loans.store` + `LoanStatus` enum aktual). |
| Bukti pembayaran | Lihat `docs/user-guide/anggota/payments.md` (menggunakan `cooperative.payments.index` → `cooperative.payments.approve`). |
| SHU | Lihat `docs/user-guide/pengurus-koperasi/shu-and-governance.md` (berdasarkan `AnnualShuController`). |

### 3.2 Gap

| Gap | Status |
|---|---|
| Pembatalan aplikasi oleh anggota | **Belum diimplementasikan** — `member.loans.cancel` tidak ada. |
| Preset laporan (RAT/AnnualReport/Pengurus) | **Belum diimplementasikan** — `CooperativeReportController` tidak menyediakan preset. |
| Kuorum · risalah rapat | **Belum diimplementasikan** — tidak ada model `ApprovalMinute`/`RatMinute` dan tidak ada field quorum. |
| SLA 3 hari kerja | **Belum diimplementasikan** — tidak ada `LoanApplicationReviewWindow`. |

## 4. Catatan pelaksanaan

- Audit dilakukan dengan metode `grep` + `read_file` terhadap source code aktual di `feature/in-app-user-guide` (commit `20c86960`).
- Source code diverifikasi per baris dengan kutipan langsung.
- Frontend tidak dibaca secara mendalam untuk klaim konten artikel; hanya untuk konfirmasi rendering Markdown.
- Phase 2 (Markdown-as-source-of-truth) akan menghapus model `DocumentationArticle`, migration, factory, seeder, dan policy DB-backed; menggantinya dengan file-based repository.
- Phase 4 (navigasi) akan menambahkan link "Pusat Panduan" ke `memberNavItems`, `adminNavItems`, dan array Manajer/Pengurus (`allNavItems`) — atau lebih baik ke `footerNavItems` terpusat.
- Phase 5 (render Markdown) akan menambahkan parser Markdown `marked` + sanitasi HTML `DOMPurify`.
- Phase 6 (in-app experience) akan menambahkan search, filter, TOC, prev/next, breadcrumb, contextual help.
- Phase 7-8 (pipeline + validator) akan membangun skrip `scripts/export-user-guide-screenshots.mjs`, `scripts/validate-user-guide.mjs`, dan paket `npm run docs:screenshots` / `npm run docs:validate`.
- Phase 9 (UI Audit + frontend test) akan mendaftarkan entry di `cooperative-pages.json` untuk landing/article empat role + search + screenshot + mobile.

Lihat `docs/decisions.md` (akan diperbarui) untuk ringkasan keputusan.
