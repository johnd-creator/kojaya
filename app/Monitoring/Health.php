<?php

namespace App\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Health
{
    protected array $checks = [];

    public function full(): array
    {
        $this->checks = [];

        $components = [
            'app' => $this->checkApp(),
            'database' => $this->checkDatabase(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'vendor_integrations' => $this->checkVendorIntegrations(),
        ];

        return [
            'status' => $this->overallStatus(),
            'timestamp' => now()->toIso8601String(),
            'components' => $components,
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
        } catch (\Throwable) {
            return $this->failedCheck('database');
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
        } catch (\Throwable) {
            return $this->failedCheck('queue');
        }
    }

    public function checkStorage(): array
    {
        $disk = null;
        $probePath = null;
        $probeAttempted = false;

        try {
            $disk = Storage::disk((string) config('filesystems.default'));
            $probePath = 'health-checks/'.Str::uuid()->toString().'.tmp';
            $probeAttempted = true;

            if ($disk->put($probePath, 'ok') !== true || ! $disk->exists($probePath)) {
                throw new \RuntimeException('Storage health probe write failed.');
            }

            if (! $disk->delete($probePath)) {
                throw new \RuntimeException('Storage health probe cleanup failed.');
            }

            $probeAttempted = false;

            $this->checks[] = ['component' => 'storage', 'status' => 'ok'];

            return ['status' => 'ok'];
        } catch (\Throwable) {
            return $this->failedCheck('storage');
        } finally {
            if ($probeAttempted && $disk !== null && $probePath !== null) {
                try {
                    if ($disk->exists($probePath)) {
                        $disk->delete($probePath);
                    }
                } catch (\Throwable) {
                }
            }
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
            $this->checks[] = ['component' => 'payment_gateway', 'status' => 'ok'];
        } catch (\Throwable) {
            $vendors['payment_gateway'] = $this->failedCheck('payment_gateway');
        }

        try {
            app(\App\Services\Integrations\PushNotificationService::class);
            $vendors['push_notification'] = ['status' => 'ok'];
            $this->checks[] = ['component' => 'push_notification', 'status' => 'ok'];
        } catch (\Throwable) {
            $this->checks[] = ['component' => 'push_notification', 'status' => 'unavailable'];
            Log::warning('Operational health check failed.', ['component' => 'push_notification']);
            $vendors['push_notification'] = [
                'status' => 'unavailable',
                'error_code' => 'PUSH_NOTIFICATION_UNAVAILABLE',
            ];
        }

        return $vendors;
    }

    protected function failedCheck(string $component): array
    {
        $this->checks[] = ['component' => $component, 'status' => 'error'];
        Log::warning('Operational health check failed.', ['component' => $component]);

        return [
            'status' => 'error',
            'error_code' => strtoupper($component).'_UNAVAILABLE',
        ];
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
