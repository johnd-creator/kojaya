<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TokenMetadataMigrationTest extends TestCase
{
    public function test_metadata_migration_rolls_back_only_its_additive_columns_on_disposable_sqlite(): void
    {
        $migration = require base_path('database/migrations/2026_07_15_000001_add_application_metadata_to_personal_access_tokens.php');

        foreach (['token_app', 'token_version', 'device_id', 'issued_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('personal_access_tokens', $column));
        }

        $migration->down();

        foreach (['token_app', 'token_version', 'device_id', 'issued_at'] as $column) {
            $this->assertFalse(Schema::hasColumn('personal_access_tokens', $column));
        }

        foreach (['token', 'abilities', 'last_used_at', 'created_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('personal_access_tokens', $column));
        }

        $migration->up();

        foreach (['token_app', 'token_version', 'device_id', 'issued_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('personal_access_tokens', $column));
        }
    }
}
