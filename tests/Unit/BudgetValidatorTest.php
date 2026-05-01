<?php

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Organization;
use App\Services\BudgetValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_validator_allows_within_limit_and_blocks_over_budget(): void
    {
        $org = Organization::factory()->create();
        $budget = Budget::create([
            'organization_id' => $org->id,
            'year' => date('Y'),
            'period' => 'ANNUAL',
            'status' => 'ACTIVE',
        ]);

        BudgetLine::create([
            'budget_id' => $budget->id,
            'gl_account' => '6100-OPX',
            'category' => 'OPEX',
            'allocated_amount' => 10000000,
            'committed_amount' => 2000000,
            'realized_amount' => 3000000,
        ]);

        $validator = new BudgetValidator;
        $ok = $validator->checkAvailability($org->id, '6100-OPX', 4000000);
        $this->assertTrue($ok['ok']);
        $this->assertEquals(5000000.0, (float) $ok['available']);

        $blocked = $validator->checkAvailability($org->id, '6100-OPX', 6000000);
        $this->assertFalse($blocked['ok']);
        $this->assertEquals(5000000.0, (float) $blocked['available']);
        $this->assertEquals('Insufficient budget', $blocked['reason']);
    }
}
