<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EssController;
use App\Http\Controllers\Api\ProductionIntegrationController;
use App\Http\Controllers\Api\TechnicianWorkOrderController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\V1\CooperativeDuesApiController;
use App\Http\Controllers\Api\V1\CooperativeMemberApiController;
use App\Http\Controllers\Api\V1\CooperativePaymentApiController;
use App\Http\Controllers\Api\V1\LoanApiController;
use App\Http\Controllers\Api\V1\MemberCoffeeOrderController;
use App\Http\Controllers\Api\V1\MemberSelfServiceController;
use App\Http\Controllers\Api\V1\MemberStoreController;
use App\Http\Controllers\Api\V1\PointApiController;
use App\Http\Controllers\Api\V1\PosApiController;
use App\Http\Controllers\Api\V1\PosSyncApiController;
use App\Http\Controllers\Api\V1\ProcurementApiController;
use App\Http\Controllers\Api\V1\RewardApiController;
use App\Http\Controllers\Api\V1\SavingsApiController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ComplianceReportController;
use App\Http\Controllers\Cooperative\CooperativeReportController;
use App\Http\Controllers\EmployeeCertificateController;
use App\Http\Controllers\MedicalCheckupController;
use App\Http\Controllers\OpenApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/google/mobile', [AuthController::class, 'loginWithGoogle'])->middleware('throttle:login');
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/session', [AuthController::class, 'session'])->middleware('ability:profile:read');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:api-write');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->middleware('throttle:api-write');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'ability:profile:read', 'throttle:api']);

Route::post('/token/rotate', [TokenController::class, 'rotate'])
    ->middleware(['auth:sanctum', 'throttle:api-write']);

