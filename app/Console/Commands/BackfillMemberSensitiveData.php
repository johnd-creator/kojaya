<?php

namespace App\Console\Commands;

use App\Services\Security\MemberSensitiveDataInspector;
use App\Services\Security\PiiCryptoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        $dryRun = (bool) $this->option('dry-run')
            || (app()->environment('production') && ! $this->option('confirm-production'));
        $repair = (bool) $this->option('repair-missing-index');
        $report = $this->emptyReport($dryRun, $resumeToken);
        $failed = false;

        $query = DB::table('cooperative_members')->orderBy('id');
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
            &$report,
            &$failed,
        ): bool {
            foreach ($rows as $row) {
                if ($limit !== null && $report['processed'] >= $limit) {
                    return false;
                }

                $record = (array) $row;
                $inspection = $inspector->inspect($record);
                $report['processed']++;
                $report['last_id'] = (string) ($record['id'] ?? '');

                foreach ($inspection['fields'] as $field => $details) {
                    $report['classifications'][$details['status']] = ($report['classifications'][$details['status']] ?? 0) + 1;

                    foreach ($details['issues'] as $issue) {
                        $report['issues'][$issue] = ($report['issues'][$issue] ?? 0) + 1;
                    }

                    if (in_array('decrypt_failure', $details['issues'], true) || $details['status'] === 'dual_mismatch') {
                        $this->error("PII consistency check failed for field [{$field}] on member [{$report['last_id']}].");
                        $failed = true;

                        return false;
                    }
                }

                try {
                    $updates = $this->updatesFor($inspection['fields'], $crypto, $repair);
                } catch (Throwable) {
                    $this->error("PII repair is required for member [{$report['last_id']}].");
                    $report['issues']['repair_required'] = ($report['issues']['repair_required'] ?? 0) + 1;
                    $failed = true;

                    return false;
                }
                if ($updates === []) {
                    continue;
                }

                if ($dryRun) {
                    $report['would_update']++;

                    continue;
                }

                try {
                    retry(3, function () use ($record, $updates): void {
                        DB::transaction(function () use ($record, $updates): void {
                            DB::table('cooperative_members')
                                ->where('id', $record['id'])
                                ->lockForUpdate()
                                ->first();
                            DB::table('cooperative_members')
                                ->where('id', $record['id'])
                                ->update($updates);
                        });
                    }, 100);
                    $report['updated']++;
                } catch (Throwable $exception) {
                    $this->error("PII backfill write failed for member [{$report['last_id']}].");
                    $report['issues']['write_failure'] = ($report['issues']['write_failure'] ?? 0) + 1;
                    $failed = true;

                    return false;
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
     * @param  array<string, array{status: string, legacy: ?string, decrypted: ?string, issues: list<string>}>  $fields
     * @return array<string, mixed>
     */
    private function updatesFor(array $fields, PiiCryptoService $crypto, bool $repair): array
    {
        $updates = [];

        foreach ($fields as $field => $details) {
            if (in_array('decrypt_failure', $details['issues'], true) || $details['status'] === 'dual_mismatch') {
                return [];
            }

            if ($details['issues'] !== [] && ! $repair) {
                throw new \RuntimeException('PII repair is required for inconsistent blind-index metadata.');
            }

            $value = $details['decrypted'] ?? $details['legacy'];
            if ($value === null) {
                if ($details['issues'] !== [] && $repair) {
                    $updates = array_merge($updates, [
                        $field => null,
                        $field.'_enc' => null,
                        $field.'_key_version' => null,
                        $field.'_bidx' => null,
                        $field.'_bidx_version' => null,
                        $field.'_migrated_at' => null,
                    ]);
                }

                continue;
            }

            if ($details['legacy'] !== null || $details['issues'] !== []) {
                $updates = array_merge($updates, [
                    $field => null,
                    $field.'_enc' => $crypto->encrypt($value),
                    $field.'_key_version' => $crypto->currentEncryptionVersion(),
                    $field.'_bidx' => $crypto->blindIndex($field, $value),
                    $field.'_bidx_version' => $crypto->currentBlindIndexVersion(),
                    $field.'_migrated_at' => now()->toDateTimeString(),
                ]);
            }
        }

        return $updates;
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     resume_token: string,
     *     processed: int,
     *     updated: int,
     *     would_update: int,
     *     last_id: ?string,
     *     classifications: array<string, int>,
     *     issues: array<string, int>
     * }
     */
    private function emptyReport(bool $dryRun, string $resumeToken): array
    {
        return [
            'dry_run' => $dryRun,
            'resume_token' => $resumeToken,
            'processed' => 0,
            'updated' => 0,
            'would_update' => 0,
            'last_id' => null,
            'classifications' => [],
            'issues' => [],
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
