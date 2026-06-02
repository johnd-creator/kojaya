<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class PruneOperationalRetentionCommand extends Command
{
    protected $signature = 'operations:prune-retention
        {--log-days= : Override log retention days}
        {--audit-days= : Override audit log retention days}
        {--dry-run : Report what would be deleted without deleting anything}';

    protected $description = 'Prune operational log files and audit logs according to configured retention policy';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $logDays = $this->retentionDays('log-days', 'operations.retention.log_days');
        $auditDays = $this->retentionDays('audit-days', 'operations.retention.audit_days');

        $logFiles = $this->prunableLogFiles($logDays);
        $auditCount = $this->prunableAuditLogCount($auditDays);

        if (! $dryRun) {
            foreach ($logFiles as $file) {
                File::delete($file);
            }

            if ($auditCount > 0 && Schema::hasTable('audit_logs')) {
                AuditLog::query()
                    ->where('created_at', '<', now()->subDays($auditDays))
                    ->delete();
            }
        }

        $this->info(($dryRun ? 'Would prune' : 'Pruned')." {$logFiles->count()} log file(s) and {$auditCount} audit log row(s).");

        return self::SUCCESS;
    }

    private function retentionDays(string $option, string $configKey): int
    {
        $value = $this->option($option);

        return max(1, (int) ($value ?: config($configKey)));
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function prunableLogFiles(int $retentionDays): \Illuminate\Support\Collection
    {
        $logPath = storage_path('logs');

        if (! File::isDirectory($logPath)) {
            return collect();
        }

        $cutoff = CarbonImmutable::now()->subDays($retentionDays);

        return collect(File::files($logPath))
            ->filter(fn (\SplFileInfo $file): bool => $this->isManagedLogFile($file->getFilename()))
            ->filter(fn (\SplFileInfo $file): bool => CarbonImmutable::createFromTimestamp($file->getMTime())->lessThan($cutoff))
            ->map(fn (\SplFileInfo $file): string => $file->getPathname())
            ->values();
    }

    private function isManagedLogFile(string $filename): bool
    {
        return preg_match('/^(laravel|worker|scheduler|queue)-.+\.(log|json)$/', $filename) === 1
            || preg_match('/^laravel\.(log|json)\.[0-9]+$/', $filename) === 1;
    }

    private function prunableAuditLogCount(int $retentionDays): int
    {
        if (! Schema::hasTable('audit_logs')) {
            return 0;
        }

        return AuditLog::query()
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->count();
    }
}
