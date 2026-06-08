<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ComponentsPageTest extends TestCase
{
    use DatabaseMigrations;

    public function test_components_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('settings.components'));

        $response->assertOk();
    }
}
