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
        {--execute : Perform the actual file copy and/or cleanup rather than a dry run}
        {--cleanup : Remove verified legacy public source files (runs in dry-run mode without --execute)}
        {--force : Force execution without interactive confirmation in production}';

    protected $description = 'Migrate employee certificate and MCU documents from public to private storage safely and idempotently';

    public function handle(EmployeeDocumentStorage $documentStorage): int
    {
        $isExecute = (bool) $this->option('execute');
        $isCleanup = (bool) $this->option('cleanup');
        $isForce = (bool) $this->option('force');

        if (! $isExecute) {
            $this->info('Running in DRY-RUN mode. No files will be modified or deleted. Pass --execute to apply changes.');
            if ($isCleanup) {
                $this->info('Cleanup flag provided in dry-run mode. Files eligible for cleanup will only be reported.');
            }
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
            'inspected_active' => 0,
            'inspected_soft_deleted' => 0,
            'copied' => 0,
            'already_private' => 0,
            'missing_files' => 0,
            'conflict' => 0,
            'invalid_path' => 0,
            'failed' => 0,
            'cleaned' => 0,
            'eligible_for_cleanup' => 0,
            'public_orphans' => 0,
        ];

        $referencedPublicPaths = [];

        $this->info('Starting employee document migration and inventory...');

        // 1. Process Employee Certificates (including soft-deleted records)
        EmployeeCertificate::withTrashed()
            ->whereNotNull('document_path')
            ->where('document_path', '!=', '')
            ->chunkById(100, function ($certificates) use ($documentStorage, $sourceDisk, $targetDisk, $isExecute, $isCleanup, &$stats, &$referencedPublicPaths) {
                foreach ($certificates as $cert) {
                    $this->processDocumentPath(
                        $cert->document_path,
                        EmployeeDocumentStorage::PREFIX_CERTIFICATES,
                        (string) $cert->employee_id,
                        'EmployeeCertificate',
                        $cert->id,
                        $cert->trashed(),
                        $documentStorage,
                        $sourceDisk,
                        $targetDisk,
                        $isExecute,
                        $isCleanup,
                        $stats,
                        $referencedPublicPaths
                    );
                }
            });

        // 2. Process Medical Checkups (including soft-deleted records)
        MedicalCheckup::withTrashed()
            ->whereNotNull('document_path')
            ->where('document_path', '!=', '')
            ->chunkById(100, function ($mcus) use ($documentStorage, $sourceDisk, $targetDisk, $isExecute, $isCleanup, &$stats, &$referencedPublicPaths) {
                foreach ($mcus as $mcu) {
                    $this->processDocumentPath(
                        $mcu->document_path,
                        EmployeeDocumentStorage::PREFIX_MCU,
                        (string) $mcu->employee_id,
                        'MedicalCheckup',
                        $mcu->id,
                        $mcu->trashed(),
                        $documentStorage,
                        $sourceDisk,
                        $targetDisk,
                        $isExecute,
                        $isCleanup,
                        $stats,
                        $referencedPublicPaths
                    );
                }
            });

        // 3. Inventory Public Files to Detect Unreferenced Orphans
        $publicCertFiles = Storage::disk($sourceDisk)->allFiles(EmployeeDocumentStorage::PREFIX_CERTIFICATES);
        $publicMcuFiles = Storage::disk($sourceDisk)->allFiles(EmployeeDocumentStorage::PREFIX_MCU);
        $allPublicFiles = array_merge($publicCertFiles, $publicMcuFiles);

        foreach ($allPublicFiles as $pubFile) {
            if (! isset($referencedPublicPaths[$pubFile])) {
                $stats['public_orphans']++;
                $this->warn("Unreferenced public orphan file detected: [{$pubFile}]");
            }
        }

        // Display results table
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Active Records Inspected', $stats['inspected_active']],
                ['Soft-Deleted Records Inspected', $stats['inspected_soft_deleted']],
                [$isExecute ? 'Copied to Private Disk' : 'Eligible to Copy (Dry-Run)', $stats['copied']],
                ['Already Verified on Private Disk', $stats['already_private']],
                ['Missing Files (Source & Target Absent)', $stats['missing_files']],
                ['Invalid / Mismatched Ownership Paths', $stats['invalid_path']],
                ['Conflicts / Checksum Mismatches', $stats['conflict']],
                ['Failed Operations', $stats['failed']],
                [$isExecute && $isCleanup ? 'Cleaned from Public Disk' : 'Eligible for Cleanup', $isExecute && $isCleanup ? $stats['cleaned'] : $stats['eligible_for_cleanup']],
                ['Unreferenced Public Orphan Files', $stats['public_orphans']],
            ]
        );

        if ($stats['conflict'] > 0 || $stats['invalid_path'] > 0 || $stats['failed'] > 0 || $stats['missing_files'] > 0 || $stats['public_orphans'] > 0) {
            $this->error(
                "Migration finished with unresolved issues: {$stats['conflict']} conflict(s), {$stats['invalid_path']} invalid path(s), {$stats['failed']} failure(s), {$stats['missing_files']} missing file(s), {$stats['public_orphans']} public orphan(s)."
            );

            return self::FAILURE;
        }

        $this->info('Migration completed successfully.');

        return self::SUCCESS;
    }

    private function processDocumentPath(
        string $path,
        string $expectedPrefix,
        string $expectedEmployeeId,
        string $recordType,
        int|string $recordId,
        bool $isTrashed,
        EmployeeDocumentStorage $documentStorage,
        string $sourceDisk,
        string $targetDisk,
        bool $isExecute,
        bool $isCleanup,
        array &$stats,
        array &$referencedPublicPaths
    ): void {
        if ($isTrashed) {
            $stats['inspected_soft_deleted']++;
        } else {
            $stats['inspected_active']++;
        }

        $referencedPublicPaths[$path] = true;

        try {
            $documentStorage->validateOwnedPath($path, $expectedPrefix, $expectedEmployeeId);
        } catch (Throwable $e) {
            $stats['invalid_path']++;
            $this->error("Invalid path or ownership mismatch for {$recordType} #{$recordId}: {$e->getMessage()}");

            return;
        }

        $sourceExists = Storage::disk($sourceDisk)->exists($path);
        $targetExists = Storage::disk($targetDisk)->exists($path);

        if (! $sourceExists && ! $targetExists) {
            $stats['missing_files']++;
            $this->warn("Missing document file on both source and target for {$recordType} #{$recordId}");

            return;
        }

        if ($targetExists) {
            $targetSize = Storage::disk($targetDisk)->size($path);
            if ($targetSize === 0) {
                $stats['conflict']++;
                $this->error("Destination file is 0 bytes for {$recordType} #{$recordId}");

                return;
            }

            if ($sourceExists) {
                $sourceSize = Storage::disk($sourceDisk)->size($path);
                $sourceHash = $this->calculateChecksum($sourceDisk, $path);
                $targetHash = $this->calculateChecksum($targetDisk, $path);

                if ($sourceSize === $targetSize && $sourceHash === $targetHash && $targetSize > 0) {
                    $stats['already_private']++;
                    if ($isCleanup) {
                        if ($isExecute) {
                            $deleted = Storage::disk($sourceDisk)->delete($path);
                            if ($deleted && Storage::disk($sourceDisk)->exists($path) === false) {
                                $stats['cleaned']++;
                            } else {
                                $stats['failed']++;
                                $this->error("Failed to delete public source after verification for {$recordType} #{$recordId}");
                            }
                        } else {
                            $stats['eligible_for_cleanup']++;
                        }
                    }
                } else {
                    $stats['conflict']++;
                    $this->error("Destination collision / checksum mismatch for {$recordType} #{$recordId}");
                }
            } else {
                $stats['already_private']++;
            }

            return;
        }

        // Target does not exist, source exists
        $sourceSize = Storage::disk($sourceDisk)->size($path);
        if ($sourceSize === 0) {
            $stats['conflict']++;
            $this->error("Source file on public disk is 0 bytes for {$recordType} #{$recordId}");

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
                $this->error("Failed writing destination file for {$recordType} #{$recordId}");

                return;
            }

            $targetSize = Storage::disk($targetDisk)->size($path);
            $targetHash = $this->calculateChecksum($targetDisk, $path);

            if ($targetSize !== $sourceSize || $targetHash !== $sourceHash) {
                Storage::disk($targetDisk)->delete($path);
                $stats['failed']++;
                $this->error("Post-copy verification mismatch for {$recordType} #{$recordId}");

                return;
            }

            $stats['copied']++;

            if ($isCleanup) {
                $deleted = Storage::disk($sourceDisk)->delete($path);
                if ($deleted && Storage::disk($sourceDisk)->exists($path) === false) {
                    $stats['cleaned']++;
                } else {
                    $stats['failed']++;
                    $this->error("Failed to delete public source after copy verification for {$recordType} #{$recordId}");
                }
            }
        } catch (Throwable $e) {
            if (Storage::disk($targetDisk)->exists($path)) {
                Storage::disk($targetDisk)->delete($path);
            }
            $stats['failed']++;
            $this->error("Exception during document migration for {$recordType} #{$recordId}: {$e->getMessage()}");
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
