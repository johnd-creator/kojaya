<?php

namespace Tests\Unit\Services\Cooperative;

use App\Services\Cooperative\PosReturnNumberGenerator;
use Carbon\Carbon;
use Tests\TestCase;

class PosReturnNumberGeneratorTest extends TestCase
{
    private PosReturnNumberGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new PosReturnNumberGenerator;
    }

    public function test_generate_matches_expected_format_and_prefix(): void
    {
        $returnNo = $this->generator->generate();

        $this->assertStringStartsWith('RET-', $returnNo);
        $this->assertSame(46, strlen($returnNo));
        $this->assertMatchesRegularExpression(
            '/^RET-\d{8}-\d{6}-[0-9A-HJKMNP-TV-Z]{26}$/',
            $returnNo,
            'Return number must match RET-YYYYMMDD-HHMMSS-<ULID> format.'
        );
    }

    public function test_generate_respects_explicit_timestamp(): void
    {
        $fixedTime = Carbon::parse('2026-05-16 14:30:45');
        $returnNo = $this->generator->generate($fixedTime);

        $this->assertStringStartsWith('RET-20260516-143045-', $returnNo);
    }

    public function test_generate_produces_collision_proof_identifiers_under_frozen_same_second(): void
    {
        Carbon::setTestNow('2026-09-09 12:00:00');

        $count = 2500;
        $generated = [];

        for ($i = 0; $i < $count; $i++) {
            $generated[] = $this->generator->generate();
        }

        $this->assertCount($count, $generated);
        $this->assertCount(
            $count,
            array_unique($generated),
            "Expected all {$count} identifiers generated at the same second to be 100% collision-free."
        );

        foreach ($generated as $id) {
            $this->assertStringStartsWith('RET-20260909-120000-', $id);
            $this->assertSame(46, strlen($id));
        }

        Carbon::setTestNow();
    }
}
