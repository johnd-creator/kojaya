<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Auth\TokenAbilityResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Carbon;
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

    public function test_invalid_phase_fails_closed_during_issuance(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('security.ability_cutover_phase', 'typo');
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        app(TokenAbilityResolver::class)->for($user, 'admin');
    }

    public function test_rotate_requires_a_valid_future_grace_deadline(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        config()->set('security.ability_cutover_phase', 'rotate');

        $this->assertNotContains('cooperative:read', app(TokenAbilityResolver::class)->for($user, 'admin'));

        config()->set('security.legacy_token_grace_until', Carbon::now()->addDay()->toISOString());

        $this->assertNotContains('cooperative:write', app(TokenAbilityResolver::class)->for($user, 'admin'));
    }

    public function test_rotate_rejects_missing_invalid_and_expired_legacy_deadlines(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = \App\Models\Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        config()->set('security.ability_cutover_phase', 'rotate');
        \Laravel\Sanctum\Sanctum::actingAs($user, ['cooperative:read']);

        $this->getJson('/api/v1/members')->assertForbidden();

        config()->set('security.legacy_token_grace_until', 'not-a-date');
        $this->getJson('/api/v1/members')->assertForbidden();

        config()->set('security.legacy_token_grace_until', Carbon::now()->subDay()->toISOString());
        $this->getJson('/api/v1/members')->assertForbidden();
    }

    public function test_deprecate_adds_headers_only_for_legacy_authorization(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = \App\Models\Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        config()->set('security.ability_cutover_phase', 'deprecate');
        config()->set('security.legacy_token_grace_until', Carbon::now()->addDay()->toISOString());

        \Laravel\Sanctum\Sanctum::actingAs($user, ['cooperative:read']);
        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertHeader('Deprecation', 'true')
            ->assertHeader('Sunset');

        \Laravel\Sanctum\Sanctum::actingAs($user, ['cooperative.member.read']);
        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertHeaderMissing('Deprecation');
    }

    public function test_remove_requires_explicit_non_expired_emergency_fallback_and_rejects_wildcard(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = \App\Models\Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        config()->set('security.ability_cutover_phase', 'remove');

        \Laravel\Sanctum\Sanctum::actingAs($user, ['cooperative:read']);
        $this->getJson('/api/v1/members')->assertForbidden();

        config()->set('security.legacy_ability_fallback_enabled', true);
        config()->set('security.legacy_ability_fallback_expires_at', Carbon::now()->addDay()->toISOString());
        $this->getJson('/api/v1/members')->assertOk();

        $wildcard = $user->createToken('wildcard', ['*']);
        $this->app['auth']->forgetGuards();
        $this->withToken($wildcard->plainTextToken);
        $this->getJson('/api/v1/members')->assertForbidden();
    }
}
