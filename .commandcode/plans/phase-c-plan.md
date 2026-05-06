# Phase C — Workflow Approval & Closing Lintas Modul

**Tanggal Rencana:** 6 Mei 2026
**Basis Audit:** `docs/improve3.md`

---

## Ringkasan Eksekutif

Phase C bertujuan menstandarkan approval log, menerapkan segregation of duties, dan membangun exception report lintas modul. Exploration menunjukkan codebase sudah punya fondasi (ApprovalLog model, CooperativePeriodLock, OperatorProcedureService exceptions) tapi tidak konsisten: hanya 2 dari 8 workflow yang punya audit trail, tidak ada segregation of duties di mana pun, dan exception report hanya ada di modul koperasi.

## Penemuan Eksplorasi

### Approval Log Status
| Workflow | ApprovalLog | Creator ≠ Approver? |
|---|---|---|
| Cooperative Loan | ✅ (polymorphic, Loan::class) | ❌ Tidak dicek |
| Purchase Request (PR) | ✅ (string 'PR', multi-level) | ❌ Tidak dicek |
| Cooperative Payment | ❌ (hanya ledger) | ❌ Tidak dicek |
| Payroll | ❌ (PayrollApproval self-documents) | ❌ Tidak dicek |
| Reimbursement | ❌ | ❌ Tidak dicek |
| Leave | ❌ | ❌ Tidak dicek |
| Overtime | ❌ | ❌ Tidak dicek |
| Purchase Order (PO) | ❌ (tidak ada approval) | N/A |

### Field Creator/Approver yang Ada
| Model | Creator Field | Approver Field |
|---|---|---|
| Reimbursement | `user_id` | `approver_id` |
| Leave | `employee_id` → `employees.user_id` | `approver_id` |
| OvertimeRequest | `employee_id` → `employees.user_id` | `approved_by` |
| CooperativePayment | `user_id` | `approved_by` |
| Loan | `user_id` | `approved_by` |
| PayrollApproval | `requester_id` | `approver_id` |
| PurchaseRequest | `requester_id` | (di ApprovalLog) |

### Closing/Lock Status
- Cooperative: `CooperativePeriodLock` + `CooperativeClosingChecklist` sudah solid, `assertUnlocked()` dipakai di Payment & Dues service
- Finance: **tidak ada period lock sama sekali**
- SHU: `CooperativeShuPeriod.status = 'CLOSED'` dengan guard duplicate close

### Exception Report Status
- Cooperative: `OperatorProcedureService::exceptions()` mengcover overdue loans, unpaid dues, pending payments, low stock
- **Tidak ada exception report lintas modul**: PR/PO overdue, payroll pending, reimbursement pending, bank unreconciled

---

## Rencana Batch

Karena Phase C mencakup 4 workstream besar dengan banyak file yang disentuh, implementasi dipecah menjadi 3 batch:

### Batch C1 — Standarisasi Approval Log
### Batch C2 — Segregation of Duties + Creator Cannot Approve
### Batch C3 — Exception Report Lintas Modul + Finance Closing Dashboard

---

## BATCH C1 — Standarisasi Approval Log

**Tujuan:** Setiap transaksi finansial penting punya actor, reason, status history, dan audit trail.

### C1.1 — Buat `HasApprovalLog` Trait

**File:** `app/Models/Traits/HasApprovalLog.php`

Sebuah trait yang menyediakan:
- `approvalLogs(): MorphMany` — polymorphic relation ke ApprovalLog
- `logApproval(string $fromStatus, string $toStatus, ?User $actor, ?string $note): ApprovalLog` — helper method terstandar
- `approvalLogItems(): Collection` — mengambil semua log dengan approver name

### C1.2 — Tambahkan ApprovalLog ke 6 Workflow

#### C1.2a — CooperativePayment
**Model:** tambah `use HasApprovalLog;` di `CooperativePayment.php`
**Service:** `CooperativePaymentService.php` — panggil `logApproval()` di `approve()` dan `reconcile()`

#### C1.2b — Payroll
**Model:** tambah `use HasApprovalLog;` di `PayrollApproval.php`
**Model:** panggil `$this->logApproval(...)` di `approve()` dan `reject()`

#### C1.2c — Reimbursement
**Model:** tambah `use HasApprovalLog;` di `Reimbursement.php`
**Controller:** `ReimbursementController.php` — panggil `logApproval()` di `approve()`, `reject()`, `pay()`

#### C1.2d — Leave
**Model:** tambah `use HasApprovalLog;` di `Leave.php`
**Controller:** `LeaveController.php` — panggil di `updateStatus()`

#### C1.2e — Overtime
**Model:** tambah `use HasApprovalLog;` di `OvertimeRequest.php`
**Controller:** `OvertimeController.php` — panggil di `approve()` dan `reject()`

#### C1.2f — Purchase Order
**Model:** tambah `use HasApprovalLog;` di `PurchaseOrder.php`
**Service:** `ProcurementService.php` — panggil `logApproval()` di `createPoFromPr()`

### C1.3 — Normalisasi subject_type

Ubah `'PR'` → `PurchaseRequest::class` di `ProcurementService.php` dan `PurchaseRequestController.php`.

### C1.4 — Tambahkan timeline approval di UI

