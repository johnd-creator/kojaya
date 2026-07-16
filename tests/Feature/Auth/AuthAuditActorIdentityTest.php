<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class AuthAuditActorIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_actor_and_user_id_match_keeps_context_unchanged(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Anggota');

        app(AuditLogService::class)->logAuth(
            'LOGIN',
            $actor->id,
            AuditContext::forActor($actor),
        );

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertSame((string) $actor->id, (string) $audit->user_id);
        $this->assertSame(['Anggota'], $audit->actor_roles);
        $this->assertSame((string) $organization->id, (string) $audit->organization_id);
        $this->assertNull($audit->subject_id);
    }

    public function test_successful_authentication_from_anonymous_context_builds_truthful_actor_from_the_user(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');

        // No authenticated actor (e.g. a queued listener or pre-auth request).
        $anonymous = AuditContext::forSystem();

        app(AuditLogService::class)->logAuth('LOGIN', $user->id, $anonymous);

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertSame((string) $user->id, (string) $audit->user_id, 'Actor must be the authenticating user.');
        $this->assertSame(['Anggota'], $audit->actor_roles, 'Actor roles must come from the authenticating user.');
        $this->assertSame((string) $organization->id, (string) $audit->organization_id, 'Organization must come from the authenticating user.');
        $this->assertNull($audit->subject_id, 'Self-authentication records no separate subject.');
    }

    public function test_differing_actor_and_subject_never_mix_roles_or_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $actor = User::factory()->create(['organization_id' => $orgA->id]);
        $actor->assignRole('Admin Koperasi');

        $subject = User::factory()->create(['organization_id' => $orgB->id]);
        $subject->assignRole('Anggota');

        app(AuditLogService::class)->logAuth(
            'IMPERSONATION_ATTEMPT',
            $subject->id,
            AuditContext::forActor($actor),
        );

        $audit = AuditLog::query()->where('action', 'IMPERSONATION_ATTEMPT')->sole();

        $this->assertSame((string) $actor->id, (string) $audit->user_id, 'Actor identity must be preserved.');
        $this->assertSame(['Admin Koperasi'], $audit->actor_roles, 'Actor roles must belong to the actor, never the affected user.');
        $this->assertSame((string) $orgA->id, (string) $audit->organization_id, 'Organization must follow the actor, never the affected user.');
        $this->assertSame(User::class, $audit->subject_type, 'Affected user must be recorded as the subject.');
        $this->assertSame((string) $subject->id, (string) $audit->subject_id);
    }

    public function test_failed_authentication_with_unknown_user_creates_no_fake_actor(): void
    {
        // No userId supplied (unknown subject) and no authenticated actor.
        app(AuditLogService::class)->logAuth('FAILED_LOGIN', null, AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'FAILED_LOGIN')->sole();

        $this->assertNull($audit->user_id, 'No fake actor must be fabricated for an unknown user.');
        $this->assertSame([], $audit->actor_roles);
        $this->assertNull($audit->organization_id);
        $this->assertNull($audit->subject_id);
    }

    public function test_unknown_user_id_from_anonymous_context_records_no_fake_actor(): void
    {
        // A userId that resolves to no real account must not fabricate an actor.
        // audit_logs.user_id is a foreign key, so a nonexistent identity is recorded
        // with a null actor and no invented roles or organization.
        app(AuditLogService::class)->logAuth('LOGIN', 'nonexistent-user-id', AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertNull($audit->user_id, 'No fake actor must be fabricated for a user that does not exist.');
        $this->assertSame([], $audit->actor_roles, 'No roles must be invented.');
        $this->assertNull($audit->organization_id);
    }
}
