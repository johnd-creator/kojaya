<?php

namespace App\Console\Commands;

use App\Models\ProjectDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateProjectDocumentsToPrivateStorageCommand extends Command
{
    protected $signature = 'project-documents:migrate-private
        {--dry-run : Simulate migration without modifying files}
        {--force : Force execution without confirmation}';

    protected $description = 'Migrate legacy project documents from public to private storage safely and idempotently';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Running in DRY-RUN mode. No files will be moved or deleted.');
        } else {
            $this->info('Starting project documents migration to private storage...');
        }

        $sourceDisk = 'public';
        $targetDisk = 'project_documents';

        $stats = [
            'migrated' => 0,
            'already_private' => 0,
            'missing_source' => 0,
            'failed' => 0,
            'public_removed' => 0,
        ];

        ProjectDocument::query()->chunkById(100, function ($documents) use (
            $sourceDisk,
            $targetDisk,
            $isDryRun,
            &$stats
        ) {
            foreach ($documents as $doc) {
                $path = $doc->file_path;

                if (! $path || ! is_string($path)) {
                    $stats['missing_source']++;

                    continue;
                }

                // Restrict legacy source paths to expected project-documents/ namespace
                if (
                    str_contains($path, '..') ||
                    str_contains($path, '\\') ||
                    str_starts_with($path, '/') ||
                    ! str_starts_with($path, 'project-documents/')
                ) {
                    $this->error("Invalid path detected for document [{$doc->id}]: {$path}");
                    $stats['failed']++;

                    continue;
                }

                $existsPrivate = Storage::disk($targetDisk)->exists($path);
                $existsPublic = Storage::disk($sourceDisk)->exists($path);

                if ($existsPrivate && ! $existsPublic) {
                    $stats['already_private']++;

                    continue;
                }

                if ($existsPrivate && $existsPublic) {
                    // Both exist: verify destination integrity before removing public
                    try {
                        $sourceContents = Storage::disk($sourceDisk)->get($path);
                        $targetContents = Storage::disk($targetDisk)->get($path);

                        if (hash('sha256', (string) $sourceContents) === hash('sha256', (string) $targetContents)) {
                            if (! $isDryRun) {
                                Storage::disk($sourceDisk)->delete($path);
                                $stats['public_removed']++;
                            } else {
                                $stats['public_removed']++;
                            }
                            $stats['already_private']++;
                        } else {
                            $this->error("Integrity mismatch between public and private for document [{$doc->id}]: {$path}");
                            $stats['failed']++;
                        }
                    } catch (Throwable $e) {
                        $this->error("Error checking integrity for document [{$doc->id}]: {$e->getMessage()}");
                        $stats['failed']++;
                    }

                    continue;
                }

                if (! $existsPrivate && $existsPublic) {
                    if ($isDryRun) {
                        $stats['migrated']++;
                        $stats['public_removed']++;

                        continue;
                    }

                    try {
                        $sourceContents = Storage::disk($sourceDisk)->get($path);
                        $sourceHash = hash('sha256', (string) $sourceContents);

                        // Write to target private disk
                        Storage::disk($targetDisk)->put($path, $sourceContents);

                        // Verify destination exists and integrity matches
                        if (! Storage::disk($targetDisk)->exists($path)) {
                            throw new \RuntimeException("Private file does not exist after write for [{$path}]");
                        }

                        $targetContents = Storage::disk($targetDisk)->get($path);
                        $targetHash = hash('sha256', (string) $targetContents);

                        if ($sourceHash !== $targetHash) {
                            // Cleanup corrupted target
                            Storage::disk($targetDisk)->delete($path);
                            throw new \RuntimeException("Integrity check failed: source hash [{$sourceHash}] != target hash [{$targetHash}]");
                        }

                        // Only then delete public source
                        Storage::disk($sourceDisk)->delete($path);

                        $stats['migrated']++;
                        $stats['public_removed']++;
                    } catch (Throwable $e) {
                        $this->error("Failed to migrate document [{$doc->id}]: {$e->getMessage()}");
                        $stats['failed']++;
                    }

                    continue;
                }

                // Neither private nor public exists
                $stats['missing_source']++;
            }
        });

        $this->table(
            ['Metric', 'Count'],
            [
                ['migrated', $stats['migrated']],
                ['already_private', $stats['already_private']],
                ['missing_source', $stats['missing_source']],
                ['failed', $stats['failed']],
                ['public_removed', $stats['public_removed']],
            ]
        );

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
