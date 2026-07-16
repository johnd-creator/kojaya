<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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
        $before = $member->getRawOriginal('identity_number_enc');

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'identity_number' => '********0001',
            ])
            ->assertSessionHasErrors(['identity_number']);

        $this->assertSame($before, $member->refresh()->getRawOriginal('identity_number_enc'));
    }

    public function test_masked_npwp_is_never_persisted(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();
        $before = $member->getRawOriginal('npwp_enc');

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'npwp' => '****.###.###.#-###.###',
            ])
            ->assertSessionHasErrors(['npwp']);

        $this->assertSame($before, $member->refresh()->getRawOriginal('npwp_enc'));
    }

    public function test_masked_bank_account_number_is_never_persisted(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();
        $before = $member->getRawOriginal('no_rekening_enc');

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'no_rekening' => '**********1234',
            ])
            ->assertSessionHasErrors(['no_rekening']);

        $this->assertSame($before, $member->refresh()->getRawOriginal('no_rekening_enc'));
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

    public function test_generic_update_payload_preserves_sensitive_values(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();
        $before = $member->only(['identity_number_enc', 'npwp_enc', 'no_rekening_enc']);

        $this->actingAs($admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Nama profil baru',
                'name' => 'Nama profil baru',
                'email' => $member->email,
                'no_telp' => $member->no_telp,
                'phone' => $member->phone,
                'jenis_anggota' => $member->jenis_anggota,
                'jenis_kelamin' => $member->jenis_kelamin,
                'kategori' => $member->kategori,
                'autodebet' => $member->autodebet,
            ])
            ->assertRedirect();

        $this->assertSame($before, $member->refresh()->only(['identity_number_enc', 'npwp_enc', 'no_rekening_enc']));
    }

    public function test_explicit_null_clears_only_the_selected_sensitive_field(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();
        $npwpBefore = $member->getRawOriginal('npwp_enc');
        $accountBefore = $member->getRawOriginal('no_rekening_enc');

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'identity_number' => null,
            ])
            ->assertSessionHas('success');

        $member->refresh();
        $this->assertNull($member->getRawOriginal('identity_number_enc'));
        $this->assertSame($npwpBefore, $member->getRawOriginal('npwp_enc'));
        $this->assertSame($accountBefore, $member->getRawOriginal('no_rekening_enc'));
    }

    public function test_api_rejects_masked_values_without_persisting_them(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();
        Sanctum::actingAs($admin, ['*']);
        $before = $member->only(['identity_number_enc', 'npwp_enc', 'no_rekening_enc']);

        foreach ([
            'identity_number' => '********0001',
            'npwp' => '****.###.###.#-###.###',
            'no_rekening' => '**********1234',
        ] as $field => $value) {
            $this->patchJson("/api/v1/members/{$member->id}/sensitive-data", [$field => $value])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([$field]);
        }

        $this->assertSame($before, $member->refresh()->only(['identity_number_enc', 'npwp_enc', 'no_rekening_enc']));
    }

    public function test_api_omitted_sensitive_values_are_preserved_and_explicit_clear_is_scoped(): void
    {
        [$admin, $member] = $this->setupMemberWithAdmin();
        Sanctum::actingAs($admin, ['*']);
        $npwpBefore = $member->getRawOriginal('npwp_enc');
        $accountBefore = $member->getRawOriginal('no_rekening_enc');

        $this->patchJson("/api/v1/members/{$member->id}/sensitive-data", [
            'nama_bank' => 'Bank baru',
        ])->assertOk();

        $this->patchJson("/api/v1/members/{$member->id}/sensitive-data", [
            'identity_number' => null,
        ])->assertOk();

        $member->refresh();
        $this->assertNull($member->getRawOriginal('identity_number_enc'));
        $this->assertSame($npwpBefore, $member->getRawOriginal('npwp_enc'));
        $this->assertSame($accountBefore, $member->getRawOriginal('no_rekening_enc'));
    }

    public function test_cross_organization_update_is_denied(): void
    {
        $otherOrg = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $otherOrg->id]);
        $admin->assignRole('Admin Koperasi');

        $org = Organization::factory()->create();
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'identity_number' => '1601234567890001',
        ]);
        $before = $member->getRawOriginal('identity_number_enc');

        $this->actingAs($admin)
            ->patch(route('cooperative.members.sensitive-data.update', $member), [
                'identity_number' => '3201234567890123',
            ])
            ->assertForbidden();

        $this->assertSame($before, $member->refresh()->getRawOriginal('identity_number_enc'));
    }

    public function test_api_cross_organization_update_is_denied_without_mutation(): void
    {
        $otherOrg = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $otherOrg->id]);
        $admin->assignRole('Admin Koperasi');

        $org = Organization::factory()->create();
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'identity_number' => '1601234567890001',
        ]);
        $before = $member->getRawOriginal('identity_number_enc');
        Sanctum::actingAs($admin, ['*']);

        $this->patchJson("/api/v1/members/{$member->id}/sensitive-data", [
            'identity_number' => '3201234567890123',
        ])->assertForbidden();

        $this->assertSame($before, $member->refresh()->getRawOriginal('identity_number_enc'));
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
