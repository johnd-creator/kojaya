<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;

class MetricsService
{
    public function pendingApprovals(): array
    {
        return [
            'loan' => $this->safeCount(fn () => \App\Models\Loan::whereNull('approved_by')->where('status', '!=', 'REJECTED')->count()),
            'reimbursement' => $this->safeCount(fn () => \App\Models\Reimbursement::whereNull('approver_id')->where('status', 'PENDING')->count()),
            'leave' => $this->safeCount(fn () => \App\Models\Leave::whereNull('approver_id')->where('status', 'PENDING')->count()),
            'overtime' => $this->safeCount(fn () => \App\Models\OvertimeRequest::whereNull('approved_by')->where('status', 'PENDING')->count()),
            'payroll' => $this->safeCount(fn () => \App\Models\PayrollApproval::whereNull('approver_id')->count()),
            'purchase_request' => $this->safeCount(fn () => \App\Models\PurchaseRequest::where('status', 'PENDING_APPROVAL')->count()),
        ];
    }

    public function failedWebhookCount(): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('webhook_logs')) {
                return 0;
            }

            return DB::table('webhook_logs')
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subDay())
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function failedPushCount(): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('push_notification_logs')) {
                return 0;
            }

            return DB::table('push_notification_logs')
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subDay())
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function failedNotificationOutboxCount(): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('notification_outboxes')) {
                return 0;
            }

            return DB::table('notification_outboxes')
                ->where('status', 'failed')
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function overdueLoanRatio(): float
    {
        $total = \App\Models\Loan::where('status', 'ACTIVE')->count();

        if ($total === 0) {
            return 0.0;
        }

        $overdue = \App\Models\Loan::where('status', 'ACTIVE')
            ->where('due_date', '<', now())
            ->count();

        return round($overdue / $total, 4);
    }

    public function queueFailureCount(): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('failed_jobs')) {
                return 0;
            }

            return DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function slowEndpoints(float $thresholdMs = 1000): array
    {
        return [];
    }

    public function dashboard(): array
    {
        return [
            'pending_approvals' => $this->pendingApprovals(),
            'failed_webhooks_24h' => $this->failedWebhookCount(),
            'failed_pushes_24h' => $this->failedPushCount(),
            'failed_notification_outboxes' => $this->failedNotificationOutboxCount(),
            'overdue_loan_ratio' => $this->overdueLoanRatio(),
            'queue_failures' => $this->queueFailureCount(),
            'slow_endpoints' => $this->slowEndpoints(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function safeCount(callable $callback): int
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return 0;
        }
    }
}
