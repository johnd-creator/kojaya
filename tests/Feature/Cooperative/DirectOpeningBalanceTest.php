<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\CooperativeOpeningBalanceWizardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DirectOpeningBalanceTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private CooperativeMember $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-01'));
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $this->operator = User::factory()->create(['organization_id' => $organization->id]);
        $this->operator->assignRole('Pengurus Koperasi');
        $this->member = CooperativeMember::factory()->active()->create(['organization_id' => $organization->id]);
        foreach (CooperativeOpeningBalanceWizardService::CATEGORIES as $category) {
            CooperativeContributionType::query()->create([
                'code' => $category, 'name' => $category, 'category' => $category,
                'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
            ]);
        }
    }

    private function input(): array
    {
        return [
            'mode' => 'DIRECT', 'cut_off_date' => '2026-09-30',
            'direct_amounts' => ['POKOK' => '200000.00', 'WAJIB' => '125000.50', 'SUKARELA' => '30000.00', 'KHUSUS' => '40000.00'],
            'source_type' => 'MANUAL_RECONCILIATION', 'source_reference' => 'Reconciled ledger', 'notes' => 'Saldo yang direkonsiliasi',
        ];
    }

    private function url(): string
    {
        return '/cooperative/members/'.$this->member->id.'/opening-balance';
    }

    public function test_direct_preview_draft_post_and_void_do_not_fabricate_monthly_history(): void
    {
        $this->actingAs($this->operator)->postJson($this->url().'/preview', $this->input())
            ->assertOk()->assertJsonPath('preview.total_amount', 395000.5)
            ->assertJsonPath('preview.months_count', 0)->assertJsonPath('preview.cut_off_date', '2026-09-30')
            ->assertJsonPath('preview.lines.1.calculation_method', 'DIRECT');
        $this->actingAs($this->operator)->post(route('cooperative.members.opening-balance.store', $this->member), $this->input())->assertRedirect();
        $batch = CooperativeMemberOpeningBalanceBatch::query()->firstOrFail();
        $this->assertTrue($batch->isDraft());
        $this->assertSame('DIRECT', $batch->metadata['mode']);
        $this->assertDatabaseCount('cooperative_ledger_entries', 0);
        $service = app(CooperativeOpeningBalanceWizardService::class);
        $service->post($batch, $this->operator);
        $credits = CooperativeLedgerEntry::query()->where('entry_type', 'OPENING_BALANCE')->get();
        $this->assertCount(4, $credits);
        $this->assertEquals(395000.5, $credits->sum('credit'));
        $this->assertEquals(0, $credits->sum('debit'));
        $this->assertEqualsCanonicalizing(['POKOK', 'WAJIB', 'SUKARELA', 'KHUSUS'], $credits->pluck('category_snapshot')->all());
        $this->assertDatabaseMissing('cooperative_ledger_entries', ['entry_type' => 'SAVING_PAYMENT']);
        $this->assertDatabaseCount('cooperative_dues_invoices', 0);
        $this->assertDatabaseCount('cooperative_payments', 0);
        $this->assertDatabaseCount('cooperative_receipts', 0);
        $service->void($batch->fresh(), $this->operator, 'Correction');
        $this->assertEquals(395000.5, CooperativeLedgerEntry::query()->where('entry_type', 'OPENING_BALANCE_REVERSAL')->sum('debit'));
        $this->assertSame(4, CooperativeLedgerEntry::query()->where('entry_type', 'OPENING_BALANCE')->count());
        $replacement = $service->createDraft($this->member->fresh(), $this->input(), $this->operator, $this->member->organization);
        $service->post($replacement, $this->operator);
        $this->assertEquals(395000.5, CooperativeLedgerEntry::query()->sum('credit') - CooperativeLedgerEntry::query()->sum('debit'));
    }

    public function test_duplicate_drafts_cannot_both_be_posted(): void
    {
        $service = app(CooperativeOpeningBalanceWizardService::class);
        $one = $service->createDraft($this->member, $this->input(), $this->operator, $this->member->organization);
        $two = $service->createDraft($this->member, $this->input(), $this->operator, $this->member->organization);
        $service->post($one, $this->operator);
        try {
            $service->post($two, $this->operator);
            $this->fail('Duplicate opening balance was accepted');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('POSTED', $exception->getMessage());
        }
        $this->assertDatabaseCount('cooperative_ledger_entries', 4);
    }

    public function test_invalid_direct_input_and_missing_category_fail_without_writes(): void
    {
        foreach ([['direct_amounts' => ['POKOK' => -1]], ['cut_off_date' => '2099-01-01'], ['direct_amounts' => ['POKOK' => 0, 'WAJIB' => 0, 'SUKARELA' => 0, 'KHUSUS' => 0]]] as $invalid) {
            $this->actingAs($this->operator)->postJson($this->url().'/preview', array_replace($this->input(), $invalid))->assertUnprocessable();
        }
        CooperativeContributionType::query()->where('category', 'WAJIB')->update(['is_active' => false]);
        $this->actingAs($this->operator)->postJson($this->url().'/preview', $this->input())->assertUnprocessable();
        $this->assertDatabaseCount('cooperative_member_opening_balance_batches', 0);
    }

    public function test_direct_mode_preserves_authorization_and_organization_scope(): void
    {
        $this->operator->syncRoles('Anggota');
        $this->actingAs($this->operator)->postJson($this->url().'/preview', $this->input())->assertForbidden();
        $this->operator->syncRoles('Admin Koperasi');
        $this->operator->update(['organization_id' => Organization::factory()->create()->id]);
        $this->actingAs($this->operator)->postJson($this->url().'/preview', $this->input())->assertNotFound();
        $this->assertDatabaseCount('cooperative_member_opening_balance_batches', 0);
    }
}
