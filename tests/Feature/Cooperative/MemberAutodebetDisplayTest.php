<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberAutodebetDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Koperasi Unit Satu',
        ]);

        $this->admin = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Admin Koperasi Unit',
            'email' => 'admin.kop@kojaya.test',
        ]);
        $this->admin->assignRole('Admin Koperasi');
        $this->admin->givePermissionTo([
            'view_cooperative_member',
            'manage_cooperative_member',
        ]);
    }

    public function test_member_list_payload_exposes_autodebet_values_bni_bri_manual(): void
    {
        $mBni = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => '001',
            'nama_anggota' => 'Member BNI',
            'name' => 'Member BNI',
            'autodebet' => 'BNI',
        ]);

        $mBri = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => '002',
            'nama_anggota' => 'Member BRI',
            'name' => 'Member BRI',
            'autodebet' => 'BRI',
        ]);

        $mManual = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => '003',
            'nama_anggota' => 'Member Manual',
            'name' => 'Member Manual',
            'autodebet' => 'MANUAL',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('cooperative.members.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/Index')
                ->has('members.data', 3)
                ->where('members.data', function ($items) use ($mBni, $mBri, $mManual): bool {
                    $bni = collect($items)->firstWhere('id', $mBni->id);
                    $bri = collect($items)->firstWhere('id', $mBri->id);
                    $manual = collect($items)->firstWhere('id', $mManual->id);

                    return $bni['autodebet'] === 'BNI'
                        && $bri['autodebet'] === 'BRI'
                        && $manual['autodebet'] === 'MANUAL';
                })
            );
    }

    public function test_member_list_payload_does_not_expose_unmasked_sensitive_pii(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => '004',
            'nama_anggota' => 'Member Sensitive',
            'name' => 'Member Sensitive',
            'autodebet' => 'BNI',
            'identity_number' => '3201234567890001',
            'no_rekening' => '009123456789',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('cooperative.members.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Members/Index')
                ->where('members.data.0.autodebet', 'BNI')
                ->where('members.data.0.no_rekening', fn ($val) => $val !== '009123456789' && str_ends_with((string) $val, '6789'))
                ->where('members.data.0.identity_number', fn ($val) => $val !== '3201234567890001' && str_ends_with((string) $val, '0001'))
            );
    }

    public function test_member_autodebet_update_consistency_between_manual_and_bni(): void
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'no_anggota' => '005',
            'nama_anggota' => 'Member Toggle',
            'name' => 'Member Toggle',
            'autodebet' => 'MANUAL',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
        ]);

        // 1. Initial list check -> MANUAL
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('members.data.0.autodebet', 'MANUAL')
            );

        // 2. Update to BNI
        $this->actingAs($this->admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Member Toggle',
                'name' => 'Member Toggle',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'BNI',
            ])
            ->assertRedirect(route('cooperative.members.index'));

        $this->assertSame('BNI', $member->refresh()->autodebet);

        // 3. Reload list -> BNI displayed
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('members.data.0.autodebet', 'BNI')
            );

        // 4. Update back to MANUAL
        $this->actingAs($this->admin)
            ->put(route('cooperative.members.update', $member), [
                'nama_anggota' => 'Member Toggle',
                'name' => 'Member Toggle',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'KOP',
                'autodebet' => 'MANUAL',
            ])
            ->assertRedirect(route('cooperative.members.index'));

        $this->assertSame('MANUAL', $member->refresh()->autodebet);

        // 5. Reload list -> MANUAL displayed
        $this->actingAs($this->admin)
            ->get(route('cooperative.members.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('members.data.0.autodebet', 'MANUAL')
            );
    }
}
