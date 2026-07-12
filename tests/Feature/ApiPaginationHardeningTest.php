<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\NotificationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPaginationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_api_clamps_page_size_to_centralized_bounds(): void
    {
        $user = User::factory()->create();
        NotificationFactory::new()->forUser($user)->count(2)->create();

        $this->actingAs($user)
            ->getJson('/api/notifications?per_page=999999')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);

        $this->actingAs($user)
            ->getJson('/api/notifications?per_page=-1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);
    }
}
