<?php

namespace App\Console\Commands;

use App\Models\CooperativePayment;
use App\Services\Cooperative\CooperativePaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MigratePaymentProofsToPrivateCommand extends Command
{
    protected $signature = 'payments:migrate-proofs-to-private
        {--execute : Copy payment proofs and remove verified public copies; without this the command is read-only}
        {--force : Skip the production confirmation prompt}';

    protected $description = 'Safely migrate legacy public cooperative payment proofs to private storage';

    private const LEGACY_PATH_PREFIX = 'cooperative/payment-proofs/';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $privateDiskName = (string) config('filesystems.payment_proof_disk', CooperativePaymentService::PROOF_DISK);

        if ($privateDiskName === '' || $privateDiskName === 'public') {
            $this->error('Payment proof disk must be configured to a private disk other than [public].');

            return self::FAILURE;
        }

        if ($execute && app()->environment('production') && ! $this->option('force')) {
            if (! $this->confirm('Migrate payment proofs and delete verified public copies in production?')) {
                $this->warn('Payment proof migration cancelled.');

                return self::FAILURE;
            }
        }

        $stats = [
            'migrated' => 0,
            'already_private' => 0,
            'missing' => 0,
            'failed' => 0,
            'public_removed' => 0,
            'public_orphans' => 0,
            'would_migrate' => 0,
            'would_remove_public' => 0,
        ];
        $referencedPaths = [];
        $paymentRecordsReadable = true;

        try {
            CooperativePayment::query()
                ->whereNotNull('proof_path')
                ->orderBy('id')
                ->chunkById(100, function ($payments) use ($execute, $privateDiskName, &$referencedPaths, &$stats): void {
                    foreach ($payments as $payment) {
                        $path = $payment->proof_path;

                        if (! $this->isSafeLegacyPath($path)) {
                            $this->error("Payment proof migration rejected an invalid path for payment [{$payment->id}].");
                            $stats['failed']++;

                            continue;
                        }

                        $referencedPaths[$path] = true;

                        try {
                            $privateDisk = Storage::disk($privateDiskName);
                            $publicDisk = Storage::disk('public');
                            $privateExists = $privateDisk->exists($path);
                            $publicExists = $publicDisk->exists($path);

                            if ($privateExists && ! $publicExists) {
                                $stats['already_private']++;

                                continue;
                            }

                            if (! $privateExists && ! $publicExists) {
                                $stats['missing']++;

                                continue;
                            }

                            if ($privateExists && $publicExists) {
                                if (! hash_equals($this->hashFile('public', $path), $this->hashFile($privateDiskName, $path))) {
                                    throw new RuntimeException('Public and private payment proof contents differ.');
                                }

                                if (! $execute) {
                                    $stats['already_private']++;
                                    $stats['would_remove_public']++;

                                    continue;
                                }

                                $this->removePublicCopy($path);
                                $stats['already_private']++;
                                $stats['public_removed']++;

                                continue;
                            }

                            $sourceHash = $this->hashFile('public', $path);

                            if (! $execute) {
                                $stats['would_migrate']++;
                                $stats['would_remove_public']++;

                                continue;
                            }

                            $this->copyToPrivateAndVerify($path, $privateDiskName, $sourceHash);
                            $this->removePublicCopy($path);
                            $stats['migrated']++;
                            $stats['public_removed']++;
                        } catch (Throwable) {
                            // Preserve the public source on any uncertain copy, verification, or delete outcome.
                            $this->error("Payment proof migration failed for payment [{$payment->id}]; inspect both copies before retrying.");
                            $stats['failed']++;
                        }
                    }
                });
        } catch (Throwable) {
            $this->error('Payment proof migration stopped because payment records could not be read safely.');
            $stats['failed']++;
            $paymentRecordsReadable = false;
        }

        if ($paymentRecordsReadable) {
            try {
                foreach (Storage::disk('public')->allFiles(self::LEGACY_PATH_PREFIX) as $publicPath) {
                    if (! isset($referencedPaths[$publicPath])) {
                        $stats['public_orphans']++;
                    }
                }
            } catch (Throwable) {
                $this->error('Payment proof migration could not inventory the legacy public proof directory.');
                $stats['failed']++;
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['migrated', $stats['migrated']],
                ['already_private', $stats['already_private']],
                ['missing', $stats['missing']],
                ['failed', $stats['failed']],
                ['public_removed', $stats['public_removed']],
                ['public_orphans', $stats['public_orphans']],
                ['would_migrate', $stats['would_migrate']],
                ['would_remove_public', $stats['would_remove_public']],
            ]
        );

        if (! $execute) {
            $this->info('Dry run complete. No payment proof files were changed.');
        }

        return $stats['missing'] > 0 || $stats['failed'] > 0 || $stats['public_orphans'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function isSafeLegacyPath(mixed $path): bool
    {
        return is_string($path)
            && str_starts_with($path, self::LEGACY_PATH_PREFIX)
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0")
            && ! str_contains($path, '//');
    }

    private function copyToPrivateAndVerify(string $path, string $privateDisk, string $expectedSourceHash): void
    {
        $sourceStream = Storage::disk('public')->readStream($path);

        if (! is_resource($sourceStream)) {
            throw new RuntimeException('Could not open the public payment proof.');
        }

        try {
            if (! Storage::disk($privateDisk)->writeStream($path, $sourceStream)) {
                throw new RuntimeException('Could not write the private payment proof.');
            }
        } finally {
            fclose($sourceStream);
        }

        if (! Storage::disk($privateDisk)->exists($path)) {
            throw new RuntimeException('The private payment proof is missing after copy.');
        }

        $sourceHashAfterCopy = $this->hashFile('public', $path);
        $privateHash = $this->hashFile($privateDisk, $path);

        if (! hash_equals($expectedSourceHash, $sourceHashAfterCopy)
            || ! hash_equals($expectedSourceHash, $privateHash)) {
            throw new RuntimeException('Payment proof integrity verification failed.');
        }
    }

    private function removePublicCopy(string $path): void
    {
        Storage::disk('public')->delete($path);

        if (Storage::disk('public')->exists($path)) {
            throw new RuntimeException('The verified public payment proof could not be removed.');
        }
    }

    private function hashFile(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Could not read a payment proof for integrity verification.');
        }

        try {
            $context = hash_init('sha256');
            $bytes = hash_update_stream($context, $stream);

            if ($bytes === false) {
                throw new RuntimeException('Could not calculate a payment proof integrity hash.');
            }

            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }
}
