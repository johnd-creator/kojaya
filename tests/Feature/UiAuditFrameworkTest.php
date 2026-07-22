<?php

namespace Tests\Feature;

use Tests\TestCase;

class UiAuditFrameworkTest extends TestCase
{
    public function test_visual_registry_has_unique_screen_ids(): void
    {
        $contents = file_get_contents(base_path('tests/visual/helpers/screen-registry.ts'));

        preg_match_all('/id: "([^"]+)"/', (string) $contents, $matches);

        $ids = $matches[1];

        $this->assertNotEmpty($ids);
        $this->assertCount(count($ids), array_unique($ids));
    }

    public function test_manifest_and_runtime_outputs_are_generated_by_the_harness(): void
    {
        $teardown = file_get_contents(base_path('tests/visual/global-teardown.ts'));
        $manifestHelper = file_get_contents(base_path('tests/visual/helpers/audit-manifest.ts'));

        $this->assertStringContainsString('manifest.json', (string) $teardown);
        $this->assertStringContainsString('JSON.stringify(', (string) $teardown);
        $this->assertStringContainsString('ui-audit-output/runtime', (string) file_get_contents(base_path('tests/visual/helpers/runtime-health.ts')));
        $this->assertStringContainsString('screenshot:', (string) $manifestHelper);
    }

    public function test_playwright_auth_state_and_sensitive_outputs_are_ignored(): void
    {
        $gitignore = file_get_contents(base_path('.gitignore'));

        $this->assertStringContainsString('/tests/visual/.auth/', (string) $gitignore);
        $this->assertStringContainsString('/ui-audit-output/', (string) $gitignore);
        $this->assertStringContainsString('/.env.playwright', (string) $gitignore);
    }
}
