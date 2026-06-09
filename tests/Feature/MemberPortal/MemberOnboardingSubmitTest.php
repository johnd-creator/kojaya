<?php

namespace Tests\Feature\MemberPortal;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberOnboardingSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Anggota']);
        Role::firstOrCreate(['name' => 'Pengurus Koperasi']);
    }

    public function test_member_can_submit_complete_onboarding(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'name' => 'Awal',
            'email' => $user->email,
            'phone' => null,
            'identity_number' => null,
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
            'onboarding_submitted_at' => null,
        ]);
        $user->assignRole('Anggota');

        $payload = [
            'name' => 'Andi Susilo',
            'email' => 'andi@example.com',
            'phone' => '08123456789',
            'address' => 'Jl. Sudirman No. 1',
            'identity_number' => '3201234567890001',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Bandung',
            'pekerjaan' => 'Operator',
            'perusahaan' => 'PT. Listrik',
            'no_rekening' => '123456',
            'nama_bank' => 'BNI',
            'nama_pemilik_rekening' => 'Andi Susilo',
        ];

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), $payload)
            ->assertRedirect('/member/onboarding')
            ->assertSessionHas('success');

        $fresh = $member->fresh();
        $this->assertSame('Andi Susilo', $fresh->name);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $fresh->validation_status);
        $this->assertNotNull($fresh->onboarding_submitted_at);
        $this->assertNotNull($fresh->profile_completed_at);

        $this->assertSame('andi@example.com', $user->fresh()->email);

        $audit = AuditLog::query()->where('action', 'sso.member_onboarding.submitted')->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertSame($member->id, $audit->subject_id);
    }

    public function test_member_cannot_submit_duplicate_identity_number(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'identity_number' => '3201234567890088',
        ]);
        $user->assignRole('Anggota');

        CooperativeMember::factory()->create([
            'identity_number' => '3201234567890099',
        ]);

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => 'Andi Susilo',
                'email' => 'andi@example.com',
                'phone' => '08123456789',
                'address' => 'Jl. Sudirman No. 1',
                'identity_number' => '3201234567890099',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'IP',
            ])
            ->assertRedirect('/member/onboarding')
            ->assertSessionHasErrors('identity_number');
    }

    public function test_onboarding_submits_only_once_when_already_approved(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'identity_number' => '3201234567890011',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            'onboarding_submitted_at' => Carbon::now()->subDay(),
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => 'Andi Susilo',
                'email' => 'andi@example.com',
                'phone' => '08123456789',
                'address' => 'Alamat',
                'identity_number' => '3201234567890011',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'IP',
            ])
            ->assertRedirect('/member/onboarding')
            ->assertSessionHas('success');

        $fresh = $member->fresh();
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $fresh->validation_status);
    }

    public function test_request_rejects_empty_required_fields(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'identity_number' => '3201234567890091',
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => '',
                'email' => 'bukan-email',
                'phone' => '',
                'address' => '',
                'identity_number' => '',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'IP',
            ])
            ->assertRedirect('/member/onboarding')
            ->assertSessionHasErrors(['name', 'email', 'phone', 'address', 'identity_number']);
    }

    public function test_non_member_cannot_submit_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Andi',
                'email' => 'andi@example.com',
                'phone' => '08123456789',
                'address' => 'Alamat',
                'identity_number' => '3201234567890002',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'IP',
            ])
            ->assertRedirect(route('dashboard'));
    }
}
