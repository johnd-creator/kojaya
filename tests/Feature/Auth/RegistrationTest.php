<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_members_can_register_and_get_redirected_to_member_portal()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Anggota Baru',
            'email' => 'anggota@example.com',
            'phone' => '081234567890',
            'identity_number' => '3273010101010001',
            'address' => 'Jl. Koperasi No. 1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('member.dashboard', absolute: false));

        $this->assertDatabaseHas('cooperative_members', [
            'email' => 'anggota@example.com',
            'name' => 'Anggota Baru',
            'status' => 'PENDING',
            'validation_status' => 'PENDING',
        ]);

        $user = auth()->user();
        $this->assertTrue($user->hasRole('Anggota'));
        $this->assertNotNull($user->cooperativeMember);
        $this->assertNotNull($user->cooperativeMember->member_no);
    }

    public function test_registration_fails_without_required_fields()
    {
        $response = $this->post(route('register.store'), [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_registration_fails_with_invalid_email()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Anggota Baru',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_registration_fails_with_short_password()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Anggota Baru',
            'email' => 'anggota@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_registration_fails_with_password_mismatch()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Anggota Baru',
            'email' => 'anggota@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_registration_fails_with_duplicate_identity_number()
    {
        \App\Models\CooperativeMember::factory()->create([
            'identity_number' => '3273010101010001',
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'Anggota Baru',
            'email' => 'anggota@example.com',
            'phone' => '081234567890',
            'identity_number' => '3273010101010001',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['identity_number']);
    }

    public function test_registration_fails_with_duplicate_email()
    {
        \App\Models\User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'Anggota Baru',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}
