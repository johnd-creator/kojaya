<?php

namespace App\Console\Commands;

use App\Services\Security\MemberSensitiveDataInspector;
use App\Services\Security\PiiCryptoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyMemberSensitiveData extends Command
{
    protected $signature = 'members:verify-sensitive-data
        {--chunk=250 : Number of members per batch}
        {--from-id= : Resume after this member ID}
        {--limit= : Maximum number of members to process}
        {--report= : Write a PII-free JSON report to this path}';

    protected $description = 'Verify member PII encryption, blind-index parity, key versions, and plaintext retirement';

    public function handle(MemberSensitiveDataInspector $inspector, PiiCryptoService $crypto): int
    {
        $chunk = max(1, min((int) $this->option('chunk'), 1000));
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $fromId = (string) ($this->option('from-id') ?: '');
        $report = [
            'rollout_phase' => $crypto->rolloutPhase(),
            'processed' => 0,
            'consistent_fields' => 0,
            'issues' => [],
            'field_counts' => [],
            'value_counts' => [],
            'encryption_versions' => [],
            'blind_index_versions' => [],
            'envelope_versions' => [],
            'last_id' => null,
        ];

        $query = DB::table('cooperative_members')->orderBy('id');
        if ($fromId !== '') {
            $query->where('id', '>', $fromId);
        }
        $query->where(function ($query): void {
            foreach (PiiCryptoService::FIELDS as $field) {
                $query->orWhereNotNull($field)
                    ->orWhereNotNull($field.'_enc')
                    ->orWhereNotNull($field.'_bidx');
            }
        });

        $query->chunkById($chunk, function ($rows) use ($inspector, $limit, &$report): bool {
            foreach ($rows as $row) {
                if ($limit !== null && $report['processed'] >= $limit) {
                    return false;
                }

                $record = (array) $row;
                $inspection = $inspector->inspect($record);
                $report['processed']++;
                $report['last_id'] = (string) ($record['id'] ?? '');

                foreach ($inspection['fields'] as $field => $details) {
                    $status = $details['status'];
                    $report['field_counts'][$field][$status] = ($report['field_counts'][$field][$status] ?? 0) + 1;

                    foreach ($details['issues'] as $issue) {
                        $report['issues'][$issue] = ($report['issues'][$issue] ?? 0) + 1;
                    }

                    if ($details['issues'] === []) {
                        $report['consistent_fields']++;
                    }

                    foreach ([
                        'encryption_versions' => $details['encryption_version'],
                        'blind_index_versions' => $details['bidx_version'],
                        'envelope_versions' => $details['envelope_version'],
                    ] as $counter => $version) {
                        if (is_string($version) && $version !== '') {
                            $report[$counter][$field][$version] = ($report[$counter][$field][$version] ?? 0) + 1;
                        }
                    }

                    if (filled($record[$field.'_enc'] ?? null)) {
                        $report['value_counts'][$field]['encrypted'] = ($report['value_counts'][$field]['encrypted'] ?? 0) + 1;
                    }

                    if (filled($record[$field.'_bidx'] ?? null)) {
                        $report['value_counts'][$field]['blind_index'] = ($report['value_counts'][$field]['blind_index'] ?? 0) + 1;
                    }
                }
            }

            return true;
        });

        foreach (PiiCryptoService::FIELDS as $field) {
            $encryptedCount = $report['value_counts'][$field]['encrypted'] ?? 0;
            $blindIndexCount = $report['value_counts'][$field]['blind_index'] ?? 0;

            if ($encryptedCount !== $blindIndexCount) {
                $report['issues']['count_parity_mismatch'] = ($report['issues']['count_parity_mismatch'] ?? 0) + 1;
            }
        }

        $this->writeReport($report);
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($report['issues'] !== []) {
            $this->error('Sensitive data verification found inconsistencies.');

            return self::FAILURE;
        }

        $this->info('Sensitive data verification passed.');

        return self::SUCCESS;
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
