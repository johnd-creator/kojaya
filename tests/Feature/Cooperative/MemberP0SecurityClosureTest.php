<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\MemberStatusTransitionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberP0SecurityClosureTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private const SHARED_INERTIA_KEYS = [
        'active_organization',
        'appearance',
        'auth',
        'csrf_token',
        'errors',
        'googleSsoEnabled',
        'name',
        'notifications',
        'sidebarOpen',
        'user_organizations',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_generic_web_update_rejects_pii_and_preserves_existing_pii(): void
    {
        [$admin, $member] = $this->memberAndUser();

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), $this->profilePayload($member) + [
                'identity_number' => '9999999999999999',
                'npwp' => '99.999.999.9-999.999',
                'no_rekening' => '9999999999',
                'address' => 'Alamat baru',
            ])
            ->assertSessionHasErrors(['identity_number', 'npwp', 'no_rekening', 'address']);

        $fresh = $member->fresh();
        $this->assertSame('1111111111111111', $fresh->identity_number);
        $this->assertSame('11.111.111.1-111.111', $fresh->npwp);
        $this->assertSame('1111111111', $fresh->no_rekening);
    }

    public function test_create_cannot_set_lifecycle_account_or_pii_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $linkedUser = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('cooperative.members.store'), $this->storePayload() + [
                'status' => CooperativeMember::VALIDATION_ACTIVE,
                'user_id' => $linkedUser->id,
                'member_no' => 'KOP-ATTACK',
                'identity_number' => '9999999999999999',
                'npwp' => '99.999.999.9-999.999',
            ])
            ->assertSessionHasErrors(['status', 'user_id', 'member_no', 'identity_number', 'npwp']);

        $this->assertDatabaseMissing('cooperative_members', ['email' => 'p0-create@test.local']);
    }

    public function test_null_organization_non_global_web_create_is_denied_without_creation(): void
    {
        $role = Role::firstOrCreate(['name' => 'Unscoped Member Creator']);
        $role->syncPermissions(['manage_cooperative_member']);
        $actor = User::factory()->create(['organization_id' => null]);
        $actor->assignRole($role);

        $this->actingAs($actor)
            ->post(route('cooperative.members.store'), $this->storePayload() + ['email' => 'unscoped-web@test.local'])
            ->assertForbidden();

        $this->assertDatabaseMissing('cooperative_members', ['email' => 'unscoped-web@test.local']);
    }

    public function test_null_organization_non_global_api_create_is_denied_without_creation(): void
    {
        $role = Role::firstOrCreate(['name' => 'Unscoped API Member Creator']);
        $role->syncPermissions(['manage_cooperative_member']);
        $actor = User::factory()->create(['organization_id' => null]);
        $actor->assignRole($role);
        Sanctum::actingAs($actor, ['cooperative.member.write']);

        $this->postJson('/api/v1/members', $this->storePayload() + ['email' => 'unscoped-api@test.local'])
            ->assertForbidden();

        $this->assertDatabaseMissing('cooperative_members', ['email' => 'unscoped-api@test.local']);
    }

    public function test_generic_member_create_and_edit_do_not_expose_user_lists(): void
    {
        [$admin, $member] = $this->memberAndUser();

        $this->actingAs($admin)
            ->get(route('cooperative.members.create'))
            ->assertInertia(fn ($page) => $page->missing('users'));

        $this->actingAs($admin)
            ->get(route('cooperative.members.edit', $member))
            ->assertInertia(fn ($page) => $page->missing('users'));
    }

    public function test_member_inertia_list_detail_and_edit_have_exact_top_level_contracts(): void
    {
        [$admin, $member] = $this->memberAndUser();

        $indexProps = $this->actingAs($admin)
            ->get(route('cooperative.members.index'))
            ->inertiaProps();
        $indexKeys = array_keys($indexProps);
        sort($indexKeys);
        $expectedIndexKeys = [...self::SHARED_INERTIA_KEYS, 'filters', 'members', 'options'];
        sort($expectedIndexKeys);
        $this->assertSame($expectedIndexKeys, $indexKeys);

        $detailProps = $this->actingAs($admin)
            ->get(route('cooperative.members.show', $member))
            ->inertiaProps();
        $detailKeys = array_keys($detailProps);
        sort($detailKeys);
        $expectedDetailKeys = [
            ...self::SHARED_INERTIA_KEYS,
            'member',
            'openingSavingBalance',
            'recentSavingsEntries',
            'savingsSummary',
        ];
        sort($expectedDetailKeys);
        $this->assertSame($expectedDetailKeys, $detailKeys);

        $editProps = $this->actingAs($admin)
            ->get(route('cooperative.members.edit', $member))
            ->inertiaProps();
        $editKeys = array_keys($editProps);
        sort($editKeys);
        $expectedEditKeys = [
            ...self::SHARED_INERTIA_KEYS,
            'employees',
            'member',
            'openingSavingBalance',
            'options',
        ];
        sort($expectedEditKeys);
        $this->assertSame($expectedEditKeys, $editKeys);
    }

    public function test_valid_create_starts_pending_without_implicit_account_link(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');

        $this->actingAs($admin)
            ->post(route('cooperative.members.store'), $this->storePayload())
            ->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()->where('email', 'p0-create@test.local')->firstOrFail();
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->validation_status);
        $this->assertNull($member->user_id);
    }

    public function test_dedicated_web_pii_update_requires_write_permission_and_preserves_scope(): void
    {
        [$admin, $member] = $this->memberAndUser('update_cooperative_member_pii');

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'npwp' => '22.222.222.2-222.222',
                'no_rekening' => '2222222222',
            ])
            ->assertRedirect();

        $this->assertSame('22.222.222.2-222.222', $member->fresh()->npwp);
        $this->assertSame('2222222222', $member->fresh()->no_rekening);

        $viewer = User::factory()->create(['organization_id' => $member->organization_id]);
        $viewer->assignRole('Kasir Koperasi');

        $this->actingAs($viewer)
            ->patch(route('cooperative.members.sensitive-data.update', $member), ['npwp' => '33.333.333.3-333.333'])
            ->assertForbidden();

        $otherOrganization = Organization::factory()->create();
        $crossOrgMember = CooperativeMember::factory()->active()->create(['organization_id' => $otherOrganization->id]);

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $crossOrgMember), ['npwp' => '44.444.444.4-444.444'])
            ->assertForbidden();
    }

    public function test_api_generic_update_rejects_lifecycle_account_and_pii_fields(): void
    {
        [$admin, $member] = $this->memberAndUser();
        Sanctum::actingAs($admin, ['cooperative.member.write']);

        $this->putJson('/api/v1/members/'.$member->id, $this->profilePayload($member) + [
            'status' => 'INACTIVE',
            'validation_status' => 'REJECTED',
            'user_id' => User::factory()->create()->id,
            'npwp' => '99.999.999.9-999.999',
        ])->assertUnprocessable();

        $this->assertSame('ACTIVE', $member->fresh()->status);
        $this->assertSame('1111111111111111', $member->fresh()->identity_number);
    }

    public function test_member_api_list_and_detail_use_exact_allowlisted_keys_and_pii_visibility(): void
    {
        $organization = Organization::factory()->create();
        $viewer = User::factory()->create(['organization_id' => $organization->id]);
        $viewer->givePermissionTo(['view_cooperative_member', 'manage_cooperative_member']);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'identity_number' => '3201234567890001',
            'npwp' => '123456789012000',
            'no_rekening' => '1234567890',
        ]);
        Sanctum::actingAs($viewer, ['cooperative.member.read']);

        $expectedKeys = [
            'address',
            'email',
            'identity_number',
            'id',
            'joined_at',
            'member_no',
            'name',
            'nama_bank',
            'nama_pemilik_rekening',
            'no_anggota',
            'no_rekening',
            'npwp',
            'organization',
            'organization_id',
            'phone',
            'status',
            'validation_status',
        ];
        sort($expectedKeys);

        $listResponse = $this->getJson('/api/v1/members')->assertOk();
        $listKeys = array_keys($listResponse->json('data.0'));
        sort($listKeys);
        $this->assertSame($expectedKeys, $listKeys);
        $maskedIdentity = $listResponse->json('data.0.identity_number');
        $this->assertNotSame('3201234567890001', $maskedIdentity);
        $this->assertStringEndsWith('0001', (string) $maskedIdentity);
        $listResponse->assertJsonMissingPath('data.0.encrypted_identity_number');
        $listResponse->assertJsonMissingPath('data.0.identity_number_bidx');

        $detailResponse = $this->getJson('/api/v1/members/'.$member->id)->assertOk();
        $maskedDetailIdentity = $detailResponse->json('data.identity_number');
        $this->assertNotSame('3201234567890001', $maskedDetailIdentity);
        $this->assertStringEndsWith('0001', (string) $maskedDetailIdentity);

        $viewer->givePermissionTo('view_cooperative_member_pii');
        $this->getJson('/api/v1/members/'.$member->id)
            ->assertOk()
            ->assertJsonPath('data.identity_number', '3201234567890001')
            ->assertJsonPath('data.npwp', '123456789012000')
            ->assertJsonPath('data.no_rekening', '1234567890');
    }

    public function test_api_create_starts_pending_and_rejects_lifecycle_and_account_fields(): void
    {
        [$admin] = $this->memberAndUser();
        $linkedUser = User::factory()->create();
        Sanctum::actingAs($admin, ['cooperative.member.write']);

        $this->postJson('/api/v1/members', [
            'tanggal_aktif' => '2026-01-01',
            'nama_anggota' => 'P0 API Create',
            'email' => 'p0-api-create@test.local',
            'phone' => '08123456789',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            'user_id' => $linkedUser->id,
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('cooperative_members', ['email' => 'p0-api-create@test.local']);
    }

    public function test_account_link_has_a_dedicated_reasoned_action(): void
    {
        [$admin, $member] = $this->memberAndUser();
        $member->forceFill(['user_id' => null])->save();
        $linkedUser = User::factory()->create(['organization_id' => $member->organization_id]);

        $this->actingAs($admin)
            ->patch(route('cooperative.members.account-link.update', $member), [
                'user_id' => $linkedUser->id,
                'reason' => 'business_verification',
            ])
            ->assertRedirect();

        $this->assertSame($linkedUser->id, $member->fresh()->user_id);
        $this->assertTrue($linkedUser->fresh()->hasRole('Anggota'));
    }

    public function test_export_fails_closed_for_non_global_user_without_organization(): void
    {
        $role = Role::firstOrCreate(['name' => 'Member Export Without Scope']);
        $role->syncPermissions([
            'view_cooperative_member',
            'export_cooperative_member',
        ]);
        $user = User::factory()->create(['organization_id' => null]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('cooperative.members.export'))
            ->assertForbidden();
    }

    public function test_status_audit_and_backfill_do_not_emit_pii(): void
    {
        $member = CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
            'identity_number' => '1234567890123456',
        ]);
        $repairable = CooperativeMember::factory()->create([
            'status' => CooperativeMember::VALIDATION_INACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $this->artisan('members:audit-status-consistency')
            ->expectsOutputToContain('ACTIVE/non-active-validation')
            ->doesntExpectOutputToContain('1234567890123456')
            ->assertSuccessful();

        $this->artisan('members:backfill-status-consistency', ['--apply' => true])
            ->assertFailed();

        $this->artisan('members:backfill-status-consistency', ['--apply' => true, '--acknowledge' => true])
            ->assertFailed();

        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $repairable->fresh()->validation_status);
    }

    public function test_domain_transition_service_rejects_arbitrary_source_state(): void
    {
        [$admin, $member] = $this->memberAndUser();

        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->requestRevision($member, $admin, 'Tidak boleh dari ACTIVE.');
    }

    public function test_member_number_update_keeps_no_anggota_and_member_no_in_sync(): void
    {
        [$admin, $member] = $this->memberAndUser();

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), array_merge($this->profilePayload($member), ['no_anggota' => 'KOP-777']))
            ->assertRedirect();

        $fresh = $member->fresh();
        $this->assertSame('KOP-777', $fresh->no_anggota);
        $this->assertSame('KOP-777', $fresh->member_no);
    }

    public function test_member_number_update_rejects_duplicate_numbers(): void
    {
        [$admin, $member] = $this->memberAndUser();
        $other = CooperativeMember::factory()->create(['no_anggota' => 'KOP-888', 'member_no' => 'KOP-888']);

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), array_merge(
                $this->profilePayload($member),
                ['no_anggota' => $other->no_anggota],
            ))
            ->assertSessionHasErrors('no_anggota');

        $this->assertSame('KOP-888', $other->fresh()->no_anggota);
        $this->assertNotSame('KOP-888', $member->fresh()->no_anggota);
    }

    /** @return array<string, mixed> */
    private function storePayload(): array
    {
        return [
            'tanggal_aktif' => '2026-01-01',
            'nama_anggota' => 'P0 Create Member',
            'name' => 'P0 Create Member',
            'email' => 'p0-create@test.local',
            'phone' => '08123456789',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ];
    }

    /** @return array{0: User, 1: CooperativeMember} */
    private function memberAndUser(?string $permission = null): array
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);

        if ($permission === null) {
            $admin->assignRole('System Admin');
        } else {
            $role = Role::firstOrCreate(['name' => 'Member P0 Test Role '.$permission]);
            $role->syncPermissions(['view_cooperative_member', 'manage_cooperative_member', $permission]);
            $admin->assignRole($role);
        }

        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            'identity_number' => '1111111111111111',
            'npwp' => '11.111.111.1-111.111',
            'no_rekening' => '1111111111',
        ]);

        return [$admin, $member];
    }

    /** @return array<string, string> */
    private function profilePayload(CooperativeMember $member): array
    {
        return [
            'employee_id' => $member->employee_id,
            'no_anggota' => $member->no_anggota,
            'nama_anggota' => $member->nama_anggota ?: $member->name,
            'name' => $member->name,
            'email' => $member->email,
            'no_telp' => $member->no_telp ?: $member->phone,
            'phone' => $member->phone,
            'jenis_anggota' => $member->jenis_anggota,
            'jenis_kelamin' => $member->jenis_kelamin,
            'kategori' => $member->kategori,
            'autodebet' => $member->autodebet,
        ];
    }
}
