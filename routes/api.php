<?php

use App\Http\Controllers\Api\TechnicianWorkOrderController;
use App\Http\Controllers\Api\V1\CooperativeDuesApiController;
use App\Http\Controllers\Api\V1\CooperativeMemberApiController;
use App\Http\Controllers\Api\V1\CooperativePaymentApiController;
use App\Http\Controllers\Api\V1\LoanApiController;
use App\Http\Controllers\Api\V1\PosApiController;
use App\Http\Controllers\Api\V1\PointApiController;
use App\Http\Controllers\Api\V1\RewardApiController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ComplianceReportController;
use App\Http\Controllers\Cooperative\CooperativeReportController;
use App\Http\Controllers\EmployeeCertificateController;
use App\Http\Controllers\MedicalCheckupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'ability:profile:read', 'throttle:api']);

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->group(function () {
    Route::get('/members', [CooperativeMemberApiController::class, 'index'])->middleware('ability:cooperative:read');
    Route::post('/members', [CooperativeMemberApiController::class, 'store'])->middleware(['ability:cooperative:write', 'throttle:api-write']);
    Route::get('/members/{member}', [CooperativeMemberApiController::class, 'show'])->middleware('ability:cooperative:read');
    Route::put('/members/{member}', [CooperativeMemberApiController::class, 'update'])->middleware(['ability:cooperative:write', 'throttle:api-write']);
    Route::post('/members/{member}/activate', [CooperativeMemberApiController::class, 'activate'])->middleware(['ability:cooperative:write', 'throttle:api-write']);
    Route::post('/members/{member}/resign', [CooperativeMemberApiController::class, 'resign'])->middleware(['ability:cooperative:write', 'throttle:api-write']);
    Route::get('/dues/invoices', [CooperativeDuesApiController::class, 'invoices'])->middleware('ability:cooperative:read');
    Route::post('/dues/generate', [CooperativeDuesApiController::class, 'generate'])->middleware(['ability:cooperative:write', 'throttle:api-write']);
    Route::post('/dues/payments', [CooperativePaymentApiController::class, 'store'])->middleware(['ability:cooperative:write', 'throttle:api-write']);
    Route::post('/dues/payments/{payment}/approve', [CooperativePaymentApiController::class, 'approve'])->middleware(['ability:cooperative:write', 'throttle:api-write']);
    Route::get('/loans', [LoanApiController::class, 'index'])->middleware('ability:cooperative:read');
    Route::post('/loans/apply', [LoanApiController::class, 'apply'])->middleware(['ability:cooperative:write', 'throttle:api-write']);
    Route::get('/loans/{loan}', [LoanApiController::class, 'show'])->middleware('ability:cooperative:read');
    Route::post('/loans/calculator', [LoanApiController::class, 'calculator'])->middleware(['ability:cooperative:read', 'throttle:api-write']);
    Route::get('/points/balance', [PointApiController::class, 'balance'])->middleware('ability:cooperative:read');
    Route::get('/points/history', [PointApiController::class, 'history'])->middleware('ability:cooperative:read');
    Route::get('/rewards', [RewardApiController::class, 'index'])->middleware('ability:cooperative:read');
    Route::post('/rewards/{reward}/redeem', [RewardApiController::class, 'redeem'])->middleware(['ability:cooperative:write', 'throttle:api-write']);
    Route::get('/pos/products', [PosApiController::class, 'products'])->middleware('ability:pos:read');
    Route::post('/pos/transactions', [PosApiController::class, 'store'])->middleware(['ability:pos:write', 'throttle:api-write']);
    Route::get('/reports/cooperative-summary', [CooperativeReportController::class, 'summary'])->middleware('ability:reports:read');
    Route::get('/reports/sales', [CooperativeReportController::class, 'sales'])->middleware('ability:reports:read');
});

// Technician Mobile API
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('technician')->group(function () {
    Route::get('/work-orders', [TechnicianWorkOrderController::class, 'index'])->middleware('ability:work-orders:read');
    Route::get('/work-orders/{id}', [TechnicianWorkOrderController::class, 'show'])->middleware('ability:work-orders:read');
    Route::post('/work-orders/{id}/start', [TechnicianWorkOrderController::class, 'start'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
    Route::post('/work-orders/{id}/complete', [TechnicianWorkOrderController::class, 'complete'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
    Route::post('/work-orders/{id}/checklists/{checklistId}', [TechnicianWorkOrderController::class, 'updateChecklist'])->middleware(['ability:work-orders:write', 'throttle:api-write']);
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
Route::middleware(['auth:web'])->prefix('audit-logs')->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/{id}', [AuditLogController::class, 'show']);
    Route::get('/history/{type}/{id}', [AuditLogController::class, 'history']);
    Route::get('/export', [AuditLogController::class, 'export']);
});
