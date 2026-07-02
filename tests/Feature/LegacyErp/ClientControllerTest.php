<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // Single-permission role granting clients management to test users.
        $role = Role::query()->firstOrCreate(['name' => 'Client Manager', 'guard_name' => 'web']);
        $role->syncPermissions([
            Permission::query()->firstOrCreate(['name' => 'manage_clients', 'guard_name' => 'web']),
        ]);

        Client::factory()->count(5)->create(['client_type' => 'PLN']);
        Client::factory()->count(3)->create(['client_type' => 'PRIVATE']);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Client Manager');

        return $user;
    }

    public function test_user_can_view_clients_index(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Index')
                ->has('clients')
                ->has('stats')
                ->where('stats.total_clients', 8)
                ->where('stats.total_pln', 5)
                ->where('stats.total_private', 3)
            );
    }

    public function test_can_search_clients_by_name(): void
    {
        $client = Client::factory()->create(['name' => 'PT Test Company']);
        $user = $this->actingUser();

        $this->actingAs($user)
            ->get(route('clients.index', ['search' => 'Test Company']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Index')
                ->has('clients')
                ->count('clients.data', 1)
            );
    }

    public function test_can_filter_clients_by_type(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user)
            ->get(route('clients.index', ['client_type' => 'PLN']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Index')
                ->has('clients')
                ->count('clients.data', 5)
            );
    }

    public function test_can_view_create_client_form(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user)
            ->get(route('clients.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Create')
                ->has('organizations')
            );
    }

    public function test_can_create_client(): void
    {
        $user = $this->actingUser();

        $clientData = [
            'code' => 'CLI-001',
            'name' => 'PT New Client',
            'address' => 'Jl. Test No. 123',
            'tax_id' => 'NPWP-123456789',
            'contact_person' => 'John Doe',
            'phone' => '08123456789',
            'email' => 'john@example.com',
            'client_type' => 'PRIVATE',
            'organization_id' => null,
        ];

        $this->actingAs($user)
            ->post(route('clients.store'), $clientData)
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'code' => 'CLI-001',
            'name' => 'PT New Client',
            'email' => 'john@example.com',
        ]);
    }

    public function test_client_code_must_be_unique(): void
    {
        $existingClient = Client::factory()->create(['code' => 'CLI-001']);
        $user = $this->actingUser();

        $clientData = [
            'code' => 'CLI-001',
            'name' => 'PT Duplicate Code',
            'contact_person' => 'Jane Doe',
            'phone' => '08123456789',
            'email' => 'jane@example.com',
            'client_type' => 'PRIVATE',
        ];

        $this->actingAs($user)
            ->post(route('clients.store'), $clientData)
            ->assertSessionHasErrors('code');
    }

    public function test_can_view_client_details(): void
    {
        $client = Client::factory()->create();
        $user = $this->actingUser();

        $this->actingAs($user)
            ->get(route('clients.show', $client))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Show')
                ->has('client')
            );
    }

    public function test_can_view_edit_client_form(): void
    {
        $client = Client::factory()->create();
        $user = $this->actingUser();

        $this->actingAs($user)
            ->get(route('clients.edit', $client))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Edit')
                ->has('client')
                ->has('organizations')
            );
    }

    public function test_can_update_client(): void
    {
        $client = Client::factory()->create(['name' => 'Old Name']);
        $user = $this->actingUser();

        $updatedData = [
            'code' => $client->code,
            'name' => 'Updated Name',
            'address' => $client->address,
            'tax_id' => $client->tax_id,
            'contact_person' => $client->contact_person,
            'phone' => $client->phone,
            'email' => $client->email,
            'client_type' => $client->client_type,
            'organization_id' => $client->organization_id,
        ];

        $this->actingAs($user)
            ->put(route('clients.update', $client), $updatedData)
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_client(): void
    {
        $client = Client::factory()->create();
        $user = $this->actingUser();

        $this->actingAs($user)
            ->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'));

        $this->assertDatabaseMissing('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_validates_required_fields(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user)
            ->post(route('clients.store'), [])
            ->assertSessionHasErrors(['code', 'name', 'contact_person', 'phone', 'email', 'client_type']);
    }

    public function test_validates_email_format(): void
    {
        $user = $this->actingUser();

        $clientData = [
            'code' => 'CLI-001',
            'name' => 'PT Test',
            'contact_person' => 'John Doe',
            'phone' => '08123456789',
            'email' => 'invalid-email',
            'client_type' => 'PRIVATE',
        ];

        $this->actingAs($user)
            ->post(route('clients.store'), $clientData)
            ->assertSessionHasErrors(['email']);
    }

    public function test_validates_client_type_values(): void
    {
        $user = $this->actingUser();

        $clientData = [
            'code' => 'CLI-001',
            'name' => 'PT Test',
            'contact_person' => 'John Doe',
            'phone' => '08123456789',
            'email' => 'test@example.com',
            'client_type' => 'INVALID_TYPE',
        ];

        $this->actingAs($user)
            ->post(route('clients.store'), $clientData)
            ->assertSessionHasErrors(['client_type']);
    }
}
