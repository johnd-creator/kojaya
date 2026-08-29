<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupStatusService;
use Illuminate\Console\Command;
use Throwable;

class BackupStatusCommand extends Command
{
    protected $signature = 'backup:status
        {--disk= : Filesystem disk containing backups}
        {--directory= : Directory inside the disk}
        {--max-age= : Maximum allowable backup age in hours (SLA)}';

    protected $description = 'Check the freshness, integrity, and SLA health of the latest database backup';

    public function __construct(
        private readonly BackupStatusService $statusService = new BackupStatusService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $disk = (string) ($this->option('disk') ?: config('operations.backup.disk', 'local'));
        $directory = trim((string) ($this->option('directory') ?: config('operations.backup.directory', 'backups/database')), '/\\');
        $maxAge = $this->option('max-age') !== null ? (int) $this->option('max-age') : null;

        try {
            $result = $this->statusService->checkStatus(
                disk: $disk,
                directory: $directory,
                maxAgeHours: $maxAge,
            );

            $this->line("Disk:          {$disk}");
            $this->line("Directory:     {$directory}");
            $this->line("Status:        <fg={$this->statusColor($result['status'])}>{$result['status']}</>");
            $this->line('Latest Backup: '.($result['latest_backup'] ?? 'None'));
            $this->line('Age:           '.($result['age_hours'] !== null ? "{$result['age_hours']} hours" : 'N/A'));
            $this->line("SLA Max Age:   {$result['max_age_hours']} hours");

            if ($result['manifest'] !== null) {
                $manifest = $result['manifest'];
                $this->line("Backup ID:     {$manifest->backupId}");
                $this->line("SHA-256:       {$manifest->sha256}");
                $this->line("Created:       {$manifest->createdAt}");
                $this->line("Verified:      {$manifest->verificationStatus}");
                if (! empty($manifest->offsiteCopy['enabled'])) {
                    $offsiteStatus = ! empty($manifest->offsiteCopy['copied']) ? 'REPLICATED' : 'NOT COPIED';
                    $this->line("Off-site:      {$offsiteStatus} ({$manifest->offsiteCopy['disk']})");
                }
            }

            if ($result['is_healthy']) {
                $this->info("✓ {$result['message']}");

                return self::SUCCESS;
            }

            $this->error("✗ {$result['message']}");

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error("Failed to check backup status: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'healthy' => 'green',
            'stale' => 'yellow',
            'corrupt', 'missing' => 'red',
            default => 'white',
        };
    }
}
