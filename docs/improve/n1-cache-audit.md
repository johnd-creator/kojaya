# Audit N+1 Query dan Kandidat Cache Master Data

Tanggal audit: 2026-06-21  
Auditor: Deepseek  
Sumber: scan `->with(`, `->load(`, `preventLazyLoading`, dan `Cache::` di modul prioritas.

## Status Prevent Lazy Loading

- `Model::preventLazyLoading()` aktif untuk environment `local` dan `testing` di `AppServiceProvider.php`.
- Ini memastikan setiap lazy loading yang tidak disengaja akan throw exception selama development/test.

## Modul yang Diaudit

### Koperasi - Members

| Query | Eager Loading | Status |
| --- | --- | --- |
| `CooperativeMemberController::index()` | `with(['user', 'cooperativeDuesInvoices'])` | ✅ Terkonfirmasi |
| `CooperativeMemberController::show()` | `load(['user', 'cooperativeDuesInvoices', 'memberCards', 'loans'])` | ✅ Terkonfirmasi |

### Koperasi - Dues

| Query | Eager Loading | Status |
| --- | --- | --- |
| `CooperativeDuesController` | `with('member')` | ✅ Terkonfirmasi |

### Koperasi - Payments

| Query | Eager Loading | Status |
| --- | --- | --- |
| `CooperativePaymentController` | `with(['member', 'user', 'paymentDetails'])` | ✅ Terkonfirmasi |

### Koperasi - Loans

| Query | Eager Loading | Status |
| --- | --- | --- |
| `LoanController::index()` | `with(['member', 'loanType'])` | ✅ Terkonfirmasi |
| `LoanController::show()` | `load(['member', 'loanType', 'installments'])` | ✅ Terkonfirmasi |

### POS - Transactions

| Query | Eager Loading | Status |
| --- | --- | --- |
| `PosTransactionHistoryController::index()` | `with(['items.product', 'member', 'cashier'])` | ✅ Terkonfirmasi |
| `PosTransactionHistoryController::show()` | `load(['items.product.category', 'member', 'cashier', 'payments'])` | ✅ Terkonfirmasi |

### POS - Reports

| Query | Eager Loading | Status |
| --- | --- | --- |
| `PosSalesReportService::productSalesForPeriod()` | `with('category')` | ✅ Terkonfirmasi |

### HR/Payroll

| Query | Eager Loading | Status |
| --- | --- | --- |
| `PayrollController::index()` | `with(['employee', 'payrollComponents'])` | ✅ Terkonfirmasi |
| `EmployeeController::index()` | `with(['organization', 'position', 'department'])` | ✅ Terkonfirmasi |

### Reports

| Query | Eager Loading | Status |
| --- | --- | --- |
| `NplTrackingService::computeAgingReport()` | `with('loan.loanType', 'loan.member')` | ✅ Terkonfirmasi |

## Kandidat Cache Master Data

Data berikut jarang berubah dan aman di-cache dengan invalidation eksplisit:

| Data | Cache Key | TTL | Invalidation Write Path |
| --- | --- | --- | --- |
| POS Categories | `pos_categories_all` | 1 jam | `PosCategory::created/updated/deleted` |
| Department list | `departments_all` | 1 jam | `Department::created/updated/deleted` |
| Position list | `positions_all` | 1 jam | `Position::created/updated/deleted` |
| Job Grade list | `job_grades_all` | 1 jam | `JobGrade::created/updated/deleted` |

### Catatan

- Cache di atas BELUM diimplementasikan. Kandidat ini dicatat untuk Plan 03 lanjutan.
- Sebelum implementasi, pastikan write path invalidation tersedia di model events (`saved`/`deleted`).
- Jangan cache data operasional yang sering berubah (dashboard summaries, transaction aggregates) tanpa invalidation yang jelas.

## Rekomendasi

1. Pantau log exception dari `preventLazyLoading` - jika muncul N+1 baru, tambahkan eager loading.
2. Cache master data hanya jika terbukti ada latency issue di dropdown tersebut.
3. Untuk setiap cache master data, buat invalidation via model event listener.
