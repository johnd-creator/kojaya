<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

// Google SSO
Route::get('/auth/google/redirect', [\App\Http\Controllers\Auth\GoogleSsoController::class, 'redirect'])
    ->middleware('guest')
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleSsoController::class, 'callback'])
    ->name('auth.google.callback');
Route::get('/auth/google/link', [\App\Http\Controllers\Auth\GoogleSsoController::class, 'link'])
    ->middleware(['auth', 'verified'])
    ->name('auth.google.link');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'show'])->name('dashboard');

    // Dashboard API
    Route::get('/api/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
    Route::get('/api/organizations', [\App\Http\Controllers\DashboardController::class, 'organizations'])->name('api.organizations');

    // Notifications
    Route::inertia('notifications', 'Notifications')->name('notifications');

    // Audit Logs
    Route::inertia('audit-logs', 'AuditLogs/Index')->middleware('can:view_audit_logs')->name('audit-logs');

    // Reports
    Route::get('reports', [\App\Http\Controllers\ReportController::class, 'page'])->name('reports');

    // Consolidated Reports API
    Route::prefix('api/reports')->group(function () {
        Route::get('/consolidated-stats', [\App\Http\Controllers\ReportController::class, 'consolidatedStats'])->name('reports.consolidated-stats');
        Route::get('/consolidated-payroll', [\App\Http\Controllers\ReportController::class, 'consolidatedPayroll'])->name('reports.consolidated-payroll');
        Route::get('/consolidated-attendance', [\App\Http\Controllers\ReportController::class, 'consolidatedAttendance'])->name('reports.consolidated-attendance');
    });

    // Audit Logs API (session-based for Inertia)
    Route::prefix('api/audit-logs')->middleware('throttle:audit-logs')->group(function () {
        Route::get('/', [App\Http\Controllers\AuditLogController::class, 'index']);
        Route::get('/export', [App\Http\Controllers\AuditLogController::class, 'export'])->middleware('throttle:audit-export');
        Route::get('/history/{type}/{id}', [App\Http\Controllers\AuditLogController::class, 'history']);
        Route::get('/{id}', [App\Http\Controllers\AuditLogController::class, 'show'])->whereNumber('id');
    });

    // Notifications API (session-based for Inertia)
    Route::prefix('api/notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index']);
        Route::get('/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount']);
        Route::get('/preferences', [App\Http\Controllers\NotificationController::class, 'getPreferences']);
        Route::put('/preferences', [App\Http\Controllers\NotificationController::class, 'updatePreferences']);
        Route::post('/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
        Route::patch('/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::get('/{id}', [App\Http\Controllers\NotificationController::class, 'show']);
    });

    // HR Master Data
    Route::resource('departments', \App\Http\Controllers\DepartmentController::class);
    Route::resource('job-grades', \App\Http\Controllers\JobGradeController::class);
    Route::resource('positions', \App\Http\Controllers\PositionController::class);
    Route::resource('work-shifts', \App\Http\Controllers\WorkShiftController::class);
    Route::resource('salary-structures', \App\Http\Controllers\SalaryStructureController::class);
    Route::resource('shift-rosters', \App\Http\Controllers\ShiftRosterController::class);
    Route::post('shift-rosters/generate', [\App\Http\Controllers\ShiftRosterController::class, 'generate'])->name('shift-rosters.generate');

    // Storage Management
    Route::resource('spare-parts', \App\Http\Controllers\SparePartController::class);
    Route::post('spare-parts/{id}/stock', [\App\Http\Controllers\SparePartController::class, 'updateStock'])->name('spare-parts.update-stock');
    Route::resource('warehouses', \App\Http\Controllers\WarehouseController::class);

    // Enterprise Asset Management
    Route::resource('assets', \App\Http\Controllers\AssetController::class);

    Route::prefix('work-orders')->name('work-orders.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WorkOrderController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WorkOrderController::class, 'create'])->name('create');
        Route::get('/{workOrder}', [\App\Http\Controllers\WorkOrderController::class, 'show'])->name('show');
        Route::post('/', [\App\Http\Controllers\WorkOrderController::class, 'store'])->name('store');
    });

    // User Management
    Route::resource('users', \App\Http\Controllers\UserController::class)->except(['create', 'show']);
    Route::resource('roles', \App\Http\Controllers\RoleController::class)->only(['index', 'edit', 'update']);
    Route::resource('organizations', \App\Http\Controllers\OrganizationController::class)->except(['show']);
    Route::post('switch-organization', [\App\Http\Controllers\SwitchOrganizationController::class, 'switch'])->name('switch-organization');
    Route::resource('clients', \App\Http\Controllers\ClientController::class);
    Route::resource('employees', \App\Http\Controllers\EmployeeController::class);
    Route::post('employees/{employee}/enable-ess', [\App\Http\Controllers\EmployeeController::class, 'enableEssAccess'])->name('employees.enable-ess');
    Route::post('employees/{employee}/revoke-ess', [\App\Http\Controllers\EmployeeController::class, 'revokeEssAccess'])->name('employees.revoke-ess');

    // Employee Transfers
    Route::resource('employee-transfers', \App\Http\Controllers\EmployeeTransferController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('employee-transfers/{transfer}/approve', [\App\Http\Controllers\EmployeeTransferController::class, 'approve'])->name('employee-transfers.approve');
    Route::post('employee-transfers/{transfer}/reject', [\App\Http\Controllers\EmployeeTransferController::class, 'reject'])->name('employee-transfers.reject');

    // Attendance & ESS
    Route::get('attendance/self-service', [\App\Http\Controllers\AttendanceController::class, 'selfService'])->name('attendance.self-service');
    Route::post('attendance/check-in', [\App\Http\Controllers\AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('ess/attendance/check-in', [\App\Http\Controllers\AttendanceController::class, 'checkInApi'])->name('ess.attendance.check-in');
    Route::post('ess/attendance/check-out', [\App\Http\Controllers\AttendanceController::class, 'checkOutApi'])->name('ess.attendance.check-out');
    Route::get('ess/geofence', [\App\Http\Controllers\AttendanceController::class, 'geofence'])->name('ess.geofence');

    // Leave Management
    Route::get('leaves/self-service', [\App\Http\Controllers\LeaveController::class, 'selfService'])->name('leaves.self-service');
    Route::post('leaves/self-service', [\App\Http\Controllers\LeaveController::class, 'store'])->name('leaves.store');
    Route::get('leaves', [\App\Http\Controllers\LeaveController::class, 'index'])->name('leaves.index');
    Route::put('leaves/{leave}/status', [\App\Http\Controllers\LeaveController::class, 'updateStatus'])->name('leaves.update-status');

    // Overtime Management
    Route::resource('overtime', \App\Http\Controllers\OvertimeController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::post('overtime/{overtimeRequest}/approve', [\App\Http\Controllers\OvertimeController::class, 'approve'])->name('overtime.approve');
    Route::post('overtime/{overtimeRequest}/reject', [\App\Http\Controllers\OvertimeController::class, 'reject'])->name('overtime.reject');

    Route::post('attendance/check-out', [\App\Http\Controllers\AttendanceController::class, 'checkOut'])->name('attendances.checkOut');
    Route::resource('attendances', \App\Http\Controllers\AttendanceController::class)->only(['index', 'store']);
    Route::prefix('ess')->name('ess.')->group(function () {
        Route::get('/', [\App\Http\Controllers\EssPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\EssPortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\EssPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/payslips', [\App\Http\Controllers\EssPortalController::class, 'payslips'])->name('payslips');
        Route::get('/compliance', [\App\Http\Controllers\EssPortalController::class, 'compliance'])->name('compliance');
    });
    Route::prefix('member')->name('member.')->middleware('member')->group(function () {
        Route::get('/', [\App\Http\Controllers\MemberPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/onboarding', [\App\Http\Controllers\MemberPortalController::class, 'onboarding'])->name('onboarding');
        Route::post('/onboarding', [\App\Http\Controllers\MemberPortalController::class, 'submitOnboarding'])->name('onboarding.submit');
        Route::post('/onboarding/steps', [\App\Http\Controllers\MemberPortalController::class, 'markOnboardingStep'])->name('onboarding.steps');
        Route::post('/payments/proof', [\App\Http\Controllers\MemberPortalController::class, 'uploadPaymentProof'])->name('payments.proof');
        Route::get('/profile', [\App\Http\Controllers\MemberPortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\MemberPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/notifications', [\App\Http\Controllers\MemberPortalController::class, 'notifications'])->name('notifications');

        // Fitur finansial sensitif hanya untuk anggota fully active
        Route::middleware('member.active')->group(function () {
            Route::get('/savings', [\App\Http\Controllers\MemberPortalController::class, 'savings'])->name('savings');
            Route::get('/loans', [\App\Http\Controllers\MemberPortalController::class, 'loans'])->name('loans');
            Route::post('/loans', [\App\Http\Controllers\MemberPortalController::class, 'applyLoan'])->name('loans.store');
            Route::get('/points', [\App\Http\Controllers\MemberPortalController::class, 'points'])->name('points');
            Route::get('/rewards', [\App\Http\Controllers\MemberPortalController::class, 'rewards'])->name('rewards');
            Route::post('/rewards/{reward}/redeem', [\App\Http\Controllers\MemberPortalController::class, 'redeemReward'])->name('rewards.redeem');
            Route::get('/transactions', [\App\Http\Controllers\MemberPortalController::class, 'transactions'])->name('transactions');
        });
    });
    Route::resource('employees.contracts', \App\Http\Controllers\EmployeeContractController::class)->only(['index', 'store', 'update']);
    Route::resource('payrolls', \App\Http\Controllers\PayrollController::class)->only(['index', 'show']);
    Route::post('payrolls/generate', [\App\Http\Controllers\PayrollController::class, 'generate'])->name('payrolls.generate');
    Route::get('payrolls/{payroll}/download-pdf', [\App\Http\Controllers\PayrollController::class, 'downloadPdf'])->name('payrolls.download-pdf');

    // THR (Tunjangan Hari Raya)
    Route::get('payrolls/thr', [\App\Http\Controllers\PayrollController::class, 'thrIndex'])->name('payrolls.thr');
    Route::post('payrolls/thr/preview', [\App\Http\Controllers\PayrollController::class, 'previewThr'])->name('payrolls.thr.preview');
    Route::post('payrolls/thr/generate', [\App\Http\Controllers\PayrollController::class, 'generateThr'])->name('payrolls.thr.generate');

    // Payroll Approvals
    Route::get('payroll-approvals', [\App\Http\Controllers\PayrollApprovalController::class, 'index'])->name('payroll-approvals.index');
    Route::post('payroll-approvals/{approval}/approve', [\App\Http\Controllers\PayrollApprovalController::class, 'approve'])->name('payroll-approvals.approve');
    Route::post('payroll-approvals/{approval}/reject', [\App\Http\Controllers\PayrollApprovalController::class, 'reject'])->name('payroll-approvals.reject');

    // Payroll Actions
    Route::post('payrolls/submit-for-approval', [\App\Http\Controllers\PayrollController::class, 'submitForApproval'])->name('payrolls.submit-approval');
    Route::get('payrolls/export/{batch}/bank-transfer', [\App\Http\Controllers\PayrollController::class, 'exportBankTransfer'])->name('payrolls.export-bank');

    // Invoicing & Client Billing
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    Route::get('invoices/{invoice}/export-efaktur-csv', [\App\Http\Controllers\InvoiceController::class, 'exportEfakturCsv'])->name('invoices.export-efaktur-csv');
    Route::post('invoices/efaktur/batch', [\App\Http\Controllers\EfakturController::class, 'createBatch'])->name('invoices.efaktur.batch-create');
    Route::get('invoices/efaktur/batches/{batch}/csv', [\App\Http\Controllers\EfakturController::class, 'downloadCsv'])->name('invoices.efaktur.batch-csv');
    Route::post('invoices/{invoice}/efaktur/api/submit', [\App\Http\Controllers\EfakturApiController::class, 'submit'])->name('invoices.efaktur.api.submit');
    Route::get('invoices/efaktur/api/submissions/{submission}/status', [\App\Http\Controllers\EfakturApiController::class, 'status'])->name('invoices.efaktur.api.status');
    Route::get('finance/efaktur', [\App\Http\Controllers\EfakturUiController::class, 'index'])->name('finance.efaktur.index');
    Route::get('finance/efaktur/submit', [\App\Http\Controllers\EfakturUiController::class, 'submitPage'])->name('finance.efaktur.submit');
    Route::get('finance/efaktur/status', [\App\Http\Controllers\EfakturUiController::class, 'status'])->name('finance.efaktur.status');
    Route::post('invoices/{invoice}/submit-for-approval', [\App\Http\Controllers\InvoiceController::class, 'submitForApproval'])->name('invoices.submit-for-approval');
    Route::post('invoices/{invoice}/approve', [\App\Http\Controllers\InvoiceController::class, 'approve'])->name('invoices.approve');
    Route::post('invoices/{invoice}/reject', [\App\Http\Controllers\InvoiceController::class, 'reject'])->name('invoices.reject');
    Route::post('invoices/{invoice}/mark-as-paid', [\App\Http\Controllers\InvoiceController::class, 'markAsPaid'])->name('invoices.mark-as-paid');

    // Finance - RKAP
    Route::resource('budgets', \App\Http\Controllers\BudgetController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('budgets/{budget}/import', [\App\Http\Controllers\BudgetController::class, 'import'])->name('budgets.import');
    Route::post('budgets/{budget}/lines', [\App\Http\Controllers\BudgetLineController::class, 'store'])->name('budgets.lines.store');
    Route::put('budgets/{budget}/lines/{line}', [\App\Http\Controllers\BudgetLineController::class, 'update'])->name('budgets.lines.update');
    Route::delete('budgets/{budget}/lines/{line}', [\App\Http\Controllers\BudgetLineController::class, 'destroy'])->name('budgets.lines.destroy');

    // Finance & Petty Cash
    Route::resource('petty-cash', \App\Http\Controllers\PettyCashAccountController::class);
    Route::post('petty-cash/transactions', [\App\Http\Controllers\PettyCashTransactionController::class, 'store'])->name('petty-cash.transactions.store');

    // Cooperative ERP
    Route::prefix('cooperative')->name('cooperative.')->group(function () {
        Route::middleware('can:view_cooperative_member')->group(function () {
            Route::get('members/export', [\App\Http\Controllers\Cooperative\CooperativeMemberController::class, 'export'])->name('members.export');
            Route::resource('members', \App\Http\Controllers\Cooperative\CooperativeMemberController::class);
            Route::post('members/{member}/activate', [\App\Http\Controllers\Cooperative\CooperativeMemberController::class, 'activate'])->name('members.activate');
            Route::post('members/{member}/deactivate', [\App\Http\Controllers\Cooperative\CooperativeMemberController::class, 'deactivate'])->name('members.deactivate');
            Route::post('members/{member}/resign', [\App\Http\Controllers\Cooperative\CooperativeMemberController::class, 'resign'])->name('members.resign');
        });

        Route::post('members/{member}/validate', [\App\Http\Controllers\Cooperative\CooperativeMemberValidationController::class, 'approve'])->name('members.validate');
        Route::post('members/{member}/approve-final', [\App\Http\Controllers\Cooperative\CooperativeMemberValidationController::class, 'approveFinal'])->name('members.approve-final');
        Route::post('members/{member}/request-revision', [\App\Http\Controllers\Cooperative\CooperativeMemberValidationController::class, 'requestRevision'])->name('members.request-revision');
        Route::post('members/{member}/reject', [\App\Http\Controllers\Cooperative\CooperativeMemberValidationController::class, 'reject'])->name('members.reject');

        // Wizard Saldo Awal Anggota (opening balance)
        Route::prefix('members/{member}/opening-balance')->name('members.opening-balance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::class, 'show'])
                ->middleware('can:manage_cooperative_opening_balance')
                ->name('show');
            Route::post('/preview', [\App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::class, 'preview'])
                ->middleware('can:manage_cooperative_opening_balance')
                ->name('preview');
            Route::post('/draft', [\App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::class, 'store'])
                ->middleware('can:manage_cooperative_opening_balance')
                ->name('store');
        });
        Route::post('opening-balances/{batch}/post', [\App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::class, 'post'])
            ->middleware('can:approve_cooperative_opening_balance')
            ->name('opening-balances.post');
        Route::post('opening-balances/{batch}/void', [\App\Http\Controllers\Cooperative\CooperativeOpeningBalanceWizardController::class, 'void'])
            ->middleware('can:void_cooperative_opening_balance')
            ->name('opening-balances.void');

        Route::middleware('can:manage_cooperative_dues')->group(function () {
            Route::get('dues', [\App\Http\Controllers\Cooperative\CooperativeDuesController::class, 'index'])->name('dues.index');
            Route::post('dues/generate', [\App\Http\Controllers\Cooperative\CooperativeDuesController::class, 'generate'])->name('dues.generate');
            Route::post('dues/mark-paid', [\App\Http\Controllers\Cooperative\CooperativeDuesController::class, 'markPaid'])->name('dues.mark-paid');
            Route::post('dues/{invoice}/mark-unpaid', [\App\Http\Controllers\Cooperative\CooperativeDuesController::class, 'markUnpaid'])->name('dues.mark-unpaid');
        });

        Route::middleware('can:manage_cooperative_payment')->group(function () {
            Route::get('payments', [\App\Http\Controllers\Cooperative\CooperativePaymentController::class, 'index'])->name('payments.index');
            Route::post('payments', [\App\Http\Controllers\Cooperative\CooperativePaymentController::class, 'store'])->name('payments.store');
            Route::post('payments/{payment}/approve', [\App\Http\Controllers\Cooperative\CooperativePaymentController::class, 'approve'])->name('payments.approve');
            Route::post('payments/bulk-approve', [\App\Http\Controllers\Cooperative\CooperativePaymentController::class, 'bulkApprove'])->name('payments.bulk-approve');
        });

        Route::get('ledger', [\App\Http\Controllers\Cooperative\CooperativeLedgerController::class, 'index'])
            ->middleware('can:view_cooperative_ledger')
            ->name('ledger.index');
        Route::middleware('can:manage_cooperative_ledger')->group(function () {
            Route::post('ledger/{entry}/cancel-payment', [\App\Http\Controllers\Cooperative\CooperativeLedgerController::class, 'cancelPayment'])->name('ledger.cancel-payment');
            Route::post('ledger/{entry}/revise-payment', [\App\Http\Controllers\Cooperative\CooperativeLedgerController::class, 'revisePayment'])->name('ledger.revise-payment');
        });

        Route::middleware('can:manage_cooperative_loan_types')->group(function () {
            Route::get('loan-types', [\App\Http\Controllers\Cooperative\LoanTypeController::class, 'index'])->name('loan-types.index');
            Route::post('loan-types', [\App\Http\Controllers\Cooperative\LoanTypeController::class, 'store'])->name('loan-types.store');
            Route::put('loan-types/{loan_type}', [\App\Http\Controllers\Cooperative\LoanTypeController::class, 'update'])->name('loan-types.update');
            Route::delete('loan-types/{loan_type}', [\App\Http\Controllers\Cooperative\LoanTypeController::class, 'destroy'])->name('loan-types.destroy');
        });

        Route::middleware('can:view_cooperative_loan')->group(function () {
            Route::get('loans/calculator', [\App\Http\Controllers\Cooperative\LoanController::class, 'calculator'])->name('loans.calculator');
            Route::get('loans', [\App\Http\Controllers\Cooperative\LoanController::class, 'index'])->name('loans.index');
            Route::get('loans/create', [\App\Http\Controllers\Cooperative\LoanController::class, 'create'])->name('loans.create');
            Route::post('loans', [\App\Http\Controllers\Cooperative\LoanController::class, 'store'])->name('loans.store');
            Route::get('loans/{loan}', [\App\Http\Controllers\Cooperative\LoanController::class, 'show'])->name('loans.show');
            Route::post('loans/{loan}/approve', [\App\Http\Controllers\Cooperative\LoanController::class, 'approve'])->name('loans.approve');
            Route::post('loans/{loan}/reject', [\App\Http\Controllers\Cooperative\LoanController::class, 'reject'])->name('loans.reject');
            Route::post('loans/{loan}/disburse', [\App\Http\Controllers\Cooperative\LoanController::class, 'disburse'])->name('loans.disburse');
            Route::post('loans/{loan}/payments', [\App\Http\Controllers\Cooperative\LoanController::class, 'pay'])->name('loans.pay');
        });

        Route::get('points', [\App\Http\Controllers\Cooperative\PointController::class, 'index'])
            ->middleware('can:manage_cooperative_points')
            ->name('points.index');

        Route::middleware('can:manage_cooperative_rewards')->group(function () {
            Route::get('rewards', [\App\Http\Controllers\Cooperative\RewardController::class, 'index'])->name('rewards.index');
            Route::post('rewards', [\App\Http\Controllers\Cooperative\RewardController::class, 'store'])->name('rewards.store');
            Route::put('rewards/{reward}', [\App\Http\Controllers\Cooperative\RewardController::class, 'update'])->name('rewards.update');
            Route::delete('rewards/{reward}', [\App\Http\Controllers\Cooperative\RewardController::class, 'destroy'])->name('rewards.destroy');
        });

        Route::middleware('can:manage_cooperative_redemption')->group(function () {
            Route::get('redemptions', [\App\Http\Controllers\Cooperative\RewardRedemptionController::class, 'index'])->name('redemptions.index');
            Route::get('redemptions/{redemption}', [\App\Http\Controllers\Cooperative\RewardRedemptionController::class, 'show'])->name('redemptions.show');
            Route::put('redemptions/{redemption}/status', [\App\Http\Controllers\Cooperative\RewardRedemptionController::class, 'updateStatus'])->name('redemptions.update-status');
        });

        Route::middleware('can:manage_cooperative_shu')->group(function () {
            Route::get('shu', [\App\Http\Controllers\Cooperative\AnnualShuController::class, 'index'])->name('shu.index');
            Route::post('shu/close', [\App\Http\Controllers\Cooperative\AnnualShuController::class, 'close'])->name('shu.close');
            Route::post('shu/{period}/request-revision', [\App\Http\Controllers\Cooperative\AnnualShuController::class, 'requestRevision'])->name('shu.request-revision');
        });

        Route::middleware('can:access_cooperative_pos')->group(function () {
            Route::get('pos', [\App\Http\Controllers\Cooperative\PosRegisterController::class, 'index'])->name('pos.index');
            Route::post('pos/transactions', [\App\Http\Controllers\Cooperative\PosRegisterController::class, 'store'])->name('pos.transactions.store');
            Route::get('pos/transactions', [\App\Http\Controllers\Cooperative\PosTransactionHistoryController::class, 'index'])->name('pos.transactions.index');
            Route::get('pos/transactions/{transaction}', [\App\Http\Controllers\Cooperative\PosTransactionHistoryController::class, 'show'])->name('pos.transactions.show');
            Route::get('pos/transactions/{transaction}/receipt', [\App\Http\Controllers\Cooperative\PosTransactionReceiptController::class, 'show'])->name('pos.transactions.receipt');
            Route::get('pos/transactions/{transaction}/receipt.pdf', [\App\Http\Controllers\Cooperative\PosTransactionReceiptController::class, 'pdf'])->name('pos.transactions.receipt.pdf');
            Route::post('pos/transactions/{transaction}/void-request', [\App\Http\Controllers\Cooperative\PosVoidController::class, 'store'])->name('pos.void-requests.store');
            Route::post('pos/void-requests/{voidRequest}/process', [\App\Http\Controllers\Cooperative\PosVoidController::class, 'process'])->name('pos.void-requests.process')
                ->middleware('can:approve_pos_void');
            Route::get('pos/void-requests', [\App\Http\Controllers\Cooperative\PosVoidController::class, 'index'])->name('pos.void-requests.index')
                ->middleware('can:approve_pos_void');
            Route::get('pos/transactions/{transaction}/returns/create', [\App\Http\Controllers\Cooperative\PosReturnController::class, 'create'])->name('pos.returns.create');
            Route::post('pos/transactions/{transaction}/returns', [\App\Http\Controllers\Cooperative\PosReturnController::class, 'store'])->name('pos.returns.store');
            Route::get('pos/members/{member}/credit/pay', [\App\Http\Controllers\Cooperative\PosMemberCreditController::class, 'create'])->name('pos.credit.create');
            Route::post('pos/members/{member}/credit/pay', [\App\Http\Controllers\Cooperative\PosMemberCreditController::class, 'store'])->name('pos.credit.store');
            Route::get('pos/coffee-orders', [\App\Http\Controllers\Cooperative\CoffeeOrderController::class, 'index'])->name('pos.coffee-orders.index');
            Route::put('pos/coffee-orders/{coffeeOrder}/status', [\App\Http\Controllers\Cooperative\CoffeeOrderController::class, 'updateStatus'])->name('pos.coffee-orders.update-status');
        });

        Route::get('pos/shu', [\App\Http\Controllers\Cooperative\PosAnnualShuController::class, 'index'])
            ->middleware('can:manage_pos_shu')
            ->name('pos.shu.index');

        Route::resource('pos-categories', \App\Http\Controllers\Cooperative\PosCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('can:manage_pos_categories')
            ->parameters(['pos-categories' => 'category']);
        Route::resource('pos-products', \App\Http\Controllers\Cooperative\PosProductController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->middleware('can:manage_pos_products')
            ->parameters(['pos-products' => 'product']);
        Route::get('pos/reports', [\App\Http\Controllers\Cooperative\PosReportController::class, 'index'])
            ->middleware('can:view_pos_reports')
            ->name('pos.reports.index');
        Route::get('pos/reports/export.csv', [\App\Http\Controllers\Cooperative\PosReportController::class, 'exportCsv'])
            ->middleware('can:view_pos_reports')
            ->name('pos.reports.export.csv');
        Route::post('pos/reports/export.pdf', [\App\Http\Controllers\Cooperative\PosReportController::class, 'enqueuePdf'])
            ->middleware('can:view_pos_reports')
            ->name('pos.reports.export.pdf.enqueue');
        Route::get('pos/reports/export.pdf/jobs/{job}/status', [\App\Http\Controllers\Cooperative\PosReportController::class, 'pdfStatus'])
            ->middleware('can:view_pos_reports')
            ->name('pos.reports.export.pdf.status');
        Route::get('pos/reports/export.pdf/jobs/{job}/download', [\App\Http\Controllers\Cooperative\PosReportController::class, 'pdfDownload'])
            ->middleware('can:view_pos_reports')
            ->name('pos.reports.export.pdf.download');

        Route::get('pos/shifts', [\App\Http\Controllers\Cooperative\PosShiftController::class, 'index'])
            ->middleware('can:access_cooperative_pos')
            ->name('pos.shifts.index');
        Route::post('pos/shifts/open', [\App\Http\Controllers\Cooperative\PosShiftController::class, 'open'])
            ->middleware('can:access_cooperative_pos')
            ->name('pos.shifts.open');
        Route::post('pos/shifts/{shift}/close', [\App\Http\Controllers\Cooperative\PosShiftController::class, 'close'])
            ->middleware('can:manage_pos_products')
            ->name('pos.shifts.close');

        Route::get('pos/closings', [\App\Http\Controllers\Cooperative\PosDailyClosingController::class, 'index'])
            ->middleware('can:view_pos_reports')
            ->name('pos.closings.index');
        Route::post('pos/closings', [\App\Http\Controllers\Cooperative\PosDailyClosingController::class, 'close'])
            ->middleware('can:view_pos_reports')
            ->name('pos.closings.close');

        Route::post('pos-products/{product}/adjust-stock', [\App\Http\Controllers\Cooperative\PosProductController::class, 'adjustStock'])
            ->middleware('can:manage_pos_products')
            ->name('pos-products.adjust-stock');

        Route::middleware('can:manage_pos_products')->group(function () {
            Route::get('pos/inventory/receipts', [\App\Http\Controllers\Cooperative\PosInventoryReceiptController::class, 'index'])->name('pos.inventory.receipts.index');
            Route::get('pos/inventory/receipts/create', [\App\Http\Controllers\Cooperative\PosInventoryReceiptController::class, 'create'])->name('pos.inventory.receipts.create');
            Route::post('pos/inventory/receipts', [\App\Http\Controllers\Cooperative\PosInventoryReceiptController::class, 'store'])->name('pos.inventory.receipts.store');
            Route::get('pos/inventory/transfers', [\App\Http\Controllers\Cooperative\PosInventoryTransferController::class, 'index'])->name('pos.inventory.transfers.index');
            Route::get('pos/inventory/transfers/create', [\App\Http\Controllers\Cooperative\PosInventoryTransferController::class, 'create'])->name('pos.inventory.transfers.create');
            Route::post('pos/inventory/transfers', [\App\Http\Controllers\Cooperative\PosInventoryTransferController::class, 'store'])->name('pos.inventory.transfers.store');
            Route::get('pos/inventory/counts', [\App\Http\Controllers\Cooperative\PosInventoryCountController::class, 'index'])->name('pos.inventory.counts.index');
            Route::get('pos/inventory/counts/create', [\App\Http\Controllers\Cooperative\PosInventoryCountController::class, 'create'])->name('pos.inventory.counts.create');
            Route::post('pos/inventory/counts', [\App\Http\Controllers\Cooperative\PosInventoryCountController::class, 'store'])->name('pos.inventory.counts.store');
            Route::get('pos/inventory/counts/{count}', [\App\Http\Controllers\Cooperative\PosInventoryCountController::class, 'show'])->name('pos.inventory.counts.show');
            Route::post('pos/inventory/counts/{count}/submit', [\App\Http\Controllers\Cooperative\PosInventoryCountController::class, 'submit'])->name('pos.inventory.counts.submit');
            Route::post('pos/inventory/counts/{count}/approve', [\App\Http\Controllers\Cooperative\PosInventoryCountController::class, 'approve'])->name('pos.inventory.counts.approve');
        });
        Route::get('reports', [\App\Http\Controllers\Cooperative\CooperativeReportController::class, 'index'])
            ->middleware('can:view_cooperative_report')
            ->name('reports.index');
        Route::get('operator/dashboard', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'dashboard'])->name('operator.dashboard');
        Route::get('operator/approval-inbox', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'approvalInbox'])->name('operator.approval-inbox');
        Route::get('operator/exceptions', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'exceptions'])->name('operator.exceptions');
        Route::get('operator/analytics', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'analytics'])->name('operator.analytics');
        Route::get('operator/closing', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'closingPage'])->name('operator.closing');
        Route::get('operator/closing/{period}', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'closing'])->name('operator.closing.show');
        Route::post('operator/closing/{period}/steps', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'completeClosingStep'])->name('operator.closing.steps.complete');
        Route::post('operator/closing/{period}/lock', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'lock'])->name('operator.closing.lock');
        Route::post('operator/closing/{period}/unlock', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'unlock'])->name('operator.closing.unlock');
        Route::post('operator/payments/{payment}/reconcile', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'reconcilePayment'])->name('operator.payments.reconcile');
        Route::get('operator/export', [\App\Http\Controllers\Cooperative\OperatorProcedureController::class, 'export'])->name('operator.export');
    });

    // Finance - Bank Batches
    Route::get('finance/bank-batches', [\App\Http\Controllers\FinanceBankController::class, 'index'])->name('finance.bank-batches.index');
    Route::post('finance/bank-batches', [\App\Http\Controllers\FinanceBankController::class, 'store'])->name('finance.bank-batches.store');
    Route::get('finance/bank-batches/{batch}/export', [\App\Http\Controllers\FinanceBankController::class, 'export'])->name('finance.bank-batches.export');
    Route::post('finance/bank-batches/reconcile', [\App\Http\Controllers\FinanceBankController::class, 'reconcile'])->name('finance.bank-batches.reconcile');
    Route::get('finance/bank-reconciliation', [\App\Http\Controllers\BankReconciliationController::class, 'index'])->name('finance.bank-reconciliation.index');
    Route::get('finance/bank-reconciliation/{batch}', [\App\Http\Controllers\BankReconciliationController::class, 'show'])->name('finance.bank-reconciliation.show');
    Route::get('finance/chart-of-accounts', [\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'index'])->name('finance.chart-of-accounts.index');
    Route::post('finance/chart-of-accounts', [\App\Http\Controllers\Accounting\ChartOfAccountController::class, 'store'])->name('finance.chart-of-accounts.store');
    Route::get('finance/journal-entries', [\App\Http\Controllers\Accounting\JournalEntryController::class, 'index'])->name('finance.journal-entries.index');
    Route::post('finance/journal-entries', [\App\Http\Controllers\Accounting\JournalEntryController::class, 'store'])->name('finance.journal-entries.store');
    Route::get('finance/trial-balance', [\App\Http\Controllers\Accounting\FinancialStatementController::class, 'trialBalance'])->name('finance.trial-balance');
    Route::get('finance/balance-sheet', [\App\Http\Controllers\Accounting\FinancialStatementController::class, 'balanceSheet'])->name('finance.balance-sheet');
    Route::get('finance/income-statement', [\App\Http\Controllers\Accounting\FinancialStatementController::class, 'incomeStatement'])->name('finance.income-statement');

    // Finance Closing
    Route::get('finance/closing', [\App\Http\Controllers\Finance\FinanceClosingController::class, 'index'])->name('finance.closing.index');
    Route::get('finance/closing/{period}', [\App\Http\Controllers\Finance\FinanceClosingController::class, 'closing'])->name('finance.closing.show');
    Route::post('finance/closing/{period}/steps', [\App\Http\Controllers\Finance\FinanceClosingController::class, 'completeClosingStep'])->name('finance.closing.steps.complete');
    Route::post('finance/closing/{period}/lock', [\App\Http\Controllers\Finance\FinanceClosingController::class, 'lock'])->name('finance.closing.lock');
    Route::post('finance/closing/{period}/unlock', [\App\Http\Controllers\Finance\FinanceClosingController::class, 'unlock'])->name('finance.closing.unlock');

    // Exceptions Dashboard
    Route::get('exceptions', [\App\Http\Controllers\ExceptionReportController::class, 'index'])->name('exceptions.index');
    Route::get('exceptions/data', [\App\Http\Controllers\ExceptionReportController::class, 'data'])->name('exceptions.data');
    Route::get('exceptions/{module}', [\App\Http\Controllers\ExceptionReportController::class, 'module'])->name('exceptions.module');

    // Monitoring
    Route::middleware('can:manage_cooperative_settings')->group(function () {
        Route::get('/monitoring/health', fn (\App\Monitoring\Health $health) => \Inertia\Inertia::render('Monitoring/Health', [
            'health' => $health->full(),
        ]))->name('monitoring.health');

        Route::get('/monitoring/metrics', [\App\Http\Controllers\Monitoring\MetricsController::class, 'index'])->name('monitoring.metrics');
    });

    // Signed URL document downloads (no auth middleware — validated by signature)
    Route::get('/download/payslip/{id}', [\App\Http\Controllers\DocumentDownloadController::class, 'payslip'])
        ->name('download.payslip')
        ->middleware('signed');
    Route::get('/download/mcu/{mcu}', [\App\Http\Controllers\DocumentDownloadController::class, 'medicalCheckup'])
        ->name('download.mcu')
        ->middleware('signed');
    Route::get('/download/certificate/{employee}/{certificate}', [\App\Http\Controllers\DocumentDownloadController::class, 'certificate'])
        ->name('download.certificate')
        ->middleware('signed');
    Route::get('/download/kyc/{memberId}/{documentId}', [\App\Http\Controllers\DocumentDownloadController::class, 'kyc'])
        ->name('download.kyc')
        ->middleware('signed');
    Route::get('/download/cooperative-receipts/{receipt}', [\App\Http\Controllers\DocumentDownloadController::class, 'cooperativeReceipt'])
        ->name('download.cooperative-receipt')
        ->middleware('signed');

    // Reimbursements
    Route::resource('reimbursements', \App\Http\Controllers\ReimbursementController::class);
    Route::post('reimbursements/{reimbursement}/approve', [\App\Http\Controllers\ReimbursementController::class, 'approve'])->name('reimbursements.approve');
    Route::post('reimbursements/{reimbursement}/reject', [\App\Http\Controllers\ReimbursementController::class, 'reject'])->name('reimbursements.reject');
    Route::post('reimbursements/{reimbursement}/pay', [\App\Http\Controllers\ReimbursementController::class, 'pay'])->name('reimbursements.pay');

    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('vendors', [\App\Http\Controllers\Procurement\VendorController::class, 'index'])->name('vendors.index');
        Route::get('purchase-requests', [\App\Http\Controllers\Procurement\PurchaseRequestController::class, 'index'])->name('prs.index');
        Route::get('purchase-requests/create', [\App\Http\Controllers\Procurement\PurchaseRequestController::class, 'create'])->name('prs.create');
        Route::post('purchase-requests', [\App\Http\Controllers\Procurement\PurchaseRequestController::class, 'store'])->name('prs.store');
        Route::get('purchase-requests/{purchaseRequest}', [\App\Http\Controllers\Procurement\PurchaseRequestController::class, 'show'])->name('prs.show');
        Route::post('purchase-requests/{purchaseRequest}/submit', [\App\Http\Controllers\Procurement\PurchaseRequestController::class, 'submit'])->name('prs.submit');
        Route::post('purchase-requests/{purchaseRequest}/approve', [\App\Http\Controllers\Procurement\PurchaseRequestController::class, 'approve'])->name('prs.approve');
        Route::post('purchase-requests/{purchaseRequest}/reject', [\App\Http\Controllers\Procurement\PurchaseRequestController::class, 'reject'])->name('prs.reject');

        Route::get('purchase-orders', [\App\Http\Controllers\Procurement\PurchaseOrderController::class, 'index'])->name('pos.index');
        Route::post('purchase-orders/from-pr/{purchaseRequest}', [\App\Http\Controllers\Procurement\PurchaseOrderController::class, 'createFromPr'])->name('pos.from-pr');
        Route::get('purchase-orders/{purchaseOrder}', [\App\Http\Controllers\Procurement\PurchaseOrderController::class, 'show'])->name('pos.show');

        Route::get('grns', [\App\Http\Controllers\Procurement\GrnController::class, 'index'])->name('grns.index');
        Route::post('grns/from-po/{purchaseOrder}', [\App\Http\Controllers\Procurement\GrnController::class, 'createFromPo'])->name('grns.from-po');
        Route::get('grns/{goodsReceiveNote}', [\App\Http\Controllers\Procurement\GrnController::class, 'show'])->name('grns.show');
        Route::post('grns/{goodsReceiveNote}/receive', [\App\Http\Controllers\Procurement\GrnController::class, 'receive'])->name('grns.receive');
    });

    // Project Management - Index & Create must come BEFORE nested routes
    Route::get('/projects', [\App\Http\Controllers\ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [\App\Http\Controllers\ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [\App\Http\Controllers\ProjectController::class, 'store'])->name('projects.store');

    // Nested project routes - must come with explicit project parameter
    Route::prefix('projects/{project}')->group(function () {
        // Show/edit/delete must be explicit to avoid conflicts
        Route::get('/', [\App\Http\Controllers\ProjectController::class, 'show'])->name('projects.show');
        Route::get('/edit', [\App\Http\Controllers\ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/', [\App\Http\Controllers\ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/', [\App\Http\Controllers\ProjectController::class, 'destroy'])->name('projects.destroy');

        Route::post('/progress', [\App\Http\Controllers\ProjectController::class, 'updateProgress'])->name('projects.update-progress');

        // Project Financials
        Route::get('/financials', [\App\Http\Controllers\ProjectFinanceController::class, 'index'])->name('projects.financials.index');
        Route::get('/financial-summary', [\App\Http\Controllers\ProjectFinanceController::class, 'summary'])->name('projects.financial-summary');
        Route::get('/budget-analysis', [\App\Http\Controllers\ProjectFinanceController::class, 'budgetAnalysis'])->name('projects.budget-analysis');
        Route::get('/transactions', [\App\Http\Controllers\ProjectFinanceController::class, 'transactions'])->name('projects.transactions');

        // Project Gantt Chart Data
        Route::get('/gantt-data', [\App\Http\Controllers\ProjectGanttController::class, 'getData'])->name('projects.gantt-data');
        Route::post('/gantt-link', [\App\Http\Controllers\ProjectGanttController::class, 'storeLink'])->name('projects.gantt-link.store');
        Route::delete('/gantt-link/{link}', [\App\Http\Controllers\ProjectGanttController::class, 'destroyLink'])->name('projects.gantt-link.destroy');

        Route::resource('tasks', \App\Http\Controllers\ProjectTaskController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('tasks/{task}/progress', [\App\Http\Controllers\ProjectTaskController::class, 'updateProgress'])->name('projects.tasks.update-progress');

        Route::resource('team', \App\Http\Controllers\ProjectTeamController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('team/availability', [\App\Http\Controllers\ProjectTeamController::class, 'availability'])->name('projects.team.availability');
        Route::post('team/bulk-assign', [\App\Http\Controllers\ProjectTeamController::class, 'bulkAssign'])->name('projects.team.bulk-assign');
        Route::post('team/{teamMember}/mobilization', [\App\Http\Controllers\ProjectTeamController::class, 'updateMobilization'])->name('projects.team.mobilization');

        // Project Milestones
        Route::resource('milestones', \App\Http\Controllers\ProjectMilestoneController::class)->except(['create', 'edit', 'show']);
        Route::patch('milestones/{milestone}/progress', [\App\Http\Controllers\ProjectMilestoneController::class, 'updateProgress'])->name('projects.milestones.update-progress');

        // Project Resources (Asset Allocation)
        Route::get('resources', [\App\Http\Controllers\ProjectResourceController::class, 'index'])->name('projects.resources.index');
        Route::post('resources/assets', [\App\Http\Controllers\ProjectResourceController::class, 'storeAsset'])->name('projects.resources.store-asset');
        Route::put('resources/assets/{allocation}', [\App\Http\Controllers\ProjectResourceController::class, 'updateAsset'])->name('projects.resources.update-asset');
        Route::delete('resources/assets/{allocation}', [\App\Http\Controllers\ProjectResourceController::class, 'destroyAsset'])->name('projects.resources.destroy-asset');

        Route::resource('documents', \App\Http\Controllers\ProjectDocumentController::class)->only(['index', 'store', 'destroy']);

        // Reports API
        Route::prefix('api/reports')->group(function () {
            Route::get('/', [\App\Http\Controllers\ReportController::class, 'index']);
            Route::get('/payslip/{employeeId}/{period}', [\App\Http\Controllers\ReportController::class, 'payslip']);
            Route::get('/payroll-summary', [\App\Http\Controllers\ReportController::class, 'payrollSummary']);
            Route::get('/payroll-detail', [\App\Http\Controllers\ReportController::class, 'payrollDetail']);
            Route::get('/attendance', [\App\Http\Controllers\ReportController::class, 'attendanceReport']);
            Route::get('/leave', [\App\Http\Controllers\ReportController::class, 'leaveReport']);
            Route::get('/certificate-compliance', [\App\Http\Controllers\ReportController::class, 'certificateCompliance']);
            Route::get('/mcu-compliance', [\App\Http\Controllers\ReportController::class, 'mcuCompliance']);
        });
    });
});
require __DIR__.'/settings.php';
// require __DIR__.'/auth.php';
