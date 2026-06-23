<?php

namespace Tests\Feature\Cooperative;

use App\Enums\Cooperative\OpeningBalanceBatchStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpeningBalanceWizardHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    private User $adminKoperasi;

    private CooperativeMember $member;

    private Organization $organization;

    private CooperativeContributionType $pokok;

    private CooperativeContributionType $wajib;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->pengurus = User::factory()->create();
        $this->pengurus->assignRole('Pengurus Koperasi');

        $this->adminKoperasi = User::factory()->create();
        $this->adminKoperasi->assignRole('Admin Koperasi');

        $this->organization = Organization::factory()->create();

        $this->member = CooperativeMember::factory()->create([
            'organization_id' => $this->organization->id,
            'tanggal_aktif' => '2016-06-15',
        ]);

        $this->pokok = CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 200000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);

        $this->wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
    }

    public function test_pengurus_can_open_wizard_page(): void
    {
        $response = $this->actingAs($this->pengurus)
            ->get("/cooperative/members/{$this->member->id}/opening-balance");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Cooperative/Members/OpeningBalance/Wizard')
            ->has('member')
            ->has('contribution_types', 2)
            ->has('source_types', 4)
            ->has('history'));
    }

    public function test_anggota_role_is_forbidden(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $this->actingAs($anggota)
            ->get("/cooperative/members/{$this->member->id}/opening-balance")
            ->assertForbidden();
    }

    public function test_preview_returns_calculated_payload(): void
    {
        $response = $this->actingAs($this->pengurus)
            ->postJson("/cooperative/members/{$this->member->id}/opening-balance/preview", [
                'calculation_start_period' => '2016-06-01',
                'calculation_end_period' => '2026-05-31',
                'contribution_types' => [$this->pokok->id, $this->wajib->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('preview.months_count', 120)
            ->assertJsonPath('preview.total_amount', 6200000)
            ->assertJsonPath('preview.has_conflicts', false)
            ->assertJsonPath('preview.conflicts', []);
    }

    public function test_preview_reports_conflicts_for_existing_saving_payments(): void
    {
        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'SAVING_PAYMENT',
            'cooperative_contribution_type_id' => $this->wajib->id,
            'category_snapshot' => 'WAJIB',
            'source_type' => \App\Models\CooperativePayment::class,
            'source_id' => 999,
            'period' => '2024-03',
            'credit' => 50000,
            'debit' => 0,
            'posted_at' => '2024-03-15',
            'description' => 'Pembayaran wajib manual.',
        ]);

        $response = $this->actingAs($this->pengurus)
            ->postJson("/cooperative/members/{$this->member->id}/opening-balance/preview", [
                'calculation_start_period' => '2016-06-01',
                'calculation_end_period' => '2026-05-31',
                'contribution_types' => [$this->pokok->id, $this->wajib->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('preview.has_conflicts', true)
            ->assertJsonStructure([
                'preview' => [
                    'conflicts' => [
                        '*' => ['category', 'entry_type', 'period', 'message', 'overlaps_calculation_period'],
                    ],
                ],
            ]);

        $conflicts = $response->json('preview.conflicts');
        $this->assertNotEmpty($conflicts);
        $this->assertSame('WAJIB', $conflicts[0]['category']);
        $this->assertTrue($conflicts[0]['overlaps_calculation_period']);
    }

    public function test_preview_validates_required_period(): void
    {
        $this->actingAs($this->pengurus)
            ->postJson("/cooperative/members/{$this->member->id}/opening-balance/preview", [
                'contribution_types' => [$this->pokok->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['calculation_start_period', 'calculation_end_period']);
    }

    public function test_store_persists_draft_batch(): void
    {
        $this->actingAs($this->pengurus)
            ->post("/cooperative/members/{$this->member->id}/opening-balance/draft", [
                'calculation_start_period' => '2016-06-01',
                'calculation_end_period' => '2026-05-31',
                'contribution_types' => [$this->pokok->id, $this->wajib->id],
                'source_type' => 'BOARD_DECISION',
                'source_reference' => 'REF-DRAFT-001',
                'notes' => 'Draft awal migrasi.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cooperative_member_opening_balance_batches', [
            'cooperative_member_id' => $this->member->id,
            'status' => 'DRAFT',
            'source_type' => 'BOARD_DECISION',
            'source_reference' => 'REF-DRAFT-001',
        ]);
    }

    public function test_admin_koperasi_cannot_post_to_ledger(): void
    {
        $batch = CooperativeMemberOpeningBalanceBatch::factory()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'status' => OpeningBalanceBatchStatus::Draft,
        ]);

        $this->actingAs($this->adminKoperasi)
            ->post("/cooperative/opening-balances/{$batch->id}/post", [
                'confirmation_notes' => 'Posting oleh admin koperasi',
            ])
            ->assertForbidden();
    }

    public function test_pengurus_can_post_draft_and_create_ledger_entries(): void
    {
        $batch = CooperativeMemberOpeningBalanceBatch::factory()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'status' => OpeningBalanceBatchStatus::Draft,
            'months_count' => 12,
            'total_amount' => 800000,
        ]);

        // Tambahkan dua line agar unik per anggota.
        \App\Models\CooperativeMemberOpeningBalanceLine::factory()->pokok(200000)->create([
            'opening_balance_batch_id' => $batch->id,
            'cooperative_contribution_type_id' => $this->pokok->id,
        ]);
        \App\Models\CooperativeMemberOpeningBalanceLine::factory()->create([
            'opening_balance_batch_id' => $batch->id,
            'cooperative_contribution_type_id' => $this->wajib->id,
            'category_snapshot' => 'WAJIB',
            'period_start' => '2025-06-01',
            'period_end' => '2026-05-31',
            'months_count' => 12,
            'unit_amount' => 50000,
            'total_amount' => 600000,
            'calculation_method' => 'MONTHLY',
        ]);

        $this->actingAs($this->pengurus)
            ->post("/cooperative/opening-balances/{$batch->id}/post", [
                'confirmation_notes' => 'Posting awal migrasi.',
            ])
            ->assertRedirect();

        $this->assertSame(OpeningBalanceBatchStatus::Posted, $batch->fresh()->status);

        $this->assertDatabaseCount('cooperative_ledger_entries', 2);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $this->member->id,
            'entry_type' => 'OPENING_BALANCE',
            'category_snapshot' => 'POKOK',
            'credit' => '200000.00',
        ]);
    }

    public function test_void_creates_reversal_entries(): void
    {
        $batch = CooperativeMemberOpeningBalanceBatch::factory()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'status' => OpeningBalanceBatchStatus::Posted,
            'posted_by' => $this->pengurus->id,
            'posted_at' => now()->subDay(),
            'total_amount' => 200000,
        ]);
        $line = \App\Models\CooperativeMemberOpeningBalanceLine::factory()->pokok(200000)->create([
            'opening_balance_batch_id' => $batch->id,
            'cooperative_contribution_type_id' => $this->pokok->id,
        ]);

        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'ledger_scope' => 'SAVINGS',
            'entry_type' => 'OPENING_BALANCE',
            'cooperative_contribution_type_id' => $this->pokok->id,
            'category_snapshot' => 'POKOK',
            'source_type' => \App\Models\CooperativeMemberOpeningBalanceLine::class,
            'source_id' => $line->id,
            'debit' => 0,
            'credit' => 200000,
            'posted_at' => now()->subDay()->toDateString(),
            'description' => 'Saldo awal POKOK',
        ]);

        $this->actingAs($this->pengurus)
            ->from("/cooperative/members/{$this->member->id}/opening-balance")
            ->post("/cooperative/opening-balances/{$batch->id}/void", [
                'reason' => 'Salah nominal migrasi, akan diulang dengan batch baru.',
            ])
            ->assertRedirect();

        $batch->refresh();
        $this->assertSame(OpeningBalanceBatchStatus::Voided, $batch->status);
        $this->assertSame('Salah nominal migrasi, akan diulang dengan batch baru.', $batch->void_reason);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $this->member->id,
            'entry_type' => 'OPENING_BALANCE_REVERSAL',
            'category_snapshot' => 'POKOK',
            'debit' => '200000.00',
            'credit' => '0.00',
        ]);
    }

    public function test_void_requires_minimum_reason_length(): void
    {
        $batch = CooperativeMemberOpeningBalanceBatch::factory()->create([
            'cooperative_member_id' => $this->member->id,
            'organization_id' => $this->organization->id,
            'status' => OpeningBalanceBatchStatus::Posted,
            'posted_at' => now()->subDay(),
        ]);

        $this->actingAs($this->pengurus)
            ->post("/cooperative/opening-balances/{$batch->id}/void", [
                'reason' => 'ok',
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_draft_requires_override_reason_when_unit_amount_differs_from_default(): void
    {
        $response = $this->actingAs($this->pengurus)
            ->post("/cooperative/members/{$this->member->id}/opening-balance/draft", [
                'calculation_start_period' => '2026-01-01',
                'calculation_end_period' => '2026-06-30',
                'contribution_types' => [$this->wajib->id],
                'overrides' => [
                    $this->wajib->id => [
                        'unit_amount' => 75000,
                        // reason kosong saat override berbeda dari default 50000.
                    ],
                ],
                'source_type' => 'BOARD_DECISION',
            ]);

        $response->assertSessionHasErrors('overrides.'.$this->wajib->id.'.reason');
    }

    public function test_draft_accepts_override_when_reason_provided(): void
    {
        $response = $this->actingAs($this->pengurus)
            ->post("/cooperative/members/{$this->member->id}/opening-balance/draft", [
                'calculation_start_period' => '2026-01-01',
                'calculation_end_period' => '2026-06-30',
                'contribution_types' => [$this->wajib->id],
                'overrides' => [
                    $this->wajib->id => [
                        'unit_amount' => 75000,
                        'reason' => 'Tarif 2024 naik dari default.',
                    ],
                ],
                'source_type' => 'BOARD_DECISION',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cooperative_member_opening_balance_batches', [
            'cooperative_member_id' => $this->member->id,
            'status' => 'DRAFT',
            'total_amount' => 450000, // 6 bulan x 75.000
        ]);
    }

    public function test_draft_accepts_override_amount_equal_to_default_without_reason(): void
    {
        $response = $this->actingAs($this->pengurus)
            ->post("/cooperative/members/{$this->member->id}/opening-balance/draft", [
                'calculation_start_period' => '2026-01-01',
                'calculation_end_period' => '2026-06-30',
                'contribution_types' => [$this->wajib->id],
                'overrides' => [
                    $this->wajib->id => [
                        'unit_amount' => 50000,
                        // reason tidak wajib karena nominal sama dengan default.
                    ],
                ],
                'source_type' => 'BOARD_DECISION',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cooperative_member_opening_balance_batches', [
            'cooperative_member_id' => $this->member->id,
            'status' => 'DRAFT',
            'total_amount' => 300000, // 6 bulan x 50.000
        ]);
    }

    public function test_draft_period_normalization_to_first_and_last_day_of_month(): void
    {
        $response = $this->actingAs($this->pengurus)
            ->post("/cooperative/members/{$this->member->id}/opening-balance/draft", [
                'calculation_start_period' => '2016-06-15',
                'calculation_end_period' => '2026-05-23',
                'contribution_types' => [$this->pokok->id],
                'source_type' => 'BOARD_DECISION',
            ]);

        $response->assertRedirect();
        $batch = CooperativeMemberOpeningBalanceBatch::query()
            ->where('cooperative_member_id', $this->member->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('2016-06-01', $batch->calculation_start_period?->toDateString());
        $this->assertSame('2026-05-31', $batch->calculation_end_period?->toDateString());

        $line = $batch->lines()->first();
        $this->assertNotNull($line);
        // ONCE contribution (POKOK) tidak punya period_start/period_end
        // karena dihitung sekali bayar; cukup verifikasi batch metadata.
        $this->assertSame('2016-06-01', $batch->calculation_start_period?->toDateString());
    }
}
