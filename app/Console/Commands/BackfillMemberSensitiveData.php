<?php

namespace App\Console\Commands;

use App\Services\Security\MemberSensitiveDataInspector;
use App\Services\Security\PiiCryptoService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class BackfillMemberSensitiveData extends Command
{
    protected $signature = 'members:backfill-sensitive-data
        {--dry-run : Inspect rows without writing}
        {--chunk=250 : Number of members per batch}
        {--from-id= : Resume after this member ID}
        {--limit= : Maximum number of members to process}
        {--resume-token= : Resume after the last ID in a previous report}
        {--repair-missing-index : Repair missing or mismatched encrypted/index metadata}
        {--rotate-to-current : Re-encrypt and re-index readable rows with current versions}
        {--retire-plaintext : Null plaintext only after parity verification}
        {--confirm-retirement : Explicitly confirm plaintext retirement}
        {--confirm-production : Explicitly allow writes when APP_ENV=production}
        {--report= : Write a PII-free JSON report to this path}';

    protected $description = 'Backfill and verify encrypted cooperative member identity data';

    public function handle(
        MemberSensitiveDataInspector $inspector,
        PiiCryptoService $crypto,
    ): int {
        $chunk = max(1, min((int) $this->option('chunk'), 1000));
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $resumeToken = (string) ($this->option('resume-token') ?: $this->option('from-id') ?: '');
        $retirePlaintext = (bool) $this->option('retire-plaintext');
        $confirmRetirement = (bool) $this->option('confirm-retirement');
        $confirmProduction = (bool) $this->option('confirm-production');

        if ($retirePlaintext && ! $crypto->allowsPlaintextRetirement()) {
            $this->error('Plaintext retirement requires the plaintext_retired rollout phase.');

            return self::FAILURE;
        }

        if ($retirePlaintext && ! $confirmRetirement) {
            $this->error('Plaintext retirement requires explicit confirmation.');

            return self::FAILURE;
        }

        if ($retirePlaintext && app()->environment('production') && ! $confirmProduction) {
            $this->error('Plaintext retirement in production requires production confirmation.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run')
            || (app()->environment('production') && ! $confirmProduction);
        $repair = (bool) $this->option('repair-missing-index');
        $rotateToCurrent = (bool) $this->option('rotate-to-current');
        $report = $this->emptyReport($dryRun, $resumeToken, $crypto);
        $failed = false;

        $query = DB::table('cooperative_members')
            ->select('id')
            ->orderBy('id');

        if ($resumeToken !== '') {
            $query->where('id', '>', $resumeToken);
        }

        $query->where(function ($query): void {
            foreach (PiiCryptoService::FIELDS as $field) {
                $query->orWhereNotNull($field)
                    ->orWhereNotNull($field.'_enc')
                    ->orWhereNotNull($field.'_bidx');
            }
        });

        $query->chunkById($chunk, function ($rows) use (
            $inspector,
            $crypto,
            $limit,
            $dryRun,
            $repair,
            $rotateToCurrent,
            $retirePlaintext,
            &$report,
            &$failed,
        ): bool {
            foreach ($rows as $row) {
                if ($limit !== null && $report['processed'] >= $limit) {
                    return false;
                }

                $id = (string) $row->id;
                $report['processed']++;
                $report['last_id'] = $id;

                try {
                    $result = $dryRun
                        ? $this->inspectSnapshot($id, $inspector, $crypto, $repair, $rotateToCurrent, $retirePlaintext)
                        : $this->processLocked($id, $inspector, $crypto, $repair, $rotateToCurrent, $retirePlaintext);
                } catch (QueryException) {
                    $this->error('PII backfill database operation failed.');
                    $report['issues']['write_failure'] = ($report['issues']['write_failure'] ?? 0) + 1;
                    $failed = true;

                    return false;
                } catch (Throwable $exception) {
                    $issue = $exception instanceof RuntimeException
                        ? 'consistency_failure'
                        : 'backfill_failure';
                    $this->error('PII backfill stopped because a row failed closed.');
                    $report['issues'][$issue] = ($report['issues'][$issue] ?? 0) + 1;
                    $failed = true;

                    return false;
                }

                if ($result === null) {
                    continue;
                }

                $this->addInspectionToReport($report, $result['inspection']);

                if ($result['updated']) {
                    $dryRun ? $report['would_update']++ : $report['updated']++;
                }
            }

            return true;
        });

        $this->writeReport($report);
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($failed) {
            return self::FAILURE;
        }

        $this->info($dryRun ? 'PII backfill dry-run completed.' : 'PII backfill completed.');

        return self::SUCCESS;
    }

    /**
     * @return array{inspection: array<string, mixed>, updated: bool}|null
     */
    private function inspectSnapshot(
        string $id,
        MemberSensitiveDataInspector $inspector,
        PiiCryptoService $crypto,
        bool $repair,
        bool $rotateToCurrent,
        bool $retirePlaintext,
    ): ?array {
        $record = DB::table('cooperative_members')->where('id', $id)->first();
        if ($record === null) {
            return null;
        }

        $inspection = $inspector->inspect((array) $record);
        $updates = $this->updatesFor($inspection['fields'], $crypto, $repair, $rotateToCurrent, $retirePlaintext);

        return [
            'inspection' => $inspection,
            'updated' => $updates !== [],
        ];
    }

    /**
     * @return array{inspection: array<string, mixed>, updated: bool}|null
     */
    private function processLocked(
        string $id,
        MemberSensitiveDataInspector $inspector,
        PiiCryptoService $crypto,
        bool $repair,
        bool $rotateToCurrent,
        bool $retirePlaintext,
    ): ?array {
        return retry(
            3,
            function () use ($id, $inspector, $crypto, $repair, $rotateToCurrent, $retirePlaintext): ?array {
                return DB::transaction(function () use ($id, $inspector, $crypto, $repair, $rotateToCurrent, $retirePlaintext): ?array {
                    $record = DB::table('cooperative_members')
                        ->where('id', $id)
                        ->lockForUpdate()
                        ->first();

                    if ($record === null) {
                        return null;
                    }

                    $inspection = $inspector->inspect((array) $record);
                    $updates = $this->updatesFor($inspection['fields'], $crypto, $repair, $rotateToCurrent, $retirePlaintext);

                    if ($updates !== []) {
                        DB::table('cooperative_members')->where('id', $id)->update($updates);
                    }

                    return [
                        'inspection' => $inspection,
                        'updated' => $updates !== [],
                    ];
                });
            },
            100,
            fn (Throwable $exception): bool => $this->isTransientDatabaseException($exception),
        );
    }

    /**
     * @param  array<string, array{status: string, legacy: ?string, decrypted: ?string, envelope_version: ?string, encryption_version: ?string, bidx_version: ?string, issues: list<string>}>  $fields
     * @return array<string, mixed>
     */
    private function updatesFor(
        array $fields,
        PiiCryptoService $crypto,
        bool $repair,
        bool $rotateToCurrent,
        bool $retirePlaintext,
    ): array {
        $updates = [];

        foreach ($fields as $field => $details) {
            $issues = $details['issues'];
            $hardIssues = ['decrypt_failure', 'plaintext_encrypted_mismatch', 'envelope_version_mismatch'];

            if (array_intersect($hardIssues, $issues) !== []) {
                throw new RuntimeException('PII consistency must be repaired before writing.');
            }

            $expectedLegacyState = $details['status'] === 'legacy_only'
                && $issues === ['missing_bidx'];
            $expectedEncryptedOnlyRotationState = $rotateToCurrent
                && $details['status'] === 'encrypted_only'
                && $details['decrypted'] !== null;
            $expectedCompatibilityRepairState = $crypto->keepsPlaintextCompatibilityCopy()
                && $details['status'] === 'encrypted_only'
                && $issues === ['missing_plaintext_compatibility_copy'];
            $expectedRetirementState = $retirePlaintext
                && array_diff($issues, ['plaintext_remaining_after_retirement']) === [];
            $expectedLegacyRetirementState = $retirePlaintext
                && $details['status'] === 'legacy_only'
                && array_diff($issues, ['missing_bidx', 'plaintext_remaining_after_retirement']) === [];
            if ($issues !== [] && ! $repair && ! $expectedLegacyState && ! $expectedEncryptedOnlyRotationState && ! $expectedCompatibilityRepairState && ! $expectedRetirementState && ! $expectedLegacyRetirementState) {
                throw new RuntimeException('PII repair is required for inconsistent metadata.');
            }

            $value = $details['decrypted'] ?? $details['legacy'];
            if ($value === null) {
                if ($issues !== [] && $repair) {
                    $updates = array_merge($updates, $this->emptyFieldUpdates($field));
                }

                continue;
            }

            $needsEncryption = $details['status'] === 'legacy_only'
                || $details['status'] === 'encrypted_only' && $issues !== []
                || $issues !== []
                || $retirePlaintext
                || ($rotateToCurrent && (
                    $details['envelope_version'] !== $crypto->currentEncryptionVersion()
                    || $details['encryption_version'] !== $crypto->currentEncryptionVersion()
                    || $details['bidx_version'] !== $crypto->currentBlindIndexVersion()
                ));
            $needsRetirement = $retirePlaintext && $details['legacy'] !== null;

            if (! $needsEncryption && ! $needsRetirement) {
                continue;
            }

            $encrypted = $needsEncryption
                ? $crypto->encrypt($value)
                : null;
            $blindIndex = $needsEncryption
                ? $crypto->blindIndex($field, $value)
                : null;

            if ($retirePlaintext) {
                if ($encrypted === null || $crypto->decrypt($encrypted, $field) !== $value || $blindIndex !== $crypto->blindIndex($field, $value)) {
                    throw new RuntimeException('PII parity verification failed before plaintext retirement.');
                }
            }

            $updates = array_merge($updates, [
                $field => $retirePlaintext ? null : $value,
                $field.'_enc' => $encrypted,
                $field.'_key_version' => $encrypted === null ? null : $crypto->currentEncryptionVersion(),
                $field.'_bidx' => $blindIndex,
                $field.'_bidx_version' => $blindIndex === null ? null : $crypto->currentBlindIndexVersion(),
                $field.'_migrated_at' => now()->toDateTimeString(),
            ]);
        }

        return $updates;
    }

    /**
     * @return array<string, null>
     */
    private function emptyFieldUpdates(string $field): array
    {
        return [
            $field => null,
            $field.'_enc' => null,
            $field.'_key_version' => null,
            $field.'_bidx' => null,
            $field.'_bidx_version' => null,
            $field.'_migrated_at' => null,
        ];
    }

    private function isTransientDatabaseException(Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? $exception->getCode());

        return in_array($sqlState, ['40001', '40P01', '55P03'], true)
            || in_array($driverCode, ['5', '6', '1205', '1213'], true);
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>  $inspection
     */
    private function addInspectionToReport(array &$report, array $inspection): void
    {
        foreach ($inspection['fields'] as $details) {
            $report['classifications'][$details['status']] = ($report['classifications'][$details['status']] ?? 0) + 1;

            foreach ($details['issues'] as $issue) {
                $report['issues'][$issue] = ($report['issues'][$issue] ?? 0) + 1;
            }

            foreach ([
                'encryption_versions' => $details['encryption_version'],
                'blind_index_versions' => $details['bidx_version'],
                'envelope_versions' => $details['envelope_version'],
            ] as $counter => $version) {
                if (is_string($version) && $version !== '') {
                    $report[$counter][$version] = ($report[$counter][$version] ?? 0) + 1;
                }
            }
        }
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     rollout_phase: string,
     *     resume_token: string,
     *     processed: int,
     *     updated: int,
     *     would_update: int,
     *     last_id: ?string,
     *     classifications: array<string, int>,
     *     issues: array<string, int>,
     *     encryption_versions: array<string, int>,
     *     blind_index_versions: array<string, int>,
     *     envelope_versions: array<string, int>
     * }
     */
    private function emptyReport(bool $dryRun, string $resumeToken, PiiCryptoService $crypto): array
    {
        return [
            'dry_run' => $dryRun,
            'rollout_phase' => $crypto->rolloutPhase(),
            'resume_token' => $resumeToken,
            'processed' => 0,
            'updated' => 0,
            'would_update' => 0,
            'last_id' => null,
            'classifications' => [],
            'issues' => [],
            'encryption_versions' => [],
            'blind_index_versions' => [],
            'envelope_versions' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeReport(array $report): void
    {
        $path = $this->option('report');
        if (! is_string($path) || $path === '') {
            return;
        }

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
