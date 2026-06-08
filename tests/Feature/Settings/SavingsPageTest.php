<?php

namespace Tests\Feature\Settings;

use App\Models\CooperativeContributionType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SavingsPageTest extends TestCase
{
    use DatabaseMigrations;

    public function test_savings_page_is_displayed_for_cooperative_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');

        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('settings.savings.edit'))
            ->assertOk();
    }
}
