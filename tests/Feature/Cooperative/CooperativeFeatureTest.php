<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\User;
use App\Services\Cooperative\DuesGenerationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CooperativeFeatureTest extends TestCase
{
    use DatabaseMigrations;

    public function test_member_creation_always_uses_cooperative_head_office(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $branch = Organization::query()->create([
            'id' => fake()->uuid(),
            'code' => 'KOP-999',
            'name' => 'Cabang Test',
            'level' => 'L1',
            'type' => 'BRANCH',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cooperative.members.store'), [
            'organization_id' => $branch->id,
            'name' => 'Anggota Test',
            'email' => 'anggota@test.local',
            'phone' => '08123',
            'joined_at' => '2026-05-01',
            'status' => 'ACTIVE',
        ])->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()->where('email', 'anggota@test.local')->firstOrFail();

        $this->assertSame('KOP-001', $member->organization->code);
        $this->assertNotSame($branch->id, $member->organization_id);
    }

    public function test_member_can_be_activated_and_resigned(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'PENDING', 'joined_at' => null]);

        $this->actingAs($user)->post(route('cooperative.members.activate', $member))->assertRedirect();
        $this->assertSame('ACTIVE', $member->refresh()->status);

        $this->actingAs($user)->post(route('cooperative.members.resign', $member))->assertRedirect();
        $this->assertSame('RESIGNED', $member->refresh()->status);
        $this->assertNotNull($member->resigned_at);
    }

    public function test_dues_generation_is_idempotent(): void
    {
        $member = $this->member(['status' => 'ACTIVE']);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $service = app(DuesGenerationService::class);

        $this->assertSame(1, $service->generateForPeriod('2026-05'));
        $this->assertSame(0, $service->generateForPeriod('2026-05'));
        $this->assertDatabaseCount('cooperative_dues_invoices', 1);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'period' => '2026-05',
            'status' => 'UNPAID',
        ]);
    }

    public function test_approved_payment_updates_invoice_and_ledger(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);

        $this->actingAs($user)->post(route('cooperative.payments.store'), [
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-01',
            'status' => 'APPROVED',
        ])->assertRedirect();

        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertSame('50000.00', $invoice->paid_amount);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SAVING_PAYMENT',
            'credit' => 50000,
        ]);
    }

    public function test_pos_transaction_reduces_stock_and_requires_active_member_for_credit(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $member = $this->member(['status' => 'PENDING']);
        $product = $this->product(['stock' => 5, 'sale_price' => 10000]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'payment_method' => 'MEMBER_CREDIT',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('cooperative_member_id');

        $member->update(['status' => 'ACTIVE']);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'payment_method' => 'MEMBER_CREDIT',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertSame(3, $product->refresh()->stock);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'POS_MEMBER_CREDIT',
            'debit' => 20000,
        ]);
    }

    public function test_api_requires_sanctum_and_cooperative_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->getJson('/api/v1/members')->assertUnauthorized();

        $plainUser = User::factory()->create();
        Sanctum::actingAs($plainUser);
        $this->getJson('/api/v1/members')->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/members')->assertOk();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(array $attributes = []): CooperativeMember
    {
        $organization = Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => fake()->uuid(),
                'name' => 'Koperasi Utama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'is_active' => true,
            ],
        );

        return CooperativeMember::query()->create([
            'organization_id' => $organization->id,
            'member_no' => 'KOP-2026-'.fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'status' => 'ACTIVE',
            'joined_at' => '2026-05-01',
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): PosProduct
    {
        $category = PosCategory::query()->create([
            'name' => 'Sembako',
            'slug' => 'sembako-'.fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ]);

        return PosProduct::query()->create([
            'pos_category_id' => $category->id,
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->word(),
            'sale_price' => 10000,
            'stock' => 10,
            'minimum_stock' => 1,
            'is_active' => true,
            ...$attributes,
        ]);
    }
}
