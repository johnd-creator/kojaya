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

    public function test_unknown_numeric_user_id_from_anonymous_context_records_no_fake_actor(): void
    {
        // A valid positive integer that resolves to no real account must not
        // fabricate an actor. audit_logs.user_id is a foreign key, so a
        // nonexistent identity is recorded with a null actor and no invented
        // roles or organization.
        app(AuditLogService::class)->logAuth('LOGIN', 999999999, AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertNull($audit->user_id, 'No fake actor must be fabricated for a user that does not exist.');
        $this->assertSame([], $audit->actor_roles, 'No roles must be invented.');
        $this->assertNull($audit->organization_id);
    }

    public function test_nonnumeric_string_identifier_from_anonymous_context_records_no_fake_actor_and_never_queries_db(): void
    {
        // A nonnumeric string must never be passed as a bigint primary key lookup.
        // PostgreSQL would throw "invalid input syntax for type bigint"; the
        // validation must short-circuit before any DB query and produce a null actor.
        app(AuditLogService::class)->logAuth('LOGIN', 'nonexistent-user-id', AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertNull($audit->user_id, 'No fake actor must be fabricated for a nonnumeric identifier.');
        $this->assertSame([], $audit->actor_roles);
        $this->assertNull($audit->organization_id);
    }

    public function test_empty_string_identifier_from_anonymous_context_records_no_fake_actor(): void
    {
        app(AuditLogService::class)->logAuth('LOGIN', '', AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertNull($audit->user_id, 'Empty string identifier must not produce a fake actor.');
        $this->assertSame([], $audit->actor_roles);
        $this->assertNull($audit->organization_id);
    }

    public function test_negative_integer_identifier_from_anonymous_context_records_no_fake_actor(): void
    {
        app(AuditLogService::class)->logAuth('LOGIN', -1, AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertNull($audit->user_id, 'Negative integer must not produce a fake actor.');
        $this->assertSame([], $audit->actor_roles);
        $this->assertNull($audit->organization_id);
    }

    public function test_negative_numeric_string_identifier_from_anonymous_context_records_no_fake_actor(): void
    {
        app(AuditLogService::class)->logAuth('LOGIN', '-1', AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertNull($audit->user_id, 'Negative numeric string must not produce a fake actor.');
        $this->assertSame([], $audit->actor_roles);
        $this->assertNull($audit->organization_id);
    }

    public function test_existing_numeric_string_id_builds_truthful_actor(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');

        app(AuditLogService::class)->logAuth('LOGIN', (string) $user->id, AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertSame((string) $user->id, (string) $audit->user_id, 'Numeric string ID must resolve to the real user.');
        $this->assertSame(['Anggota'], $audit->actor_roles);
        $this->assertSame((string) $organization->id, (string) $audit->organization_id);
    }

    public function test_correlation_id_is_preserved_for_all_identifier_types(): void
    {
        $correlationId = '11111111-2222-3333-4444-555555555555';
        $context = AuditContext::forSystem(correlationId: $correlationId);

        app(AuditLogService::class)->logAuth('LOGIN', 'nonexistent-user-id', $context);

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertSame($correlationId, $audit->correlation_id, 'Correlation ID must be preserved even when the actor resolves to null.');
    }

    public function test_invalid_identifier_with_existing_actor_does_not_create_subject(): void
    {
        $orgA = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $orgA->id]);
        $actor->assignRole('System Admin');

        // When an actor already exists and the affected user identifier is
        // nonnumeric, the DB lookup must be skipped; no subject is recorded.
        app(AuditLogService::class)->logAuth(
            'LOGIN',
            'nonexistent-user-id',
            AuditContext::forActor($actor),
        );

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertSame((string) $actor->id, (string) $audit->user_id, 'Actor must be preserved.');
        $this->assertSame(['System Admin'], $audit->actor_roles, 'Actor roles must belong to the actor.');
        $this->assertNull($audit->subject_id, 'No subject must be recorded for a nonnumeric identifier.');
        $this->assertNull($audit->subject_type);
    }
}
