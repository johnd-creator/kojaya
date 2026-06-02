<?php

namespace App\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class Health
{
    protected array $checks = [];

    public function full(): array
    {
        $this->checks = [];

        return [
            'status' => $this->overallStatus(),
            'timestamp' => now()->toIso8601String(),
            'components' => [
                'app' => $this->checkApp(),
                'database' => $this->checkDatabase(),
                'queue' => $this->checkQueue(),
                'storage' => $this->checkStorage(),
                'vendor_integrations' => $this->checkVendorIntegrations(),
            ],
            'counts' => $this->counts(),
        ];
    }

    public function liveness(): array
    {
        return [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function checkApp(): array
    {
        return [
            'status' => 'ok',
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ];
    }

    public function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $this->checks[] = ['component' => 'database', 'status' => 'ok'];

            return ['status' => 'ok', 'connection' => DB::connection()->getName()];
        } catch (\Throwable $e) {
            $this->checks[] = ['component' => 'database', 'status' => 'error'];

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $size = Queue::size();

            $this->checks[] = ['component' => 'queue', 'status' => 'ok'];

            return [
                'status' => 'ok',
                'connection' => $connection,
                'pending_jobs' => $size,
            ];
        } catch (\Throwable $e) {
            $this->checks[] = ['component' => 'queue', 'status' => 'error'];

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function checkStorage(): array
    {
        try {
            $disk = config('filesystems.default');
            Storage::disk($disk)->put('health-check-test', 'ok');
            Storage::disk($disk)->delete('health-check-test');

            $this->checks[] = ['component' => 'storage', 'status' => 'ok'];

            return ['status' => 'ok', 'disk' => $disk];
        } catch (\Throwable $e) {
            $this->checks[] = ['component' => 'storage', 'status' => 'error'];

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function checkVendorIntegrations(): array
    {
        $vendors = [];

        try {
            $gateway = app(\App\Services\Integrations\PaymentGatewayService::class);
            $providerName = method_exists($gateway, 'getProviderName')
                ? $gateway->getProviderName()
                : 'unknown';
            $vendors['payment_gateway'] = ['status' => 'ok', 'provider' => $providerName];
        } catch (\Throwable $e) {
            $vendors['payment_gateway'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            app(\App\Services\Integrations\PushNotificationService::class);
            $vendors['push_notification'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $vendors['push_notification'] = ['status' => 'unavailable', 'message' => $e->getMessage()];
        }

        return $vendors;
    }

    public function counts(): array
    {
        return [
            'pending_approvals' => $this->pendingApprovalCount(),
            'failed_jobs' => $this->failedJobsCount(),
            'failed_notification_outboxes' => $this->failedNotificationOutboxesCount(),
            'overdue_loans' => $this->overdueLoanCount(),
        ];
    }

    protected function failedNotificationOutboxesCount(): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('notification_outboxes')) {
                return 0;
            }

            return DB::table('notification_outboxes')->where('status', 'failed')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function failedJobsCount(): int
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

    protected function pendingApprovalCount(): int
    {
        $count = 0;

        try {
            $count += \App\Models\Loan::whereNull('approved_by')->where('status', '!=', 'REJECTED')->count();
        } catch (\Throwable) {
        }

        try {
            $count += \App\Models\Reimbursement::whereNull('approver_id')->where('status', 'PENDING')->count();
        } catch (\Throwable) {
        }

        try {
            $count += \App\Models\Leave::whereNull('approver_id')->where('status', 'PENDING')->count();
        } catch (\Throwable) {
        }

        try {
            $count += \App\Models\OvertimeRequest::whereNull('approved_by')->where('status', 'PENDING')->count();
        } catch (\Throwable) {
        }

        return $count;
    }

    protected function overdueLoanCount(): int
    {
        try {
            return \App\Models\Loan::where('status', 'ACTIVE')
                ->where('due_date', '<', now())
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function overallStatus(): string
    {
        $allOk = collect($this->checks)->every(fn ($c) => ($c['status'] ?? 'ok') === 'ok');

        return $allOk ? 'ok' : 'degraded';
    }
}