Route::get('/openapi.json', OpenApiController::class)->middleware('throttle:api');
Route::post('/payments/webhook', [ProductionIntegrationController::class, 'paymentWebhook'])->middleware('throttle:api-write');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/devices/push-token', [ProductionIntegrationController::class, 'registerDevice'])
        ->middleware(['ability:profile:read', 'throttle:api-write']);
    Route::post('/payments/charge', [ProductionIntegrationController::class, 'createPaymentCharge'])
        ->middleware(['member.api.active', 'ability:member:write', 'throttle:api-write', 'idempotent']);
    Route::get('/monitoring/health', [ProductionIntegrationController::class, 'monitoring'])
        ->middleware('ability:reports:read');
});

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->group(function () {
    Route::prefix('member')->group(function () {
        // Onboarding-safe routes: available to members in any validation status
        // so pending, revision, and rejected members can complete onboarding.
        Route::get('/dashboard', [MemberSelfServiceController::class, 'dashboard'])->middleware('ability:member:read');
        Route::get('/onboarding/status', [MemberSelfServiceController::class, 'onboardingStatus'])->middleware('ability:member:read');
        Route::post('/onboarding/steps', [MemberSelfServiceController::class, 'markOnboardingStep'])->middleware(['ability:member:write', 'throttle:api-write']);
        Route::get('/status-journey', [MemberSelfServiceController::class, 'statusJourney'])->middleware('ability:member:read');
        Route::get('/profile', [MemberSelfServiceController::class, 'profile'])->middleware('ability:member:read');
        Route::put('/profile', [MemberSelfServiceController::class, 'updateProfile'])->middleware(['ability:member:write', 'throttle:api-write']);
        Route::get('/resignation', [MemberSelfServiceController::class, 'resignationStatus'])->middleware('ability:member:read');

        Route::prefix('notifications')->group(function () {
            Route::get('/', [MemberSelfServiceController::class, 'notifications'])->middleware('ability:member:read');
            Route::get('/recent', [App\Http\Controllers\NotificationController::class, 'recent'])->middleware('ability:member:read');
            Route::get('/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount'])->middleware('ability:member:read');
            Route::get('/summary', [App\Http\Controllers\NotificationController::class, 'summary'])->middleware('ability:member:read');
            Route::get('/preferences', [App\Http\Controllers\NotificationController::class, 'getPreferences'])->middleware('ability:member:read');
            Route::put('/preferences', [App\Http\Controllers\NotificationController::class, 'updatePreferences'])->middleware(['ability:member:write', 'throttle:api-write']);
            Route::patch('/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->middleware(['ability:member:write', 'throttle:api-write']);
            Route::post('/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->middleware(['ability:member:write', 'throttle:api-write']);
        });

        // Active-only routes: financial and transactional endpoints require
        // the member to be fully active (status ACTIVE and validation_status ACTIVE).
        Route::middleware('member.api.active')->group(function () {
            Route::post('/resignation', [MemberSelfServiceController::class, 'submitResignation'])->middleware(['ability:member:write', 'throttle:api-write']);
            Route::delete('/resignation', [MemberSelfServiceController::class, 'cancelResignation'])->middleware(['ability:member:write', 'throttle:api-write']);
            Route::get('/savings/summary', [MemberSelfServiceController::class, 'savingsSummary'])->middleware('ability:member:read');
            Route::get('/savings/ledger', [MemberSelfServiceController::class, 'savingsLedger'])->middleware('ability:member:read');
            Route::post('/savings/withdraw', [MemberSelfServiceController::class, 'requestSavingsWithdrawal'])->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);
            Route::get('/dues/invoices', [MemberSelfServiceController::class, 'invoices'])->middleware('ability:member:read');
            Route::get('/dues/invoices/{invoice}', [MemberSelfServiceController::class, 'showInvoice'])->middleware('ability:member:read');
            Route::post('/dues/invoices/{invoice}/payment-intent', [MemberSelfServiceController::class, 'createPaymentIntent'])->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);
            Route::get('/payments', [MemberSelfServiceController::class, 'payments'])->middleware('ability:member:read');
            Route::get('/payments/{payment}', [MemberSelfServiceController::class, 'showPayment'])->middleware('ability:member:read');
            Route::get('/payments/{payment}/status', [MemberSelfServiceController::class, 'paymentStatus'])->name('api.v1.member.payments.status')->middleware('ability:member:read');
            Route::get('/payments/{payment}/qris-image', [MemberSelfServiceController::class, 'qrisImage'])->name('api.v1.member.payments.qris-image')->middleware('ability:member:read');
            Route::get('/payments/{payment}/receipt', [MemberSelfServiceController::class, 'paymentReceipt'])->middleware('ability:member:read');
            Route::post('/payments/proof', [MemberSelfServiceController::class, 'uploadPaymentProof'])->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);
            Route::get('/bills', [MemberSelfServiceController::class, 'bills'])->middleware('ability:member:read');
            Route::get('/bills/{bill}', [MemberSelfServiceController::class, 'showBill'])->middleware('ability:member:read');
            Route::post('/bills/{bill}/payment-intent', [MemberSelfServiceController::class, 'createBillPaymentIntent'])->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);
            Route::get('/loans/options', [MemberSelfServiceController::class, 'loanOptions'])->middleware('ability:member:read');
            Route::get('/loans', [MemberSelfServiceController::class, 'loans'])->middleware('ability:member:read');
            Route::post('/loans', [MemberSelfServiceController::class, 'applyLoan'])->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);
            Route::get('/loans/{loan}', [MemberSelfServiceController::class, 'loan'])->middleware('ability:member:read');
            Route::post('/loans/{loan}/restructure', [MemberSelfServiceController::class, 'requestLoanRestructure'])->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);
            Route::get('/shu', [MemberSelfServiceController::class, 'shu'])->middleware('ability:member:read');
            Route::get('/reward-redemptions', [MemberSelfServiceController::class, 'rewardRedemptions'])->middleware('ability:member:read');
            Route::get('/transactions', [MemberSelfServiceController::class, 'transactions'])->middleware('ability:member:read');
            Route::get('/transactions/unified', [MemberSelfServiceController::class, 'unifiedTransactions'])->middleware('ability:member:read');
            Route::get('/coffee/menu', [MemberCoffeeOrderController::class, 'index'])->middleware('ability:member:read');
            Route::post('/coffee/orders', [MemberCoffeeOrderController::class, 'store'])->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);
            Route::get('/coffee/orders/{coffeeOrder}', [MemberCoffeeOrderController::class, 'show'])->middleware('ability:member:read');
            Route::get('/store/catalog', [MemberStoreController::class, 'catalog'])->middleware('ability:member:read');
            Route::post('/store/orders', [MemberStoreController::class, 'store'])->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);
            Route::get('/payment-intents/{intent}', [MemberStoreController::class, 'showIntent'])->middleware('ability:member:read');
            Route::get('/support-tickets', [MemberSelfServiceController::class, 'supportTickets'])->middleware('ability:member:read');
            Route::post('/support-tickets', [MemberSelfServiceController::class, 'storeSupportTicket'])->middleware(['ability:member:write', 'throttle:api-write']);
        });
    });

    Route::get('/members', [CooperativeMemberApiController::class, 'index'])->middleware('cooperative.ability:cooperative.member.read,cooperative:read');
    Route::post('/members', [CooperativeMemberApiController::class, 'store'])->middleware(['cooperative.ability:cooperative.member.write,cooperative:write', 'throttle:api-write']);
    Route::get('/members/resignation-requests', [CooperativeMemberApiController::class, 'resignationRequests'])->middleware('cooperative.ability:cooperative.resignation.review,cooperative.member.read,cooperative:read');
    Route::get('/members/{member}', [CooperativeMemberApiController::class, 'show'])->middleware('cooperative.ability:cooperative.member.read,cooperative:read');
    Route::put('/members/{member}', [CooperativeMemberApiController::class, 'update'])->middleware(['cooperative.ability:cooperative.member.write,cooperative:write', 'throttle:api-write']);
    Route::patch('/members/{member}/sensitive-data', [CooperativeMemberApiController::class, 'updateSensitiveData'])->middleware(['cooperative.ability:cooperative.member.write,cooperative:write', 'throttle:api-write']);
    Route::patch('/members/{member}/account', [CooperativeMemberApiController::class, 'linkAccount'])->middleware(['cooperative.ability:cooperative.member.write,cooperative:write', 'throttle:api-write']);
    Route::post('/members/{member}/activate', [CooperativeMemberApiController::class, 'activate'])->middleware(['cooperative.ability:cooperative.member.write,cooperative:write', 'throttle:api-write']);
    Route::post('/members/{member}/resign', [CooperativeMemberApiController::class, 'resign'])->middleware(['cooperative.ability:cooperative.member.write,cooperative:write', 'throttle:api-write']);
    Route::post('/members/resignation-requests/{resignationRequest}/process', [CooperativeMemberApiController::class, 'processResignationRequest'])->middleware(['cooperative.ability:cooperative.resignation.review,cooperative.member.write,cooperative:write', 'throttle:api-write']);
    Route::get('/dues/invoices', [CooperativeDuesApiController::class, 'invoices'])->middleware('cooperative.ability:cooperative.dues.read,cooperative:read');
    Route::post('/dues/generate', [CooperativeDuesApiController::class, 'generate'])->middleware(['cooperative.ability:cooperative.dues.write,cooperative:write', 'throttle:api-write']);
    Route::post('/dues/payments', [CooperativePaymentApiController::class, 'store'])->middleware(['cooperative.ability:cooperative.payment.record,cooperative:write', 'throttle:api-write', 'idempotent']);
    Route::post('/dues/payments/batch', [CooperativePaymentApiController::class, 'batch'])->middleware(['cooperative.ability:cooperative.payment.record,cooperative:write', 'throttle:api-write', 'idempotent']);
    Route::post('/dues/payments/{payment}/approve', [CooperativePaymentApiController::class, 'approve'])->middleware(['cooperative.ability:cooperative.payment.record,cooperative:write', 'throttle:api-write']);
    Route::get('/savings/categories', [SavingsApiController::class, 'categories'])->middleware('cooperative.ability:cooperative.ledger.read,cooperative:read');
    Route::get('/savings/ledger', [SavingsApiController::class, 'ledger'])->middleware('cooperative.ability:cooperative.ledger.read,cooperative:read');
    Route::get('/loans', [LoanApiController::class, 'index'])->middleware('cooperative.ability:cooperative.loan.read,cooperative:read');
    Route::post('/loans/apply', [LoanApiController::class, 'apply'])->middleware(['cooperative.ability:cooperative.loan.write,cooperative:write', 'throttle:api-write', 'idempotent']);
    Route::get('/loans/{loan}', [LoanApiController::class, 'show'])->middleware('cooperative.ability:cooperative.loan.read,cooperative:read');
    Route::post('/loans/{loan}/review', [LoanApiController::class, 'review'])->middleware(['cooperative.ability:cooperative.loan.review,cooperative:write', 'throttle:api-write']);
    Route::post('/loans/{loan}/approve', [LoanApiController::class, 'approve'])->middleware(['cooperative.ability:cooperative.loan.approve,cooperative:write', 'throttle:api-write']);
    Route::post('/loans/{loan}/reject', [LoanApiController::class, 'reject'])->middleware(['cooperative.ability:cooperative.loan.review,cooperative.loan.approve,cooperative:write', 'throttle:api-write']);
    Route::post('/loans/calculator', [LoanApiController::class, 'calculator'])->middleware(['cooperative.ability:cooperative.loan.read,cooperative:read', 'throttle:api-write']);
    Route::middleware('member.api.active')->group(function () {
        Route::get('/points/balance', [PointApiController::class, 'balance'])->middleware('ability:member:read');
        Route::get('/points/history', [PointApiController::class, 'history'])->middleware('ability:member:read');
        Route::get('/rewards', [RewardApiController::class, 'index'])->middleware('ability:member:read');
        Route::post('/rewards/{reward}/redeem', [RewardApiController::class, 'redeem'])->middleware(['ability:member:write', 'throttle:api-write', 'idempotent']);
    });
    Route::get('/pos/products', [PosApiController::class, 'products'])->middleware('ability:pos:read');
    Route::post('/pos/transactions', [PosApiController::class, 'store'])->middleware(['ability:pos:write', 'throttle:api-write', 'idempotent']);
    Route::post('/pos/returns', [PosApiController::class, 'processReturn'])->middleware(['ability:pos:write', 'throttle:api-write', 'idempotent']);

    Route::get('/pos/sync/catalog', [PosSyncApiController::class, 'catalog'])->middleware('ability:pos:read');
    Route::post('/pos/sync/enqueue', [PosSyncApiController::class, 'enqueue'])->middleware(['ability:pos:write', 'throttle:api-write']);
    Route::post('/pos/sync/process/{idempotency_key}', [PosSyncApiController::class, 'process'])->middleware(['ability:pos:write', 'throttle:api-write']);
    Route::post('/pos/sync/batch', [PosSyncApiController::class, 'processBatch'])->middleware(['ability:pos:write', 'throttle:api-write']);
    Route::get('/pos/sync/status/{idempotency_key}', [PosSyncApiController::class, 'status'])->middleware('ability:pos:read');
    Route::get('/reports/cooperative-summary', [CooperativeReportController::class, 'summary'])->middleware('ability:reports:read');
    Route::get('/reports/sales', [CooperativeReportController::class, 'sales'])->middleware('ability:reports:read');
    Route::get('/reports/npl-aging', [CooperativeReportController::class, 'nplAging'])->middleware('ability:reports:read');
    Route::get('/procurement/vendors/{vendor}/performance', [ProcurementApiController::class, 'vendorPerformance'])->middleware('ability:reports:read');

    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->middleware('cooperative.ability:cooperative.member.read,cooperative:read');
        Route::get('/recent', [App\Http\Controllers\NotificationController::class, 'recent'])->middleware('cooperative.ability:cooperative.member.read,cooperative:read');
        Route::get('/summary', [App\Http\Controllers\NotificationController::class, 'summary'])->middleware('cooperative.ability:cooperative.member.read,cooperative:read');
        Route::get('/preferences', [App\Http\Controllers\NotificationController::class, 'getPreferences'])->middleware('cooperative.ability:cooperative.member.read,cooperative:read');
        Route::put('/preferences', [App\Http\Controllers\NotificationController::class, 'updatePreferences'])->middleware(['cooperative.ability:cooperative.member.write,cooperative:write', 'throttle:api-write']);
        Route::patch('/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->middleware(['cooperative.ability:cooperative.member.write,cooperative:write', 'throttle:api-write']);
        Route::post('/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->middleware(['cooperative.ability:cooperative.member.write,cooperative:write', 'throttle:api-write']);
    });
});

