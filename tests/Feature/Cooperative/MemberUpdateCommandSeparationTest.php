<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberUpdateCommandSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // 1. Admin without PII-write changes name; all PII stays intact.

    public function test_admin_without_pii_write_can_update_name_and_pii_preserved(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'name' => 'Original Name',
            'identity_number' => '1234567890123456',
            'npwp' => '12.345.678.9-012.000',
            'no_rekening' => '9876543210',
        ]);

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Updated Name',
                'name' => 'Updated Name',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
            ])
            ->assertRedirect();

        $member->refresh();
        $this->assertSame('Updated Name', $member->name);
        $this->assertSame('1234567890123456', $member->identity_number);
        $this->assertSame('12.345.678.9-012.000', $member->npwp);
        $this->assertSame('9876543210', $member->no_rekening);
    }

    // 2. Unauthorized NPWP write is rejected.

    public function test_unauthorized_npwp_write_through_generic_update_is_rejected(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Name',
                'name' => 'Name',
                'npwp' => '99.999.999.9-999.999',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
            ])
            ->assertSessionHasErrors('npwp');
    }

    // 3. PII viewer without write cannot update.

    public function test_pii_viewer_without_write_cannot_update_sensitive_data(): void
    {
        $org = Organization::factory()->create();
        $viewer = User::factory()->create(['organization_id' => $org->id]);
        $viewer->givePermissionTo(['view_cooperative_member_pii']);

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        $this->actingAs($viewer)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'npwp' => '11.222.333.4-555.678',
            ])
            ->assertForbidden();
    }

    // 4. Authorized same-org PII update succeeds.

    public function test_authorized_same_org_pii_update_succeeds(): void
    {
        $org = Organization::factory()->create();
        $pengurus = User::factory()->create(['organization_id' => $org->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        $this->actingAs($pengurus)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'npwp' => '11.222.333.4-555.678',
            ])
            ->assertSessionHas('success');

        $this->assertSame('11.222.333.4-555.678', $member->refresh()->npwp);
    }

    // 5. Cross-org PII update is denied.

    public function test_cross_org_pii_update_is_denied(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        // User with PII write in Org A but NOT view_cooperative_all
        $adminA = User::factory()->create(['organization_id' => $orgA->id]);
        $adminA->givePermissionTo(['update_cooperative_member_pii']);

        $memberB = CooperativeMember::factory()->active()->create([
            'organization_id' => $orgB->id,
        ]);

        $this->actingAs($adminA)
            ->patch(route('cooperative.members.sensitive-data.update', $memberB), [
                'npwp' => '11.222.333.4-555.678',
            ])
            ->assertForbidden();
    }

    // 6. Generic update with status=INACTIVE is rejected.

    public function test_generic_update_with_status_field_is_rejected(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Name',
                'name' => 'Name',
                'status' => 'INACTIVE',
                'validation_status' => 'INACTIVE',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
            ])
            ->assertSessionHasErrors(['status', 'validation_status']);
    }

    // 7. Generic update with user_id is rejected.

    public function test_generic_update_with_user_id_is_rejected(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);
        $otherUser = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Name',
                'name' => 'Name',
                'user_id' => $otherUser->id,
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
            ])
            ->assertSessionHasErrors('user_id');
    }

    // 8. Omitted PII fields are preserved on sensitive-data update.

    public function test_omitted_pii_fields_are_preserved(): void
    {
        $org = Organization::factory()->create();
        $pengurus = User::factory()->create(['organization_id' => $org->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'identity_number' => 'ORIGINAL-ID-12345678',
            'no_rekening' => 'ORIGINAL-REK-9876',
        ]);

        $this->actingAs($pengurus)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'npwp' => '11.222.333.4-555.678',
            ])
            ->assertSessionHas('success');

        $member->refresh();
        $this->assertSame('ORIGINAL-ID-12345678', $member->identity_number, 'identity_number must be preserved when not submitted.');
        $this->assertSame('ORIGINAL-REK-9876', $member->no_rekening, 'no_rekening must be preserved when not submitted.');
        $this->assertSame('11.222.333.4-555.678', $member->npwp);
    }

    // 9. Explicit clear PII only through authorized sensitive-data action.

    public function test_explicit_clear_pii_only_through_sensitive_data_endpoint(): void
    {
        $org = Organization::factory()->create();
        $pengurus = User::factory()->create(['organization_id' => $org->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'no_rekening' => '1234567890',
        ]);

        $this->actingAs($pengurus)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'no_rekening' => null,
            ])
            ->assertSessionHas('success');

        $this->assertNull($member->refresh()->no_rekening);
    }

    // 10. Web and API behavior are identical.

    public function test_api_update_prohibits_pii_fields_just_like_web(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'identity_number' => 'API-ORIGINAL-12345',
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($admin, ['cooperative.member.write', 'cooperative:write']);

        $this->putJson("/api/v1/members/{$member->id}", [
            'nama_anggota' => 'API Name',
            'name' => 'API Name',
            'identity_number' => 'HACKED-99999',
            'npwp' => 'HACKED-NPWP',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ])->assertStatus(422);

        $this->assertSame('API-ORIGINAL-12345', $member->refresh()->identity_number);
    }

    public function test_api_sensitive_data_update_works_same_as_web(): void
    {
        $org = Organization::factory()->create();
        $pengurus = User::factory()->create(['organization_id' => $org->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($pengurus, ['cooperative.member.write', 'cooperative:write']);

        $this->patchJson("/api/v1/members/{$member->id}/sensitive-data", [
            'identity_number' => 'API-PII-UPDATE-999',
        ])->assertOk();

        $this->assertSame('API-PII-UPDATE-999', $member->refresh()->identity_number);
    }

    public function test_account_linking_requires_reason_and_audit(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $newUser = User::factory()->create(['organization_id' => $org->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => null,
        ]);

        $this->actingAs($admin)
            ->patch(route('cooperative.members.account-link.update', $member), [
                'user_id' => $newUser->id,
                'reason' => 'member_correction',
            ])
            ->assertSessionHas('success');

        $this->assertSame($newUser->id, $member->refresh()->user_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.account.linked',
            'subject_type' => CooperativeMember::class,
            'subject_id' => $member->id,
        ]);
    }
}
