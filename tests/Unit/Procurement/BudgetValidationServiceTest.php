<?php

namespace Tests\Unit\Procurement;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Organization;
use App\Services\Procurement\BudgetValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_availability_passes_when_enough_budget(): void
    {
        $org = Organization::factory()->create();
        $budget = Budget::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'year' => date('Y'),
            'period' => 'ANNUAL',
            'status' => 'APPROVED',
        ]);
        BudgetLine::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'budget_id' => $budget->id,
            'gl_account' => '6101',
            'allocated_amount' => 1000000,
            'committed_amount' => 200000,
            'realized_amount' => 0,
        ]);

        $svc = new BudgetValidationService;
        $result = $svc->checkAvailability([
            ['gl_account' => '6101', 'amount' => 100000],
        ], $org->id);

        $this->assertTrue($result['ok']);
        $this->assertEquals(100000, $result['total_requested']);
    }

    public function test_commit_updates_committed_amount_and_blocks_when_insufficient(): void
    {
        $org = Organization::factory()->create();
        $budget = Budget::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'year' => date('Y'),
            'period' => 'ANNUAL',
            'status' => 'APPROVED',
        ]);
        $line = BudgetLine::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'budget_id' => $budget->id,
            'gl_account' => '6201',
            'allocated_amount' => 300000,
            'committed_amount' => 0,
            'realized_amount' => 0,
        ]);

        $svc = new BudgetValidationService;
        $okRes = $svc->commit([
            ['gl_account' => '6201', 'amount' => 100000],
        ], $org->id);
        $this->assertTrue($okRes['ok']);
        $this->assertEquals(100000.0, $line->fresh()->committed_amount);

        $failRes = $svc->commit([
            ['gl_account' => '6201', 'amount' => 300000],
        ], $org->id);
        $this->assertFalse($failRes['ok']);
        $this->assertEquals('insufficient_budget', $failRes['error']);
    }
}
