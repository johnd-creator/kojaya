<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LegacyTokenClassificationCommandTest extends TestCase
{
    public function test_dry_run_classifies_without_mutating_legacy_tokens(): void
    {
        $user = User::factory()->create();
        $safe = $user->createToken('legacy-member', ['profile:read', 'member:read', 'member:write']);
        $unsafe = $user->createToken('legacy-unknown', ['*']);

        $this->artisan('tokens:classify-legacy', [
            '--dry-run' => true,
            '--batch' => 1,
        ])->assertSuccessful();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $safe->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $unsafe->accessToken->id]);
    }

    public function test_explicit_rotation_revokes_only_unsafe_tokens_after_grace_deadline(): void
    {
        $user = User::factory()->create();
        $safe = $user->createToken('legacy-member', ['profile:read', 'member:read', 'member:write']);
        $unsafe = $user->createToken('legacy-unknown', ['*']);

        $this->artisan('tokens:classify-legacy', [
            '--revoke-unsafe' => true,
            '--confirm' => true,
            '--grace-until' => Carbon::now()->subMinute()->toISOString(),
        ])->assertSuccessful();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $safe->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $unsafe->accessToken->id]);
    }
}
