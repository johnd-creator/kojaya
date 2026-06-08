<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AppearancePageTest extends TestCase
{
    use DatabaseMigrations;

    public function test_appearance_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('appearance.edit'));

        $response->assertOk();
    }
}
