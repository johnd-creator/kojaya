<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DestructiveCommandGuardTest extends TestCase
{
    public function test_destructive_commands_are_prohibited_when_configured(): void
    {
        DB::prohibitDestructiveCommands(true);

        $commands = [
            'migrate:fresh',
            'migrate:refresh',
            'migrate:reset',
            'db:wipe',
        ];

        foreach ($commands as $command) {
            Artisan::call($command, ['--force' => true]);
            $output = Artisan::output();

            $this->assertStringContainsString(
                'prohibited',
                strtolower($output),
                "Command '{$command}' must be prohibited from running.",
            );
        }

        // Reset prohibition for other tests
        DB::prohibitDestructiveCommands(false);
    }

    public function test_forward_migration_is_not_prohibited(): void
    {
        DB::prohibitDestructiveCommands(true);

        $exitCode = Artisan::call('migrate:status');
        $output = Artisan::output();

        $this->assertStringNotContainsString('prohibited', strtolower($output));
        $this->assertSame(0, $exitCode);

        DB::prohibitDestructiveCommands(false);
    }
}
