<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ComposerSetupSafetyTest extends TestCase
{
    public function test_setup_and_project_creation_do_not_run_database_migrations(): void
    {
        $composer = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

        foreach (['setup', 'post-create-project-cmd'] as $scriptName) {
            $script = implode(' ', $composer['scripts'][$scriptName]);
            $this->assertDoesNotMatchRegularExpression('/artisan\s+migrate(?:[:\s]|$)/', $script, $scriptName);
        }
    }
}
