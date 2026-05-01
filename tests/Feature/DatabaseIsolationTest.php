<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    public function test_database_is_sqlite(): void
    {
        $this->assertEquals('sqlite', config('database.default'), 'DB should be sqlite');
        $this->assertEquals(':memory:', config('database.connections.sqlite.database'), 'Should use in-memory database');
    }

    public function test_production_database_not_affected(): void
    {
        // Check if we're accidentally using PostgreSQL
        $this->assertNotEquals('pgsql', config('database.default'), 'Should NOT be using pgsql in tests');

        // Verify SQLite is in memory
        $sqliteFile = config('database.connections.sqlite.database');
        $this->assertEquals(':memory:', $sqliteFile, 'Should be in-memory, not file');
    }
}
