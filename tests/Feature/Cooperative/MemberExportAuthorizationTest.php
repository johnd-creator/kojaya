<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberExportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_anggota_role_is_forbidden_from_exporting_members(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');

        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.members.export'))
            ->assertForbidden();
    }

    public function test_admin_without_export_permission_is_forbidden(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        // Kasir Koperasi has view_cooperative_member but NOT export_cooperative_member
        $user->assignRole('Kasir Koperasi');

        $this->actingAs($user)
            ->get(route('cooperative.members.export'))
            ->assertForbidden();
    }

    public function test_admin_koperasi_with_export_permission_can_export(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');

        $this->actingAs($admin)
            ->get(route('cooperative.members.export'))
            ->assertOk();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('cooperative.members.export'))
            ->assertRedirect('/login');
    }

    public function test_export_is_scoped_to_organization_for_non_global_admin(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        CooperativeMember::factory()->create([
            'organization_id' => $orgA->id,
            'no_anggota' => 'AAA-001',
            'member_no' => 'AAA-001',
            'name' => 'Member Org A',
        ]);

        CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'no_anggota' => 'BBB-002',
            'member_no' => 'BBB-002',
            'name' => 'Member Org B',
        ]);

        $scoped = new \App\Exports\AnggotaExport([], $orgA->id);
        $scopedNames = $scoped->query()->pluck('name')->all();

        $this->assertContains('Member Org A', $scopedNames);
        $this->assertNotContains('Member Org B', $scopedNames);
    }

    public function test_export_masks_pii_fields(): void
    {
        $organization = Organization::factory()->create();

        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'npwp' => '12.345.678.9-012.000',
            'no_rekening' => '1234567890',
        ]);

        $scoped = new \App\Exports\AnggotaExport([], $organization->id);
        $row = $scoped->query()->first();
        $mapped = $scoped->map($row);

        // NPWP at position 4, No Rekening at position 10
        $this->assertNotEquals('12.345.678.9-012.000', $mapped[4]);
        $this->assertNotEquals('1234567890', $mapped[10]);
        $this->assertStringEndsWith('7890', $mapped[10]);
    }

    private function roleUser(string $roleName, Organization $organization): User
    {
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($roleName);

        return $user;
    }
}