// Employee Self Service Mobile API
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('ess')->group(function () {
    Route::get('/dashboard', [EssController::class, 'dashboard'])->middleware('ability:ess:read');
    Route::get('/profile', [EssController::class, 'profile'])->middleware('ability:ess:read');
    Route::put('/profile', [EssController::class, 'updateProfile'])->middleware(['ability:ess:write', 'throttle:api-write']);
    Route::get('/attendance/today', [EssController::class, 'todayAttendance'])->middleware('ability:attendance:read');
    Route::get('/attendance/history', [EssController::class, 'attendanceHistory'])->middleware('ability:attendance:read');
    Route::post('/attendance/check-in', [EssController::class, 'checkIn'])->middleware(['ability:attendance:write', 'throttle:api-write']);
    Route::post('/attendance/check-out', [EssController::class, 'checkOut'])->middleware(['ability:attendance:write', 'throttle:api-write']);
    Route::post('/attendance/correction', [EssController::class, 'requestAttendanceCorrection'])->middleware(['ability:attendance:write', 'throttle:api-write']);
    Route::post('/attendance/corrections/{attendanceCorrection}/approve', [EssController::class, 'approveAttendanceCorrection'])->middleware(['ability:attendance:write', 'throttle:api-write']);
    Route::get('/geofence', [EssController::class, 'geofence'])->middleware('ability:attendance:read');
    Route::get('/shift-roster', [EssController::class, 'shiftRoster'])->middleware('ability:ess:read');
    Route::get('/thr/entitlement', [EssController::class, 'thrEntitlement'])->middleware('ability:payroll:read');
    Route::get('/leaves', [EssController::class, 'leaves'])->middleware('ability:ess:read');
    Route::post('/leaves', [EssController::class, 'storeLeave'])->middleware(['ability:ess:write', 'throttle:api-write']);
    Route::post('/leaves/{leave}/cancel', [EssController::class, 'cancelLeave'])->middleware(['ability:ess:write', 'throttle:api-write']);
    Route::get('/overtime', [EssController::class, 'overtime'])->middleware('ability:ess:read');
    Route::post('/overtime', [EssController::class, 'storeOvertime'])->middleware(['ability:ess:write', 'throttle:api-write']);
    Route::get('/reimbursements', [EssController::class, 'reimbursements'])->middleware('ability:ess:read');
    Route::post('/reimbursements', [EssController::class, 'storeReimbursement'])->middleware(['ability:ess:write', 'throttle:api-write']);
    Route::get('/payslips', [EssController::class, 'payslips'])->middleware('ability:ess:read');
    Route::get('/payslips/{payroll}/download', [EssController::class, 'downloadPayslip'])->middleware('ability:ess:read');
    Route::get('/compliance', [EssController::class, 'compliance'])->middleware('ability:ess:read');
    Route::get('/notifications', [EssController::class, 'notifications'])->middleware('ability:ess:read');
});

