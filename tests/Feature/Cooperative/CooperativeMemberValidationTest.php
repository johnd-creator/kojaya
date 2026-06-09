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
        Role::firstOrCreate(['name' => 'Anggota']);
        Permission::firstOrCreate(['name' => 'validate_cooperative_member']);
    }

    public function test_validator_can_approve_pending_member(): void
    {
        $validator = User::factory()->create();
        $validator->givePermissionTo('validate_cooperative_member');
        Role::firstOrCreate(['name' => 'Anggota']);
        $user = User::factory()->create();

        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->assertFalse($user->hasRole('Anggota'));

        $this->actingAs($validator)
            ->post(route('cooperative.members.validate', $member))
            ->assertRedirect();

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $member->fresh()->validation_status);
        $this->assertNotNull($member->fresh()->validated_at);
        $this->assertTrue($user->fresh()->hasRole('Anggota'));
    }

    public function test_validator_can_reject_pending_member_with_notes(): void
    {
        $validator = User::factory()->create();
        $validator->givePermissionTo('validate_cooperative_member');

        $member = CooperativeMember::factory()->create([
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->actingAs($validator)
            ->post(route('cooperative.members.reject', $member), [
                'notes' => 'Data KTP tidak terbaca dengan jelas, mohon upload ulang.',
            ])
            ->assertRedirect();

        $this->assertSame(CooperativeMember::VALIDATION_REJECTED, $member->fresh()->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_INACTIVE, $member->fresh()->status);
    }

    public function test_revision_does_not_overwrite_member_lifecycle_status(): void
    {
        $validator = User::factory()->create();
        $validator->givePermissionTo('validate_cooperative_member');

        $member = CooperativeMember::factory()->create([
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->actingAs($validator)
            ->post(route('cooperative.members.request-revision', $member), [
                'notes' => 'Nomor identitas perlu diperbaiki.',
            ])
            ->assertRedirect();

        $this->assertSame(CooperativeMember::VALIDATION_REVISION, $member->fresh()->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $member->fresh()->status);
    }

    public function test_non_validator_cannot_approve(): void
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
        $validator->givePermissionTo('validate_cooperative_member');
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

    public function test_service_rejects_double_validation(): void
    {
        $validator = User::factory()->create();
        $validator->givePermissionTo('validate_cooperative_member');

        $member = CooperativeMember::factory()->create([
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $this->actingAs($validator)
            ->post(route('cooperative.members.validate', $member))
            ->assertStatus(409);
    }
}
