<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_valid_integer_id_that_is_not_found_produces_null_actor_without_exception(): void
    {
        app(AuditLogService::class)->logAuth('LOGIN', 999999999, AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertNull($audit->user_id, 'No fake actor must be fabricated for a user that does not exist.');
        $this->assertSame([], $audit->actor_roles, 'No roles must be invented.');
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

    public function test_leading_zero_numeric_string_id_resolves_to_real_user(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');

        $zeroPadded = '0'.(string) $user->id;

        app(AuditLogService::class)->logAuth('LOGIN', $zeroPadded, AuditContext::forSystem());

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertSame((string) $user->id, (string) $audit->user_id, 'Numeric string with leading zero must resolve to the real user.');
        $this->assertSame(['Anggota'], $audit->actor_roles);
    }

    #[DataProvider('invalidIdentifierProvider')]
    public function test_invalid_identifier_produces_null_actor_and_preserves_context_without_user_lookup(int|string $identifier): void
    {
        $correlationId = '11111111-2222-3333-4444-555555555555';
        $context = new AuditContext(
            actorId: null,
            actorRoles: [],
            organizationId: null,
            correlationId: $correlationId,
            ip: '192.0.2.10',
            userAgent: 'AuthAuditActorIdentityTest/1.0',
            source: AuditContext::SOURCE_HTTP,
        );
        $userSelects = [];

        DB::listen(function (QueryExecuted $query) use (&$userSelects): void {
            if ($this->isUsersTableSelect($query->sql)) {
                $userSelects[] = $query->sql;
            }
        });

        app(AuditLogService::class)->logAuth('LOGIN', $identifier, $context);

        $audit = AuditLog::query()->where('action', 'LOGIN')->sole();

        $this->assertNull($audit->user_id, "Identifier [{$identifier}] must not fabricate an actor.");
        $this->assertSame([], $audit->actor_roles);
        $this->assertNull($audit->organization_id);
        $this->assertNull($audit->subject_id);
        $this->assertNull($audit->subject_type);
        $this->assertSame($correlationId, $audit->correlation_id, 'Correlation ID must be preserved for any invalid identifier.');
        $this->assertSame($context->source, $audit->source);
        $this->assertSame($context->ip, $audit->ip_address);
        $this->assertSame($context->userAgent, $audit->user_agent);
        $this->assertSame([], $userSelects, 'No SELECT against the users table is permitted for an invalid identifier.');
    }

    /** @return array<string, array{0: int|string}> */
    public static function invalidIdentifierProvider(): array
    {
        return [
            'zero int' => [0],
            'zero string' => ['0'],
            'all zero string' => ['00'],
            'leading zero zero string' => ['0000'],
            'negative int' => [-1],
            'negative string' => ['-1'],
            'decimal string' => ['1.0'],
            'scientific notation' => ['1e3'],
            'plus sign' => ['+1'],
            'whitespace' => [' 1 '],
            'empty string' => [''],
            'nonnumeric string' => ['nonexistent-user-id'],
            'over bigint max' => ['9223372036854775808'],
            'far overlong digit string' => ['999999999999999999999999999999'],
        ];
    }

    #[DataProvider('validIdentifierProvider')]
    public function test_valid_identifier_executes_select_against_users_table(int|string $identifier): void
    {
        $userSelects = [];

        DB::listen(function (QueryExecuted $query) use (&$userSelects): void {
            if ($this->isUsersTableSelect($query->sql)) {
                $userSelects[] = $query->sql;
            }
        });

        app(AuditLogService::class)->logAuth('LOGIN', $identifier, AuditContext::forSystem());

        $this->assertNotEmpty($userSelects, "Valid identifier [{$identifier}] must execute a SELECT against the users table.");
    }

    /** @return array<string, array{0: int|string}> */
    public static function validIdentifierProvider(): array
    {
        return [
            'positive integer' => [1],
            'positive numeric string' => ['1'],
            'leading zero numeric string' => ['0001'],
            'maximum PHP integer' => [PHP_INT_MAX],
            'maximum PostgreSQL bigint string' => ['9223372036854775807'],
        ];
    }

    public function test_invalid_identifier_with_existing_actor_preserves_actor_and_creates_no_subject(): void
    {
        $orgA = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $orgA->id]);
        $actor->assignRole('System Admin');
        $userSelects = [];

        DB::listen(function (QueryExecuted $query) use (&$userSelects): void {
            if ($this->isUsersTableSelect($query->sql)) {
                $userSelects[] = $query->sql;
            }
        });

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
        $this->assertSame([], $userSelects, 'An invalid identifier must not trigger a users lookup even when an actor already exists.');
    }

    /**
     * Detect a SELECT against the users table, tolerating different quote styles
     * and column lists across database drivers.
     */
    private function isUsersTableSelect(string $sql): bool
    {
        $normalized = strtolower($sql);

        return preg_match('/^\s*select\b.*\bfrom\s+(?:"users"|`users`|users)(?:\s|$)/is', $normalized) === 1;
    }
}
