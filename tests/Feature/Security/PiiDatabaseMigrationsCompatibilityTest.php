<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PiiDatabaseMigrationsCompatibilityTest extends TestCase
{
    use DatabaseMigrations;

    public function test_database_migrations_can_setup_and_teardown_with_pii_metadata(): void
    {
        $this->assertTrue(Schema::hasTable('cooperative_members'));
        $this->assertTrue(Schema::hasColumn('cooperative_members', 'identity_number_key_version'));
        $this->assertTrue(Schema::hasColumn('cooperative_members', 'no_rekening_migrated_at'));
    }
}
