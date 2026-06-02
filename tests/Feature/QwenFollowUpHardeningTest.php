<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Enums\CooperativeShuPeriodStatus;
use App\Enums\VendorStatus;
use App\Models\CooperativeShuPeriod;
use App\Models\Vendor;
use App\Services\Cooperative\AnnualShuDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QwenFollowUpHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_statuses_are_cast_to_formal_enums(): void
    {
        $vendor = Vendor::factory()->create([
            'status' => VendorStatus::Suspended,
        ]);

        $period = CooperativeShuPeriod::query()->create([
            'year' => 2026,
            'status' => CooperativeShuPeriodStatus::Revision,
        ]);

        $this->assertSame(VendorStatus::Suspended, $vendor->refresh()->status);
        $this->assertSame(CooperativeShuPeriodStatus::Revision, $period->refresh()->status);
    }

    public function test_api_error_codes_include_business_specific_failures(): void
    {
        $this->assertSame('PERIOD_LOCKED', ApiErrorCode::PeriodLocked->value);
        $this->assertSame('INSUFFICIENT_BALANCE', ApiErrorCode::InsufficientBalance->value);
    }

    public function test_reclosing_revision_shu_period_uses_closed_revised_status(): void
    {
        CooperativeShuPeriod::query()->create([
            'year' => 2027,
            'status' => CooperativeShuPeriodStatus::Revision,
        ]);

        $period = app(AnnualShuDistributionService::class)->close(2027);

        $this->assertSame(CooperativeShuPeriodStatus::ClosedRevised, $period->status);
        $this->assertDatabaseHas('cooperative_shu_periods', [
            'year' => 2027,
            'status' => CooperativeShuPeriodStatus::ClosedRevised->value,
        ]);
    }

    public function test_architecture_documentation_counts_match_codebase(): void
    {
        $architecture = file_get_contents(base_path('docs/architecture.md'));

        $this->assertStringContainsString('114 models', $architecture);
        $this->assertStringContainsString('52 with UUID', $architecture);
        $this->assertStringContainsString('53 model factories', $architecture);
    }
}
