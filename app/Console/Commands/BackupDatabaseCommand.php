<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database
        {--disk= : Filesystem disk for backup output}
        {--directory= : Directory inside the backup disk}
        {--prune : Delete backups older than BACKUP_RETENTION_DAYS}';

    protected $description = 'Create a database backup and optionally prune old backup artifacts';

    public function handle(): int
    {
        $disk = (string) ($this->option('disk') ?: config('operations.backup.disk'));
        $directory = trim((string) ($this->option('directory') ?: config('operations.backup.directory')), '/');

        $path = $this->createBackup($disk, $directory);
        $this->info("Database backup written to {$disk}:{$path}");

        if ($this->option('prune')) {
            $deleted = $this->pruneOldBackups($disk, $directory);
            $this->info("Pruned {$deleted} old backup file(s).");
        }

        return self::SUCCESS;
    }

    private function createBackup(string $disk, string $directory): string
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");
        $driver = $connection['driver'] ?? null;
        $timestamp = now()->format('Ymd-His');

        return match ($driver) {
            'sqlite' => $this->backupSqlite($disk, $directory, $connection, $timestamp),
            'pgsql' => $this->backupWithProcess($disk, $directory, $timestamp, 'dump', $this->pgDumpCommand($connection), [
                'PGPASSWORD' => (string) ($connection['password'] ?? ''),
            ]),
            'mysql', 'mariadb' => $this->backupWithProcess($disk, $directory, $timestamp, 'sql', $this->mysqlDumpCommand($connection), []),
            default => throw new RuntimeException("Unsupported backup driver [{$driver}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function backupSqlite(string $disk, string $directory, array $connection, string $timestamp): string
    {
        $database = (string) ($connection['database'] ?? '');

        if ($database === '' || $database === ':memory:' || ! is_file($database)) {
            throw new RuntimeException('SQLite backup requires a file-backed database.');
        }

        $path = "{$directory}/{$timestamp}-{$this->databaseSlug()}.sqlite";
        Storage::disk($disk)->put($path, fopen($database, 'rb'));

        return $path;
    }

    /**
     * @param  array<int, string>  $command
     * @param  array<string, string>  $environment
     */
    private function backupWithProcess(
        string $disk,
        string $directory,
        string $timestamp,
        string $extension,
        array $command,
        array $environment,
    ): string {
        $tmpDirectory = storage_path('app/private/backups/tmp');
        File::ensureDirectoryExists($tmpDirectory);
        $tmpFile = "{$tmpDirectory}/{$timestamp}-{$this->databaseSlug()}.{$extension}";

        $process = new Process([...$command, $tmpFile], base_path(), array_filter($environment));
        $process->setTimeout(300);
        $process->mustRun();

        $path = "{$directory}/".basename($tmpFile);
        Storage::disk($disk)->put($path, fopen($tmpFile, 'rb'));
        File::delete($tmpFile);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $connection
     * @return array<int, string>
     */
    private function pgDumpCommand(array $connection): array
    {
        return array_values(array_filter([
            'pg_dump',
            '--format=custom',
            '--no-owner',
            '--no-acl',
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? 5432),
            '--username='.($connection['username'] ?? ''),
            '--dbname='.($connection['database'] ?? ''),
            '--file',
        ]));
    }

    /**
     * @param  array<string, mixed>  $connection
     * @return array<int, string>
     */
    private function mysqlDumpCommand(array $connection): array
    {
        return array_values(array_filter([
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? 3306),
            '--user='.($connection['username'] ?? ''),
            ($connection['password'] ?? null) ? '--password='.$connection['password'] : null,
            (string) ($connection['database'] ?? ''),
            '--result-file',
        ]));
    }

    private function pruneOldBackups(string $disk, string $directory): int
    {
        $retentionDays = max(1, (int) config('operations.backup.retention_days'));
        $cutoff = now()->subDays($retentionDays)->getTimestamp();
        $deleted = 0;

        foreach (Storage::disk($disk)->files($directory) as $file) {
            if (Storage::disk($disk)->lastModified($file) >= $cutoff) {
                continue;
            }

            Storage::disk($disk)->delete($file);
            $deleted++;
        }

        return $deleted;
    }

    private function databaseSlug(): string
    {
        return str((string) config('database.connections.'.config('database.default').'.database'))
            ->afterLast(DIRECTORY_SEPARATOR)
            ->replaceMatches('/[^A-Za-z0-9_.-]+/', '-')
            ->trim('-')
            ->lower()
            ->value() ?: 'database';
    }
}
