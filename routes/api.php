<?php

use App\Http\Controllers\Api\TechnicianWorkOrderController;
use App\Http\Controllers\Api\V1\CooperativeDuesApiController;
use App\Http\Controllers\Api\V1\CooperativeMemberApiController;
use App\Http\Controllers\Api\V1\CooperativePaymentApiController;
use App\Http\Controllers\Api\V1\PosApiController;
use App\Http\Controllers\Cooperative\CooperativeReportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ComplianceReportController;
use App\Http\Controllers\EmployeeCertificateController;
use App\Http\Controllers\MedicalCheckupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/members', [CooperativeMemberApiController::class, 'index']);
    Route::post('/members', [CooperativeMemberApiController::class, 'store']);
    Route::get('/members/{member}', [CooperativeMemberApiController::class, 'show']);
    Route::put('/members/{member}', [CooperativeMemberApiController::class, 'update']);
    Route::post('/members/{member}/activate', [CooperativeMemberApiController::class, 'activate']);
    Route::post('/members/{member}/resign', [CooperativeMemberApiController::class, 'resign']);
    Route::get('/dues/invoices', [CooperativeDuesApiController::class, 'invoices']);
    Route::post('/dues/generate', [CooperativeDuesApiController::class, 'generate']);
    Route::post('/dues/payments', [CooperativePaymentApiController::class, 'store']);
    Route::post('/dues/payments/{payment}/approve', [CooperativePaymentApiController::class, 'approve']);
    Route::get('/pos/products', [PosApiController::class, 'products']);
    Route::post('/pos/transactions', [PosApiController::class, 'store']);
    Route::get('/reports/cooperative-summary', [CooperativeReportController::class, 'summary']);
    Route::get('/reports/sales', [CooperativeReportController::class, 'sales']);
});

// Technician Mobile API
Route::middleware(['auth:sanctum'])->prefix('technician')->group(function () {
    Route::get('/work-orders', [TechnicianWorkOrderController::class, 'index']);
    Route::get('/work-orders/{id}', [TechnicianWorkOrderController::class, 'show']);
    Route::post('/work-orders/{id}/start', [TechnicianWorkOrderController::class, 'start']);
    Route::post('/work-orders/{id}/complete', [TechnicianWorkOrderController::class, 'complete']);
    Route::post('/work-orders/{id}/checklists/{checklistId}', [TechnicianWorkOrderController::class, 'updateChecklist']);
});

// Employee Certificates API
Route::middleware(['auth:sanctum'])->prefix('employees/{employeeId}')->group(function () {
    Route::get('/certificates', [EmployeeCertificateController::class, 'index']);
    Route::post('/certificates', [EmployeeCertificateController::class, 'store']);
    Route::get('/certificates/{id}', [EmployeeCertificateController::class, 'show']);
    Route::put('/certificates/{id}', [EmployeeCertificateController::class, 'update']);
    Route::delete('/certificates/{id}', [EmployeeCertificateController::class, 'destroy']);
    Route::post('/certificates/{id}/upload', [EmployeeCertificateController::class, 'uploadDocument']);
});

// Medical Checkups API
Route::middleware(['auth:sanctum'])->prefix('employees/{employeeId}')->group(function () {
    Route::get('/mcu', [MedicalCheckupController::class, 'index']);
    Route::post('/mcu', [MedicalCheckupController::class, 'store']);
    Route::get('/mcu/{id}', [MedicalCheckupController::class, 'show']);
    Route::put('/mcu/{id}', [MedicalCheckupController::class, 'update']);
    Route::delete('/mcu/{id}', [MedicalCheckupController::class, 'destroy']);
    Route::post('/mcu/{id}/upload', [MedicalCheckupController::class, 'uploadDocument']);
});

// Compliance Reports API
Route::middleware(['auth:sanctum'])->prefix('reports')->group(function () {
    Route::get('/certificate-compliance', [ComplianceReportController::class, 'certificateCompliance']);
    Route::get('/mcu-compliance', [ComplianceReportController::class, 'mcuCompliance']);
    Route::get('/non-compliant-employees', [ComplianceReportController::class, 'nonCompliantEmployees']);
});

// Audit Logs API (Web session auth for Inertia)
Route::middleware(['auth:web'])->prefix('audit-logs')->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/{id}', [AuditLogController::class, 'show']);
    Route::get('/history/{type}/{id}', [AuditLogController::class, 'history']);
    Route::get('/export', [AuditLogController::class, 'export']);
});
