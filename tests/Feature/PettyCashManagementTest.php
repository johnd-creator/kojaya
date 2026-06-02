<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PettyCashAccount;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PettyCashManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function financeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Finance Pusat');

        return $user;
    }

    public function test_user_can_view_petty_cash_index(): void
    {
        $user = $this->financeUser();
        PettyCashAccount::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('petty-cash.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PettyCash/Index')
                ->has('accounts', 2)
                ->has('organizations')
            );
    }

    public function test_user_can_create_petty_cash_account(): void
    {
        $user = $this->financeUser();
        $organization = Organization::factory()->create();

        $this->actingAs($user)
            ->from(route('petty-cash.index'))
            ->post(route('petty-cash.store'), [
                'organization_id' => $organization->id,
                'name' => 'Kas Operasional',
                'limit' => 5000000,
                'description' => 'Untuk operasional harian',
                'status' => 'ACTIVE',
            ])
            ->assertRedirect(route('petty-cash.index'));

        $this->assertDatabaseHas('petty_cash_accounts', [
            'organization_id' => $organization->id,
            'name' => 'Kas Operasional',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_user_can_record_debit_transaction_and_balance_is_increased(): void
    {
        $user = $this->financeUser();
        $account = PettyCashAccount::factory()->create(['balance' => 1000000]);

        $this->actingAs($user)
            ->from(route('petty-cash.show', $account->id))
            ->post(route('petty-cash.transactions.store'), [
                'petty_cash_account_id' => $account->id,
                'transaction_date' => now()->toDateString(),
                'type' => 'DEBIT',
                'amount' => 250000,
                'description' => 'Top up kas kecil',
                'reference_no' => 'TOPUP-001',
            ])
            ->assertRedirect(route('petty-cash.show', $account->id));

        $account->refresh();

        $this->assertDatabaseHas('petty_cash_transactions', [
            'petty_cash_account_id' => $account->id,
            'user_id' => $user->id,
            'type' => 'DEBIT',
            'amount' => 250000,
            'status' => 'APPROVED',
        ]);
        $this->assertSame('1250000.00', $account->balance);
    }

    public function test_credit_transaction_cannot_exceed_account_balance(): void
    {
        $user = $this->financeUser();
        $account = PettyCashAccount::factory()->create(['balance' => 100000]);

        $this->actingAs($user)
            ->from(route('petty-cash.show', $account->id))
            ->post(route('petty-cash.transactions.store'), [
                'petty_cash_account_id' => $account->id,
                'transaction_date' => now()->toDateString(),
                'type' => 'CREDIT',
                'amount' => 250000,
                'description' => 'Pembelian ATK',
            ])
            ->assertRedirect(route('petty-cash.show', $account->id))
            ->assertSessionHasErrors(['amount']);

        $this->assertDatabaseCount('petty_cash_transactions', 0);
        $account->refresh();
        $this->assertSame('100000.00', $account->balance);
    }

    public function test_account_with_transactions_cannot_be_deleted(): void
    {
        $user = $this->financeUser();
        $account = PettyCashAccount::factory()->create();

        $this->actingAs($user)->post(route('petty-cash.transactions.store'), [
            'petty_cash_account_id' => $account->id,
            'transaction_date' => now()->toDateString(),
            'type' => 'DEBIT',
            'amount' => 100000,
            'description' => 'Saldo awal',
        ]);

        $this->actingAs($user)
            ->from(route('petty-cash.index'))
            ->delete(route('petty-cash.destroy', $account->id))
            ->assertRedirect(route('petty-cash.index'))
            ->assertSessionHas('error', 'Cannot delete account with existing transactions.');

        $this->assertDatabaseHas('petty_cash_accounts', [
            'id' => $account->id,
        ]);
    }
}
