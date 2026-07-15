<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Auth\TokenAbilityResolver;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class GranularAbilityCutoverTest extends TestCase
{
    public function test_remove_phase_stops_issuing_legacy_cooperative_abilities(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('security.ability_cutover_phase', 'remove');
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');

        $abilities = app(TokenAbilityResolver::class)->for($user, 'admin');

        $this->assertNotContains('cooperative:read', $abilities);
        $this->assertNotContains('cooperative:write', $abilities);
        $this->assertContains('cooperative.member.read', $abilities);
    }

    public function test_instrument_phase_retains_legacy_compatibility_for_inventory(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('security.ability_cutover_phase', 'instrument');
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');

        $abilities = app(TokenAbilityResolver::class)->for($user, 'admin');

        $this->assertContains('cooperative:read', $abilities);
        $this->assertContains('cooperative:write', $abilities);
        $this->assertContains('cooperative.member.read', $abilities);
    }
}
