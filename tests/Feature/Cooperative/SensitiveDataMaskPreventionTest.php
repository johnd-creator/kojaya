<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveDataMaskPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_omitted_pii_remains_unchanged_through_sensitive_data_endpoint(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();
        $originalIdentity = $member->getRawOriginal('identity_number_enc');

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'nama_bank' => 'BCA',
            ])
            ->assertSessionHas('success');

        $member->refresh();
        $this->assertSame($originalIdentity, $member->getRawOriginal('identity_number_enc'));
    }

    public function test_masked_identity_number_is_never_persisted(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'identity_number' => '********0001',
            ])
            ->assertSessionHasErrors(['identity_number']);
    }

    public function test_masked_npwp_is_never_persisted(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'npwp' => '****.###.###.#-###.###',
            ])
            ->assertSessionHasErrors(['npwp']);
    }

    public function test_masked_bank_account_number_is_never_persisted(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'no_rekening' => '**********1234',
            ])
            ->assertSessionHasErrors(['no_rekening']);
    }

    public function test_valid_new_value_replaces_existing_value(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'identity_number' => '3201234567890123',
            ])
            ->assertSessionHas('success');

        $member->refresh();
        $this->assertSame('3201234567890123', $member->identity_number);
    }

    public function test_generic_update_rejects_pii_fields(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => $member->nama_anggota,
                'name' => $member->name,
                'identity_number' => '3201234567890001',
                'npwp' => '12.345.678.9-012.000',
                'no_rekening' => '1234567890',
                'jenis_anggota' => $member->jenis_anggota,
                'jenis_kelamin' => $member->jenis_kelamin,
                'kategori' => $member->kategori,
                'autodebet' => $member->autodebet,
            ])
            ->assertSessionHasErrors(['identity_number', 'npwp', 'no_rekening']);
    }

    public function test_cross_organization_update_is_denied(): void
    {
        $otherOrg = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $otherOrg->id]);
        $admin->assignRole('Pengurus Koperasi');

        $org = Organization::factory()->create();
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'identity_number' => '3201234567890123',
            ])
            ->assertRedirect();
    }

    /**
     * @return array{0: User, 1: CooperativeMember}
     */
    private function setupMemberWithAdmin(): array
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Pengurus Koperasi');

        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'identity_number' => '1601234567890001',
            'npwp' => '12.345.678.9-012.000',
            'no_rekening' => '9876543210',
        ]);

        return [$admin, $member];
    }
}
