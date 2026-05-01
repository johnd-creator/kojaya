<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // CRITICAL: Force SQLite for ALL tests to prevent production database wipe
        // This MUST be set before any database operations
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        parent::setUp();

        // Update application config to use SQLite
        app()->config->set('database.default', 'sqlite');
        app()->config->set('database.connections.sqlite.database', ':memory:');
        $this->artisan('migrate', ['--force' => true]);
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
