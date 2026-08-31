<?php

namespace Tests\Feature\Api\V1;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CooperativeMemberApiControllerOpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(string $permissionBag = 'all'): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'Coop API Test '.$permissionBag]);
        $role->syncPermissions(
            $permissionBag === 'all'
                ? ['view_cooperative_member', 'manage_cooperative_member', 'manage_cooperative_opening_balance']
                : ['view_cooperative_member', 'manage_cooperative_member']
        );
        $user->assignRole($role);

        Sanctum::actingAs($user, ['cooperative.member.read', 'cooperative.member.write', 'cooperative.ledger.read']);

        return $user;
    }

    public function test_api_store_with_opening_saving_balance_requires_wizard_even_when_user_lacks_wizard_permission(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->actingAdmin('no-wizard');
        $admin->forceFill(['organization_id' => $organization->id])->save();

        $response = $this->postJson('/api/v1/members', [
            'name' => 'Anggota API Legacy',
            'nama_anggota' => 'Anggota API Legacy',
            'email' => 'api-legacy@test.local',
            'phone' => '081234',
            'tanggal_aktif' => '2020-01-01',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
            'opening_saving_balance' => 175000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'api-legacy@test.local')
            ->assertJsonPath('meta.opening_balance.mode', 'wizard_required');

        $member = CooperativeMember::query()->where('email', 'api-legacy@test.local')->firstOrFail();
        $this->assertDatabaseMissing('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'OPENING_BALANCE',
            'source_type' => CooperativeMember::class,
        ]);
    }

    public function test_api_store_with_opening_saving_balance_returns_wizard_metadata_when_user_has_permission(): void
    {
        $admin = $this->actingAdmin('all');

        $organization = Organization::factory()->create();
        $admin->forceFill(['organization_id' => $organization->id])->save();

        $response = $this->postJson('/api/v1/members', [
            'name' => 'Anggota API Wizard',
            'nama_anggota' => 'Anggota API Wizard',
            'email' => 'api-wizard@test.local',
            'phone' => '081234',
            'tanggal_aktif' => '2020-01-01',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'IP',
            'autodebet' => 'MANUAL',
            'opening_saving_balance' => 250000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('meta.opening_balance.mode', 'wizard_required');

        $member = CooperativeMember::query()->where('email', 'api-wizard@test.local')->firstOrFail();
        $this->assertDatabaseMissing('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'OPENING_BALANCE',
            'source_type' => CooperativeMember::class,
        ]);
    }

    public function test_api_update_with_existing_wizard_batch_keeps_requiring_the_wizard(): void
    {
        $admin = $this->actingAdmin('all');

        $organization = Organization::factory()->create();
        $admin->forceFill(['organization_id' => $organization->id])->save();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'tanggal_aktif' => '2020-01-01',
        ]);

        CooperativeMemberOpeningBalanceBatch::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'status' => 'POSTED',
            'calculation_start_period' => '2020-01-01',
            'calculation_end_period' => '2020-01-31',
            'months_count' => 1,
            'total_amount' => 100000,
            'source_type' => 'BOARD_DECISION',
        ]);

        $response = $this->putJson("/api/v1/members/{$member->id}", [
            'name' => $member->name,
            'nama_anggota' => $member->nama_anggota,
            'email' => $member->email,
            'phone' => $member->phone,
            'no_anggota' => $member->no_anggota,
            'jenis_anggota' => $member->jenis_anggota,
            'jenis_kelamin' => $member->jenis_kelamin,
            'kategori' => $member->kategori,
            'autodebet' => $member->autodebet,
            'opening_saving_balance' => 500000,
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.opening_balance.mode', 'wizard_required');

        // Ledger wizard tidak boleh ter-overwrite oleh API.
        $this->assertSame(
            0,
            CooperativeLedgerEntry::query()
                ->where('cooperative_member_id', $member->id)
                ->where('source_type', CooperativeMember::class)
                ->count()
        );
    }

    public function test_api_update_without_opening_saving_balance_does_not_change_ledger(): void
    {
        $admin = $this->actingAdmin('no-wizard');

        $organization = Organization::factory()->create();
        $admin->forceFill(['organization_id' => $organization->id])->save();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'tanggal_aktif' => '2020-01-01',
        ]);

        $existing = CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'OPENING_BALANCE',
            'source_type' => CooperativeMember::class,
            'source_id' => $member->id,
            'credit' => 50000,
            'debit' => 0,
            'posted_at' => '2020-01-15',
            'description' => 'Saldo awal lama.',
        ]);

        $response = $this->putJson("/api/v1/members/{$member->id}", [
            'name' => $member->name,
            'nama_anggota' => $member->nama_anggota,
            'email' => $member->email,
            'phone' => $member->phone,
            'no_anggota' => $member->no_anggota,
            'jenis_anggota' => $member->jenis_anggota,
            'jenis_kelamin' => $member->jenis_kelamin,
            'kategori' => $member->kategori,
            'autodebet' => $member->autodebet,
        ]);

        $response->assertOk();

        $existing->refresh();
        $this->assertSame((float) 50000, (float) $existing->credit);
    }
}
