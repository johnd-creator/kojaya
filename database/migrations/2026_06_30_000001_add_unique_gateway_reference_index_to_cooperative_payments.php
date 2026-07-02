<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE UNIQUE INDEX cooperative_payments_gateway_provider_reference_unique
    ON cooperative_payments (gateway_provider, gateway_reference)
    WHERE gateway_reference IS NOT NULL
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS cooperative_payments_gateway_provider_reference_unique');
    }
};
