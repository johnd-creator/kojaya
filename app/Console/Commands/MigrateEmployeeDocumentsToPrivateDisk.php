<?php

namespace App\Console\Commands;

use App\Models\EmployeeCertificate;
use App\Models\MedicalCheckup;
use App\Services\Security\EmployeeDocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateEmployeeDocumentsToPrivateDisk extends Command
{
    protected $signature = 'security:migrate-employee-documents-private
        {--execute : Perform the actual file copy rather than a dry run}
        {--cleanup : Remove verified legacy public source files (requires --execute)}
        {--force : Force execution without interactive confirmation in production}';

    protected $description = 'Migrate employee certificate and MCU documents from public to private storage safely and idempotently';

    public function handle(EmployeeDocumentStorage $documentStorage): int
    {
        $isExecute = (bool) $this->option('execute');
        $isCleanup = (bool) $this->option('cleanup');
        $isForce = (bool) $this->option('force');

        if (! $isExecute) {
            $this->info('Running in DRY-RUN mode. No files will be modified or deleted. Pass --execute to apply changes.');
        } else {
            if ($isCleanup && app()->environment('production') && ! $isForce) {
                if (! $this->confirm('Are you sure you want to delete verified public employee document copies in production?')) {
                    $this->warn('Operation cancelled by user.');

                    return self::FAILURE;
                }
            }
        }

        $sourceDisk = EmployeeDocumentStorage::LEGACY_DISK;
        $targetDisk = EmployeeDocumentStorage::DISK;

        $stats = [
            'inspected' => 0,
            'copied' => 0,
            'already_private' => 0,
            'missing_source' => 0,
            'conflict' => 0,
            'failed' => 0,
            'cleaned' => 0,
            'eligible_for_cleanup' => 0,
        ];

        $this->info('Starting employee document migration...');

        // 1. Process Employee Certificates
        EmployeeCertificate::query()
            ->whereNotNull('document_path')
            ->where('document_path', '!=', '')
            ->chunkById(100, function ($certificates) use ($documentStorage, $sourceDisk, $targetDisk, $isExecute, $isCleanup, &$stats) {
                foreach ($certificates as $cert) {
                    $this->processDocumentPath(
                        $cert->document_path,
                        $documentStorage,
                        $sourceDisk,
                        $targetDisk,
                        $isExecute,
                        $isCleanup,
                        $stats
                    );
                }
            });

        // 2. Process Medical Checkups
        MedicalCheckup::query()
            ->whereNotNull('document_path')
            ->where('document_path', '!=', '')
            ->chunkById(100, function ($mcus) use ($documentStorage, $sourceDisk, $targetDisk, $isExecute, $isCleanup, &$stats) {
                foreach ($mcus as $mcu) {
                    $this->processDocumentPath(
                        $mcu->document_path,
                        $documentStorage,
                        $sourceDisk,
                        $targetDisk,
                        $isExecute,
                        $isCleanup,
                        $stats
                    );
                }
            });

        // Display results table
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records Inspected', $stats['inspected']],
                [$isExecute ? 'Copied to Private Disk' : 'Eligible to Copy (Dry-Run)', $stats['copied']],
                ['Already Verified on Private Disk', $stats['already_private']],
                ['Missing Source on Public Disk', $stats['missing_source']],
                ['Conflicts / Validation Errors', $stats['conflict']],
                ['Failed Copies', $stats['failed']],
                [$isExecute && $isCleanup ? 'Cleaned from Public Disk' : 'Eligible for Cleanup', $isExecute && $isCleanup ? $stats['cleaned'] : $stats['eligible_for_cleanup']],
            ]
        );

        if ($stats['conflict'] > 0 || $stats['failed'] > 0) {
            $this->error("Migration finished with {$stats['conflict']} conflict(s) and {$stats['failed']} failure(s).");

            return self::FAILURE;
        }

        $this->info('Migration completed successfully.');

        return self::SUCCESS;
    }

    private function processDocumentPath(
        string $path,
        EmployeeDocumentStorage $documentStorage,
        string $sourceDisk,
        string $targetDisk,
        bool $isExecute,
        bool $isCleanup,
        array &$stats
    ): void {
        $stats['inspected']++;

        try {
            $documentStorage->validatePath($path);
        } catch (Throwable $e) {
            $stats['conflict']++;
            $this->error("Validation error for record path: {$e->getMessage()}");

            return;
        }

        $sourceExists = Storage::disk($sourceDisk)->exists($path);
        $targetExists = Storage::disk($targetDisk)->exists($path);

        if ($targetExists) {
            if ($sourceExists) {
                $sourceSize = Storage::disk($sourceDisk)->size($path);
                $targetSize = Storage::disk($targetDisk)->size($path);
                $sourceHash = $this->calculateChecksum($sourceDisk, $path);
                $targetHash = $this->calculateChecksum($targetDisk, $path);

                if ($sourceSize === $targetSize && $sourceHash === $targetHash && $targetSize > 0) {
                    $stats['already_private']++;
                    if ($isCleanup) {
                        if ($isExecute) {
                            Storage::disk($sourceDisk)->delete($path);
                            $stats['cleaned']++;
                        } else {
                            $stats['eligible_for_cleanup']++;
                        }
                    }
                } else {
                    $stats['conflict']++;
                    $this->error("Destination collision / checksum mismatch for path: {$path}");
                }
            } else {
                $targetSize = Storage::disk($targetDisk)->size($path);
                if ($targetSize > 0) {
                    $stats['already_private']++;
                } else {
                    $stats['conflict']++;
                    $this->error("Destination file is 0 bytes for path: {$path}");
                }
            }

            return;
        }

        // Target does not exist
        if (! $sourceExists) {
            $stats['missing_source']++;

            return;
        }

        $sourceSize = Storage::disk($sourceDisk)->size($path);
        if ($sourceSize === 0) {
            $stats['conflict']++;
            $this->error("Source file on public disk is 0 bytes for path: {$path}");

            return;
        }

        $sourceHash = $this->calculateChecksum($sourceDisk, $path);

        if (! $isExecute) {
            $stats['copied']++;
            if ($isCleanup) {
                $stats['eligible_for_cleanup']++;
            }

            return;
        }

        // Execute copy
        try {
            $stream = Storage::disk($sourceDisk)->readStream($path);
            if (! is_resource($stream)) {
                $contents = Storage::disk($sourceDisk)->get($path);
                $written = Storage::disk($targetDisk)->put($path, $contents);
            } else {
                $written = Storage::disk($targetDisk)->put($path, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! $written || ! Storage::disk($targetDisk)->exists($path)) {
                $stats['failed']++;
                $this->error("Failed writing destination file for path: {$path}");

                return;
            }

            $targetSize = Storage::disk($targetDisk)->size($path);
            $targetHash = $this->calculateChecksum($targetDisk, $path);

            if ($targetSize !== $sourceSize || $targetHash !== $sourceHash) {
                Storage::disk($targetDisk)->delete($path);
                $stats['failed']++;
                $this->error("Post-copy verification mismatch for path: {$path}");

                return;
            }

            $stats['copied']++;

            if ($isCleanup) {
                Storage::disk($sourceDisk)->delete($path);
                $stats['cleaned']++;
            }
        } catch (Throwable $e) {
            if (Storage::disk($targetDisk)->exists($path)) {
                Storage::disk($targetDisk)->delete($path);
            }
            $stats['failed']++;
            $this->error("Exception during document migration for path [{$path}]: {$e->getMessage()}");
        }
    }

    private function calculateChecksum(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);
        if (is_resource($stream)) {
            $ctx = hash_init('sha256');
            hash_update_stream($ctx, $stream);
            fclose($stream);

            return hash_final($ctx);
        }

        return hash('sha256', (string) Storage::disk($disk)->get($path));
    }
}
