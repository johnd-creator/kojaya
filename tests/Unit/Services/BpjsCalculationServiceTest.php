<?php

namespace Tests\Unit\Services;

use App\Services\BpjsCalculationService;
use Tests\TestCase;

class BpjsCalculationServiceTest extends TestCase
{
    private BpjsCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BpjsCalculationService;
    }

    public function test_bpjs_kesehatan_capped_at_max_salary(): void
    {
        $low = $this->service->calculate(5_000_000);
        $high = $this->service->calculate(20_000_000);

        $this->assertEquals(50_000, $low['bpjs_kesehatan']['employee']); // 1% of 5,000,000
        $this->assertEquals(200_000, $low['bpjs_kesehatan']['employer']); // 4% of 5,000,000

        // Capped at 12,000,000
        $this->assertEquals(120_000, $high['bpjs_kesehatan']['employee']); // 1% of 12,000,000
        $this->assertEquals(480_000, $high['bpjs_kesehatan']['employer']); // 4% of 12,000,000
    }

    public function test_jht_and_jp_capped_at_max_salary(): void
    {
        $low = $this->service->calculate(5_000_000);
        $high = $this->service->calculate(20_000_000);

        // JHT employee 2%, employer 3.7%
        $this->assertEquals(100_000, $low['bpjs_jht']['employee']);
        $this->assertEquals(185_000, $low['bpjs_jht']['employer']);

        $this->assertEquals(240_000, $high['bpjs_jht']['employee']); // 2% of 12,000,000
        $this->assertEquals(444_000, $high['bpjs_jht']['employer']); // 3.7% of 12,000,000

        // JP employee 1%, employer 1%
        $this->assertEquals(50_000, $low['bpjs_jp']['employee']);
        $this->assertEquals(50_000, $low['bpjs_jp']['employer']);

        $this->assertEquals(120_000, $high['bpjs_jp']['employee']);
        $this->assertEquals(120_000, $high['bpjs_jp']['employer']);
    }

    public function test_jkk_jkm_use_separate_caps_and_employer_only(): void
    {
        $low = $this->service->calculate(5_000_000);
        $high = $this->service->calculate(20_000_000);

        // JKK 0.89% employer only, capped at 9,000,000
        $this->assertEqualsWithDelta(44_500, $low['bpjs_jkk']['amount'], 0.01);
        $this->assertEqualsWithDelta(80_100, $high['bpjs_jkk']['amount'], 0.01); // 0.89% of 9,000,000

        // JKM 0.3% employer only, capped at 9,000,000
        $this->assertEqualsWithDelta(15_000, $low['bpjs_jkm']['amount'], 0.01);
        $this->assertEqualsWithDelta(27_000, $high['bpjs_jkm']['amount'], 0.01); // 0.3% of 9,000,000
    }

    public function test_totals_consistency(): void
    {
        $result = $this->service->calculate(10_000_000);

        $sumEmployee = $result['bpjs_kesehatan']['employee']
                     + $result['bpjs_jht']['employee']
                     + $result['bpjs_jp']['employee'];
        $this->assertEquals($sumEmployee, $result['total_employee_deduction']);

        $sumEmployer = $result['bpjs_kesehatan']['employer']
                     + $result['bpjs_jht']['employer']
                     + $result['bpjs_jp']['employer']
                     + $result['bpjs_jkk']['amount']
                     + $result['bpjs_jkm']['amount'];
        $this->assertEquals($sumEmployer, $result['total_employer_contribution']);
    }
}
