<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MemberActivationTest extends TestCase
{
    use DatabaseMigrations;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('System Admin');
    }

    public function test_pending_member_cannot_be_activated_directly(): void
    {
        $member = CooperativeMember::factory()->create([
            'no_anggota' => 'TMP0LNWGQU',
            'member_no' => 'TMP0LNWGQU',
            'status' => 'PENDING',
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);

        $this->actingAs($this->admin)
            ->post(route('cooperative.members.activate', $member))
            ->assertRedirect();

        $member->refresh();

        $this->assertSame('PENDING', $member->status);
        $this->assertSame('TMP0LNWGQU', $member->no_anggota);
        $this->assertSame($member->no_anggota, $member->member_no);
    }

    public function test_activate_keeps_existing_kop_number(): void
    {
        $member = CooperativeMember::factory()->create([
            'no_anggota' => 'KOP-005',
            'member_no' => 'KOP-005',
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_INACTIVE,
        ]);

        $this->actingAs($this->admin)
            ->post(route('cooperative.members.activate', $member))
            ->assertRedirect();

        $member->refresh();

        $this->assertSame('ACTIVE', $member->status);
        $this->assertSame('KOP-005', $member->no_anggota);
        $this->assertSame('KOP-005', $member->member_no);
    }

    public function test_store_auto_generates_kop_number_when_empty(): void
    {
        $this->actingAs($this->admin)->post(route('cooperative.members.store'), [
            'tanggal_aktif' => '2026-01-01',
            'nama_anggota' => 'Test Member',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ])->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()
            ->where('name', 'Test Member')
            ->firstOrFail();

        $this->assertMatchesRegularExpression('/^KOP-\d+$/', $member->no_anggota);
        $this->assertSame($member->no_anggota, $member->member_no);
    }

    public function test_store_keeps_manual_kop_number(): void
    {
        $this->actingAs($this->admin)->post(route('cooperative.members.store'), [
            'no_anggota' => 'KOP-042',
            'tanggal_aktif' => '2026-01-01',
            'nama_anggota' => 'Manual Member',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
        ])->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()
            ->where('name', 'Manual Member')
            ->firstOrFail();

        $this->assertSame('KOP-042', $member->no_anggota);
        $this->assertSame('KOP-042', $member->member_no);
    }
}
