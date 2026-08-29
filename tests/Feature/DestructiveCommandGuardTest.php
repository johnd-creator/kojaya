<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DestructiveCommandGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        // Always reset prohibition state to safe testing default
        DB::prohibitDestructiveCommands(false);
        $this->app['env'] = 'testing';
        parent::tearDown();
    }

    public function test_app_service_provider_prohibits_destructive_commands_in_production_staging_and_qa(): void
    {
        $destructiveCommands = [
            'migrate:fresh',
            'migrate:refresh',
            'migrate:reset',
            'db:wipe',
        ];

        foreach (['production', 'staging', 'qa'] as $env) {
            // Explicitly reset prohibition state before testing each environment to prove independent wiring
            DB::prohibitDestructiveCommands(false);

            $this->app['env'] = $env;
            config(['app.env' => $env]);

            // Re-boot AppServiceProvider to test exact provider wiring under this environment
            (new AppServiceProvider($this->app))->boot();

            foreach ($destructiveCommands as $command) {
                Artisan::call($command, ['--force' => true]);
                $output = Artisan::output();

                $this->assertStringContainsString(
                    'prohibited',
                    strtolower($output),
                    "Command '{$command}' must be prohibited from running when booted in environment '{$env}'.",
                );
            }
        }
    }

    public function test_app_service_provider_allows_safe_environments(): void
    {
        foreach (['local', 'testing', 'playwright'] as $env) {
            DB::prohibitDestructiveCommands(false);

            $this->app['env'] = $env;
            config(['app.env' => $env]);

            // Re-boot AppServiceProvider under safe environments
            (new AppServiceProvider($this->app))->boot();

            $exitCode = Artisan::call('migrate:status');
            $output = Artisan::output();

            $this->assertStringNotContainsString(
                'prohibited',
                strtolower($output),
                "Safe environment '{$env}' must not prohibit normal migration operations.",
            );
            $this->assertSame(0, $exitCode);
        }
    }

    public function test_forward_migrations_are_never_prohibited(): void
    {
        foreach (['production', 'staging', 'qa'] as $env) {
            DB::prohibitDestructiveCommands(false);

            $this->app['env'] = $env;
            config(['app.env' => $env]);

            (new AppServiceProvider($this->app))->boot();

            $exitCode = Artisan::call('migrate:status');
            $output = Artisan::output();

            $this->assertStringNotContainsString('prohibited', strtolower($output));
            $this->assertSame(0, $exitCode);
        }
    }
}
