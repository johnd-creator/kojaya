<?php

namespace App\Console\Commands;

use App\Models\CooperativeMember;
use Illuminate\Console\Command;

class BackfillMemberSensitiveData extends Command
{
    protected $signature = 'members:backfill-sensitive-data {--chunk=250 : Number of members per batch}';

    protected $description = 'Encrypt legacy member identity and bank fields and populate blind indexes';

    public function handle(): int
    {
        $chunk = max(1, min((int) $this->option('chunk'), 1000));
        $updated = 0;

        CooperativeMember::query()
            ->where(function ($query): void {
                $query->whereNotNull('identity_number')
                    ->orWhereNotNull('npwp')
                    ->orWhereNotNull('no_rekening');
            })
            ->chunkById($chunk, function ($members) use (&$updated): void {
                foreach ($members as $member) {
                    $member->setAttribute('identity_number', $member->identity_number);
                    $member->setAttribute('npwp', $member->npwp);
                    $member->setAttribute('no_rekening', $member->no_rekening);
                    $member->saveQuietly();
                    $updated++;
                }

                $this->output->write('.', false);
            });

        $this->newLine();
        $this->info("Backfilled {$updated} member record(s).");

        return self::SUCCESS;
    }
}
