<?php

namespace Tests\Feature\MemberPortal;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $user = User::factory()->create(['email' => 'original@example.com']);
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'name' => 'Awal',
            'email' => $user->email,
            'phone' => null,
            'identity_number' => '3201000000000001',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'no_rekening' => '9999999999',
            'nama_bank' => 'BCA',
            'nama_pemilik_rekening' => 'Original Holder',
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'onboarding_submitted_at' => null,
        ]);
        $user->assignRole('Anggota');

        $payload = [
            'name' => 'Andi Susilo',
            'email' => 'andi@example.com', // Attempt to mutate email
            'phone' => '08123456789',
            'address' => 'Jl. Sudirman No. 1',
            'identity_number' => '3201234567890001', // Attempt to mutate NIK
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'CDB', // Attempt to mutate kategori
            'tanggal_lahir' => '1990-01-01',
            'tempat_lahir' => 'Bandung',
            'pekerjaan' => 'Operator',
            'no_rekening' => '123456', // Attempt to mutate bank
            'nama_bank' => 'BNI',
            'nama_pemilik_rekening' => 'Andi Susilo',
        ];

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), $payload)
            ->assertRedirect('/member/onboarding')
            ->assertSessionHas('success');

        $fresh = $member->fresh();
        // Safe fields are updated
        $this->assertSame('Andi Susilo', $fresh->name);
        $this->assertSame('08123456789', $fresh->phone);
        $this->assertSame('Jl. Sudirman No. 1', $fresh->address);
        $this->assertSame('Bandung', $fresh->tempat_lahir);
        $this->assertNotNull($fresh->onboarding_submitted_at);
        $this->assertNotNull($fresh->profile_completed_at);

        // Sensitive PII fields MUST NOT be mutated (R1-01)
        $this->assertSame('original@example.com', $user->fresh()->email);
        $this->assertSame('3201000000000001', $fresh->identity_number);
        $this->assertSame('IP', $fresh->kategori);
        $this->assertSame('9999999999', $fresh->no_rekening);
        $this->assertSame('BCA', $fresh->nama_bank);
        $this->assertSame('Original Holder', $fresh->nama_pemilik_rekening);

        // Status & validation_status MUST NOT self-advance (ONB-03)
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $fresh->validation_status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING, $fresh->status);

        $audit = AuditLog::query()->where('action', 'member.profile.updated')->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertSame($member->id, $audit->subject_id);
    }

    public function test_legacy_sensitive_pii_in_post_is_safely_ignored_and_not_mutated(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'identity_number' => '3201234567890088',
            'npwp' => '12.345.678.9-000.000',
            'kategori' => 'IP',
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => 'Updated Name',
                'email' => 'attacker@example.com',
                'phone' => '08123456789',
                'address' => 'Jl. Sudirman No. 1',
                'identity_number' => '3201234567890099',
                'npwp' => '99.999.999.9-999.999',
                'kategori' => 'CDB',
                'perusahaan' => 'PT Hacked',
                'no_rekening' => '9999999999',
                'nama_bank' => 'Bank Hacked',
            ])
            ->assertRedirect('/member/onboarding')
            ->assertSessionHas('success');

        $fresh = $member->fresh();
        // Safe fields updated
        $this->assertSame('Updated Name', $fresh->name);
        $this->assertSame('08123456789', $fresh->phone);
        // Sensitive PII strictly preserved
        $this->assertSame('3201234567890088', $fresh->identity_number);
        $this->assertSame('12.345.678.9-000.000', $fresh->npwp);
        $this->assertSame('IP', $fresh->kategori);
        $this->assertSame('original@example.com', $user->fresh()->email);
    }

    public function test_active_member_cannot_submit_onboarding(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'identity_number' => '3201234567890011',
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => 'Andi Susilo',
                'phone' => '08123456789',
                'address' => 'Alamat',
            ])
            ->assertForbidden();
    }

    public function test_under_review_member_cannot_submit_onboarding(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => 'Andi Susilo',
                'phone' => '08123456789',
                'address' => 'Alamat',
            ])
            ->assertForbidden();
    }

    public function test_rejected_member_cannot_submit_onboarding(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_INACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_REJECTED,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => 'Andi Susilo',
                'phone' => '08123456789',
                'address' => 'Alamat',
            ])
            ->assertForbidden();
    }

    public function test_non_member_cannot_submit_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('member.onboarding.submit'), [
                'name' => 'Andi',
                'phone' => '08123456789',
                'address' => 'Alamat',
            ])
            ->assertRedirect(route('dashboard'));
    }

    public function test_onboarding_submit_does_not_emit_submitted_for_validation_notification(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'status' => CooperativeMember::VALIDATION_PENDING,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
        ]);
        $user->assignRole('Anggota');

        // Verify no notification dispatcher call or fake notification in DB
        $initialNotificationCount = \Illuminate\Support\Facades\DB::table('notifications')->count();

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => 'Andi Test',
                'phone' => '08123456789',
                'address' => 'Alamat',
            ])
            ->assertRedirect('/member/onboarding');

        $this->assertSame($initialNotificationCount, \Illuminate\Support\Facades\DB::table('notifications')->count());
    }
}
