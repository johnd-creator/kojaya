<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\Cooperative\MemberProfileCompletenessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CooperativeMemberValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Pengurus Koperasi']);
        Role::firstOrCreate(['name' => 'Admin Koperasi']);
        Role::firstOrCreate(['name' => 'System Admin']);
        Role::firstOrCreate(['name' => 'Anggota']);
        Permission::firstOrCreate(['name' => 'validate_cooperative_member']);
        Permission::firstOrCreate(['name' => 'verify_cooperative_member']);
        Permission::firstOrCreate(['name' => 'approve_cooperative_member']);
    }

    public function test_admin_can_verify_pending_member_without_assigning_member_role(): void
    {
        $validator = User::factory()->create();
        $validator->givePermissionTo('verify_cooperative_member');
        Role::firstOrCreate(['name' => 'Anggota']);
        $user = User::factory()->create();

        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);
        $validator->forceFill(['organization_id' => $member->organization_id])->save();

        $this->assertFalse($user->hasRole('Anggota'));

        $this->actingAs($validator)
            ->post(route('cooperative.members.validate', $member))
            ->assertRedirect();

        $fresh = $member->fresh();

        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $fresh->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $fresh->status);
        $this->assertNotNull($fresh->admin_validated_at);
        $this->assertSame($validator->id, $fresh->admin_validated_by);
        $this->assertNull($fresh->validated_at);
        $this->assertFalse($user->fresh()->hasRole('Anggota'));
    }

    public function test_pengurus_can_final_approve_admin_verified_member(): void
    {
        $validator = User::factory()->create();
        $validator->assignRole('Pengurus Koperasi');
        $validator->givePermissionTo('approve_cooperative_member');
        Role::firstOrCreate(['name' => 'Anggota']);
        $user = User::factory()->create();

        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
            'admin_validated_at' => now(),
            'admin_validated_by' => User::factory()->create()->id,
        ]);
        $validator->forceFill(['organization_id' => $member->organization_id])->save();

        $this->assertFalse($user->hasRole('Anggota'));

        $this->actingAs($validator)
            ->post(route('cooperative.members.approve-final', $member))
            ->assertRedirect();

        $fresh = $member->fresh();

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $fresh->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $fresh->status);
        $this->assertNotNull($fresh->validated_at);
        $this->assertSame($validator->id, $fresh->validated_by);
        $this->assertTrue($user->fresh()->hasRole('Anggota'));
    }

    public function test_system_admin_can_final_approve_admin_verified_member(): void
    {
        $validator = User::factory()->create();
        $validator->assignRole('System Admin');
        Role::firstOrCreate(['name' => 'Anggota']);
        $user = User::factory()->create();

        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        $this->actingAs($validator)
            ->post(route('cooperative.members.approve-final', $member))
            ->assertRedirect();

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->validation_status);
        $this->assertTrue($user->fresh()->hasRole('Anggota'));
    }

    public function test_admin_cannot_final_approve_member(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin Koperasi');
        $admin->givePermissionTo('verify_cooperative_member');
        $admin->givePermissionTo('approve_cooperative_member');

        $member = CooperativeMember::factory()->create([
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);

        $this->actingAs($admin)
            ->post(route('cooperative.members.approve-final', $member))
            ->assertForbidden();
    }

    public function test_validator_can_reject_pending_member_with_notes(): void
    {
        $validator = User::factory()->create();
        $validator->givePermissionTo('verify_cooperative_member');

        $member = CooperativeMember::factory()->create([
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);
        $validator->forceFill(['organization_id' => $member->organization_id])->save();

        $this->actingAs($validator)
            ->post(route('cooperative.members.reject', $member), [
                'notes' => 'Data KTP tidak terbaca dengan jelas, mohon upload ulang.',
            ])
            ->assertRedirect();

        $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $member->fresh()->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->fresh()->status);
    }

    public function test_revision_sets_member_to_inactive_lifecycle_state(): void
    {
        $validator = User::factory()->create();
        $validator->givePermissionTo('verify_cooperative_member');

        $member = CooperativeMember::factory()->create([
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);
        $validator->forceFill(['organization_id' => $member->organization_id])->save();

        $this->actingAs($validator)
            ->post(route('cooperative.members.request-revision', $member), [
                'notes' => 'Nomor identitas perlu diperbaiki.',
            ])
            ->assertRedirect();

        $this->assertSame(CooperativeMember::VALIDATION_REVISION, $member->fresh()->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->fresh()->status);
    }

    public function test_non_validator_cannot_verify(): void
    {
        $other = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->actingAs($other)
            ->post(route('cooperative.members.validate', $member))
            ->assertForbidden();
    }

    public function test_reject_requires_notes_minimum_length(): void
    {
        $validator = User::factory()->create();
        $validator->givePermissionTo('verify_cooperative_member');
        $member = CooperativeMember::factory()->create([
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->actingAs($validator)
            ->from(route('cooperative.members.index'))
            ->post(route('cooperative.members.reject', $member), ['notes' => 'ok'])
            ->assertRedirect(route('cooperative.members.index'))
            ->assertSessionHasErrors('notes');
    }

    public function test_completeness_service_marks_missing_fields(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'name' => 'Sudah Lengkap',
            'email' => $user->email,
            'phone' => null,
            'address' => 'Alamat',
            'identity_number' => null,
            'jenis_anggota' => 'AB',
            'kategori' => 'IP',
        ]);

        $summary = (new MemberProfileCompletenessService)->summarize($member);

        $this->assertSame(7, $summary['total_fields']);
        $this->assertSame(5, $summary['completed_fields']);
        $this->assertContains('phone', collect($summary['missing'])->pluck('key')->all());
        $this->assertContains('identity_number', collect($summary['missing'])->pluck('key')->all());
    }

    public function test_service_rejects_double_admin_verification(): void
    {
        $validator = User::factory()->create();
        $validator->givePermissionTo('verify_cooperative_member');

        $member = CooperativeMember::factory()->create([
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);
        $validator->forceFill(['organization_id' => $member->organization_id])->save();

        $this->actingAs($validator)
            ->post(route('cooperative.members.validate', $member))
            ->assertStatus(409);
    }

    public function test_same_actor_cannot_final_approve_member_they_verified(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('Pengurus Koperasi');
        $actor->givePermissionTo('approve_cooperative_member');
        $actor->givePermissionTo('verify_cooperative_member');

        $member = CooperativeMember::factory()->create([
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
            'admin_validated_at' => now(),
            'admin_validated_by' => $actor->id,
        ]);
        $actor->forceFill(['organization_id' => $member->organization_id])->save();

        $this->actingAs($actor)
            ->post(route('cooperative.members.approve-final', $member))
            ->assertSessionHasErrors('approved_by');

        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $member->fresh()->validation_status);
        $this->assertNull($member->fresh()->validated_at);
    }

    public function test_system_admin_cannot_final_approve_member_they_verified(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');

        $member = CooperativeMember::factory()->create([
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
            'admin_validated_at' => now(),
            'admin_validated_by' => $admin->id,
        ]);
        $admin->forceFill(['organization_id' => $member->organization_id])->save();

        $this->actingAs($admin)
            ->post(route('cooperative.members.approve-final', $member))
            ->assertSessionHasErrors('approved_by');

        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $member->fresh()->validation_status);
        $this->assertNull($member->fresh()->validated_at);
    }

    public function test_admin_verification_and_approval_produces_audit_logs(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('verify_cooperative_member');

        $pengurus = User::factory()->create();
        $pengurus->assignRole('Pengurus Koperasi');
        $pengurus->givePermissionTo('approve_cooperative_member');

        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $admin->forceFill(['organization_id' => $member->organization_id])->save();
        $pengurus->forceFill(['organization_id' => $member->organization_id])->save();

        // 1. Admin verification
        $this->actingAs($admin)
            ->post(route('cooperative.members.validate', $member), [
                'notes' => 'Verifikasi berkas administratif pelamar valid.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.status.transitioned',
            'module' => 'cooperative.lifecycle',
            'user_id' => $admin->id,
        ]);

        // 2. Final Approval by different user (Pengurus)
        $this->actingAs($pengurus)
            ->post(route('cooperative.members.approve-final', $member), [
                'notes' => 'Persetujuan akhir keanggotaan disetujui Pengurus.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.status.transitioned',
            'module' => 'cooperative.lifecycle',
            'user_id' => $pengurus->id,
        ]);

        $fresh = $member->fresh();
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $fresh->status);
        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $fresh->validation_status);
    }
}