// Technician Mobile API
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('technician')->group(function () {
    Route::get('/work-orders', [TechnicianWorkOrderController::class, 'index'])->middleware('ability:work-orders:read');
    Route::get('/work-orders/{id}', [TechnicianWorkOrderController::class, 'show'])->middleware('ability:work-orders:read');
    Route::post('/work-orders/{id}/start', [TechnicianWorkOrderController::class, 'start'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
    Route::post('/work-orders/{id}/complete', [TechnicianWorkOrderController::class, 'complete'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
    Route::post('/work-orders/{id}/checklists/{checklistId}', [TechnicianWorkOrderController::class, 'updateChecklist'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
    Route::post('/work-orders/{id}/attachments', [TechnicianWorkOrderController::class, 'storeAttachment'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
    Route::post('/work-orders/{id}/parts', [TechnicianWorkOrderController::class, 'storePart'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
    Route::post('/work-orders/{id}/sync', [TechnicianWorkOrderController::class, 'sync'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
    Route::get('/work-orders/{id}/timeline', [TechnicianWorkOrderController::class, 'timeline'])->middleware('ability:work-orders:read');
    Route::post('/work-orders/{id}/escalate', [TechnicianWorkOrderController::class, 'escalate'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
    Route::post('/work-orders/{id}/reopen', [TechnicianWorkOrderController::class, 'reopen'])->middleware(['ability:work-orders:review', 'throttle:api-write']);
});

// Employee Certificates API
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('employees/{employeeId}')->group(function () {
    Route::get('/certificates', [EmployeeCertificateController::class, 'index'])->middleware('ability:employee-documents:read');
    Route::post('/certificates', [EmployeeCertificateController::class, 'store'])->middleware(['ability:employee-documents:write', 'throttle:api-write']);
    Route::get('/certificates/{id}', [EmployeeCertificateController::class, 'show'])->middleware('ability:employee-documents:read');
    Route::put('/certificates/{id}', [EmployeeCertificateController::class, 'update'])->middleware(['ability:employee-documents:write', 'throttle:api-write']);
    Route::delete('/certificates/{id}', [EmployeeCertificateController::class, 'destroy'])->middleware(['ability:employee-documents:write', 'throttle:api-write']);
    Route::post('/certificates/{id}/upload', [EmployeeCertificateController::class, 'uploadDocument'])->middleware(['ability:employee-documents:write', 'throttle:api-write']);
});

// Medical Checkups API
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('employees/{employeeId}')->group(function () {
    Route::get('/mcu', [MedicalCheckupController::class, 'index'])->middleware('ability:employee-documents:read');
    Route::post('/mcu', [MedicalCheckupController::class, 'store'])->middleware(['ability:employee-documents:write', 'throttle:api-write']);
    Route::get('/mcu/{id}', [MedicalCheckupController::class, 'show'])->middleware('ability:employee-documents:read');
    Route::put('/mcu/{id}', [MedicalCheckupController::class, 'update'])->middleware(['ability:employee-documents:write', 'throttle:api-write']);
    Route::delete('/mcu/{id}', [MedicalCheckupController::class, 'destroy'])->middleware(['ability:employee-documents:write', 'throttle:api-write']);
    Route::post('/mcu/{id}/upload', [MedicalCheckupController::class, 'uploadDocument'])->middleware(['ability:employee-documents:write', 'throttle:api-write']);
});

// Compliance Reports API
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('reports')->group(function () {
    Route::get('/certificate-compliance', [ComplianceReportController::class, 'certificateCompliance'])->middleware('ability:reports:read');
    Route::get('/mcu-compliance', [ComplianceReportController::class, 'mcuCompliance'])->middleware('ability:reports:read');
    Route::get('/non-compliant-employees', [ComplianceReportController::class, 'nonCompliantEmployees'])->middleware('ability:reports:read');
});

// Audit Logs API (Web session auth for Inertia)
Route::middleware(['auth:web', 'throttle:audit-logs'])->prefix('audit-logs')->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/{id}', [AuditLogController::class, 'show']);
    Route::get('/history/{type}/{id}', [AuditLogController::class, 'history']);
    Route::get('/export', [AuditLogController::class, 'export'])->middleware('throttle:audit-export');
});