5 halaman Show ditambah timeline ApprovalLog: Reimbursement, Leave, Overtime, CooperativePayment, Payroll.

---

## BATCH C2 — Segregation of Duties + Creator Cannot Approve

**Tujuan:** Creator tidak bisa menjadi approver final pada workflow sensitif.

### C2.1 — Buat Validation Rule `SegregationOfDuties`

**File:** `app/Rules/SegregationOfDuties.php`

### C2.2 — Terapkan di 7 Workflow

| Workflow | File | Check |
|---|---|---|
| Loan | `LoanService::approve()` | `actor->id !== loan->user_id` |
| CooperativePayment | `CooperativePaymentService::approve()` | `approver->id !== payment->user_id` |
| Payroll | `PayrollApproval::approve()` | `approver->id !== requester_id` |
| Reimbursement | `ReimbursementPolicy::approve()` | `user->id !== reimbursement->user_id` |
| Leave | `LeavePolicy::approve()` | `user->id !== leave->employee->user_id` |
| Overtime | `OvertimeRequestPolicy::approve()` | `user->id !== overtime->employee->user_id` |
| PR | `ProcurementService::approvePr()` | `user->id !== pr->requester_id` |

### C2.3 — Tambahkan test negatif

**File:** `tests/Feature/PhaseCSegregationTest.php` — 7 test cases

---

## BATCH C3 — Exception Report Lintas Modul + Finance Closing Dashboard

**Tujuan:** Operator dan finance bisa melihat exception / overdue lintas modul, dan ada closing dashboard untuk finance.

### C3.1 — Cross-Module Exception Service

**File:** `app/Services/Exceptions/CrossModuleExceptionService.php`

Mengumpulkan exceptions dari cooperative, finance, hr, procurement.

### C3.2 — Exception Report Controller + Page

**Controller:** `ExceptionReportController` — `index()` dan `show(module)`
**Page:** `Exceptions/Dashboard.vue` — tab per modul, card summary, tabel detail
**Route:** `GET /exceptions`

### C3.3 — Finance Closing Service + Controller + Page

**Service:** `FinanceClosingService` — pakai `CooperativePeriodLock` dengan `module = 'FINANCE'`
**Controller:** `FinanceClosingController` — mirror `OperatorProcedureController`
**Page:** `Finance/Closing.vue` — mirror `Cooperative/Operator/Closing.vue`
**Route:** `GET /finance/closing`

### C3.4 — Tambahkan test

**File:** `tests/Feature/PhaseCExceptionReportTest.php`

---

## Urutan Eksekusi

```
Batch C1 → Batch C2 → Batch C3
```

- **C1** duluan karena membangun fondasi (trait, approval log yang terstandar)
- **C2** bergantung pada C1 — segregation of duties dicatat di ApprovalLog
- **C3** bisa paralel setelah C1 selesai, tapi butuh data approval log dari C1 untuk exception report pending approvals

---

## File-File yang Disentuh

### Batch C1 (13 file)
- `app/Models/Traits/HasApprovalLog.php` (NEW)
- `app/Models/CooperativePayment.php`
- `app/Models/Reimbursement.php`
- `app/Models/Leave.php`
- `app/Models/OvertimeRequest.php`
- `app/Models/PayrollApproval.php`
- `app/Models/PurchaseOrder.php`
- `app/Services/Cooperative/CooperativePaymentService.php`
- `app/Http/Controllers/ReimbursementController.php`
- `app/Http/Controllers/LeaveController.php`
- `app/Http/Controllers/OvertimeController.php`
- `app/Services/Procurement/ProcurementService.php`
- `app/Http/Controllers/Procurement/PurchaseRequestController.php`

### Batch C2 (9 file)
- `app/Rules/SegregationOfDuties.php` (NEW)
- `app/Services/Cooperative/LoanService.php`
- `app/Services/Cooperative/CooperativePaymentService.php`
- `app/Models/PayrollApproval.php`
- `app/Policies/ReimbursementPolicy.php`
- `app/Policies/LeavePolicy.php`
- `app/Policies/OvertimeRequestPolicy.php`
- `app/Services/Procurement/ProcurementService.php`
- `tests/Feature/PhaseCSegregationTest.php` (NEW)

### Batch C3 (7 file)
- `app/Services/Exceptions/CrossModuleExceptionService.php` (NEW)
- `app/Services/Finance/FinanceClosingService.php` (NEW)
- `app/Http/Controllers/ExceptionReportController.php` (NEW)
- `app/Http/Controllers/Finance/FinanceClosingController.php` (NEW)
- `resources/js/pages/Exceptions/Dashboard.vue` (NEW)
- `resources/js/pages/Finance/Closing.vue` (NEW)
- `tests/Feature/PhaseCExceptionReportTest.php` (NEW)

---

## Verifikasi (Definition of Done)

1. **Batch C1:** Setiap transaksi finansial (loan, payment, payroll, reimbursement, leave, overtime, PR, PO) menghasilkan ApprovalLog entry di setiap transisi status, dengan actor, from_status, to_status, reason, dan timestamp.
2. **Batch C2:** 7 test negatif lulus — creator tidak bisa approve transaksinya sendiri.
3. **Batch C3:** Halaman exception report menampilkan overdue/unpaid/pending dari semua modul. Finance closing dashboard bisa lock/unlock period.
