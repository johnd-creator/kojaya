<?php

namespace App\Console\Commands;

use App\Services\AuditLogService;
use App\Services\Auth\LegacyTokenClassifier;
use App\Support\AuditContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class ClassifyLegacyTokensCommand extends Command
{
    protected $signature = 'tokens:classify-legacy
        {--dry-run : Inspect only; this is the default}
        {--report= : Write the aggregate report to a controlled JSON path}
        {--batch=100 : Maximum records read per batch}
        {--revoke-unsafe : Revoke unsafe legacy tokens}
        {--confirm : Confirm the explicit revocation operation}
        {--grace-until= : Do not revoke before this UTC deadline}';

    protected $description = 'Classify legacy Sanctum tokens without exposing token material.';

    public function handle(LegacyTokenClassifier $classifier, AuditLogService $audit): int
    {
        $batch = max(1, min((int) $this->option('batch'), 1000));
        $mutating = (bool) $this->option('revoke-unsafe') && ! $this->option('dry-run');
        $confirm = (bool) $this->option('confirm');

        if ($mutating && (! $confirm || $this->option('grace-until') === null)) {
            $this->error('Revocation requires --confirm and --grace-until.');

            return self::FAILURE;
        }

        try {
            $graceUntil = $this->option('grace-until') !== null
                ? Carbon::parse((string) $this->option('grace-until'))
                : null;
        } catch (\Throwable) {
            $this->error('The grace deadline must be a valid date.');

            return self::FAILURE;
        }

        if ($mutating && $graceUntil?->isFuture()) {
            $this->warn('Grace deadline has not elapsed; no tokens were revoked.');
            $mutating = false;
        }

        $counts = [
            'member' => 0,
            'ess' => 0,
            'technician' => 0,
            'admin' => 0,
            'unsafe' => 0,
        ];
        $revoked = 0;

        PersonalAccessToken::query()
            ->whereNull('token_app')
            ->orderBy('id')
            ->chunkById($batch, function ($tokens) use (&$counts, &$revoked, $classifier, $mutating): void {
                foreach ($tokens as $token) {
                    $classification = $classifier->classify($token->abilities);
                    $counts[$classification]++;

                    if ($mutating && $classification === 'unsafe') {
                        $revoked += PersonalAccessToken::query()->whereKey($token->id)->delete();
                    }
                }
            });

        $report = [
            'dry_run' => ! $mutating,
            'batch' => $batch,
            'counts' => $counts,
            'revoked' => $revoked,
        ];

        if ($path = $this->option('report')) {
            file_put_contents((string) $path, json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        }

        if ($mutating && $revoked > 0) {
            $audit->log('auth.legacy_tokens.revoked', 'auth.token', null, [
                'new' => [
                    'unsafe_tokens_revoked' => $revoked,
                    'classification_counts' => $counts,
                ],
                'reason' => 'Explicit legacy token rotation after the configured grace deadline.',
            ], AuditContext::forCli());
        }

        $this->line(json_encode($report, JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
