<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SQLite3;
use Symfony\Component\Process\Process;

class VerifyDatabaseBackupCommand extends Command
{
    protected $signature = 'backup:verify
        {path? : Backup path inside the selected disk. Defaults to the latest file in the backup directory}
        {--disk= : Filesystem disk containing the backup}
        {--directory= : Directory inside the backup disk}';

    protected $description = 'Verify the latest or selected database backup can be read or restored safely';

    public function handle(): int
    {
        $disk = (string) ($this->option('disk') ?: config('operations.backup.disk'));
        $directory = trim((string) ($this->option('directory') ?: config('operations.backup.directory')), '/');
        $path = (string) ($this->argument('path') ?: $this->latestBackupPath($disk, $directory));

        if ($path === '') {
            $this->error("No backup files found in {$disk}:{$directory}.");

            return self::FAILURE;
        }

        if (! Storage::disk($disk)->exists($path)) {
            $this->error("Backup file does not exist: {$disk}:{$path}");

            return self::FAILURE;
        }

        try {
            $this->verify($disk, $path);
        } catch (\Throwable $exception) {
            $this->error('Backup verification failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Backup verified: {$disk}:{$path}");

        return self::SUCCESS;
    }

    private function latestBackupPath(string $disk, string $directory): ?string
    {
        return collect(Storage::disk($disk)->files($directory))
            ->sortByDesc(fn (string $file): int => Storage::disk($disk)->lastModified($file))
            ->first();
    }

    private function verify(string $disk, string $path): void
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        match ($extension) {
            'sqlite', 'sqlite3', 'db' => $this->verifySqlite($disk, $path),
            'dump' => $this->verifyPostgresDump($disk, $path),
            'sql' => $this->verifySqlDump($disk, $path),
            default => throw new RuntimeException("Unsupported backup file extension [{$extension}]."),
        };
    }

    private function verifySqlite(string $disk, string $path): void
    {
        $tmpDirectory = storage_path('app/private/backups/verify');
        File::ensureDirectoryExists($tmpDirectory);
        $tmpFile = $tmpDirectory.'/'.uniqid('verify-', true).'.sqlite';

        try {
            File::put($tmpFile, Storage::disk($disk)->get($path));

            $database = new SQLite3($tmpFile, SQLITE3_OPEN_READONLY);
            $result = $database->querySingle('PRAGMA integrity_check');
            $database->close();

            if ($result !== 'ok') {
                throw new RuntimeException("SQLite integrity_check returned [{$result}].");
            }
        } finally {
            File::delete($tmpFile);
        }
    }

    private function verifyPostgresDump(string $disk, string $path): void
    {
        $tmpDirectory = storage_path('app/private/backups/verify');
        File::ensureDirectoryExists($tmpDirectory);
        $tmpFile = $tmpDirectory.'/'.basename($path);

        try {
            File::put($tmpFile, Storage::disk($disk)->get($path));

            $process = new Process(['pg_restore', '--list', $tmpFile], base_path());
            $process->setTimeout(120);
            $process->mustRun();
        } finally {
            File::delete($tmpFile);
        }
    }

    private function verifySqlDump(string $disk, string $path): void
    {
        $contents = Storage::disk($disk)->get($path);

        if (! str_contains($contents, 'CREATE') && ! str_contains($contents, 'INSERT')) {
            throw new RuntimeException('SQL dump does not contain CREATE or INSERT statements.');
        }
    }
}
