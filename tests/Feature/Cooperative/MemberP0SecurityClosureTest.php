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

    public function test_account_link_has_a_dedicated_reasoned_action(): void
    {
        [$admin, $member] = $this->memberAndUser();
        $linkedUser = User::factory()->create(['organization_id' => $member->organization_id]);

        $this->actingAs($admin)
            ->patch(route('cooperative.members.account-link.update', $member), [
                'user_id' => $linkedUser->id,
                'reason' => 'Akun anggota diverifikasi oleh operator.',
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
            'validation_status' => null,
            'identity_number' => '1234567890123456',
        ]);

        $this->artisan('members:audit-status-consistency')
            ->expectsOutputToContain('ACTIVE/null')
            ->doesntExpectOutputToContain('1234567890123456')
            ->assertSuccessful();

        $this->artisan('members:backfill-status-consistency', ['--apply' => true])
            ->assertSuccessful();

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->validation_status);
    }

    public function test_domain_transition_service_rejects_arbitrary_source_state(): void
    {
        [$admin, $member] = $this->memberAndUser();

        $this->expectException(ValidationException::class);
        app(MemberStatusTransitionService::class)->requestRevision($member, $admin, 'Tidak boleh dari ACTIVE.');
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
