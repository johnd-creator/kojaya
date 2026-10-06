<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Services\Cooperative\DuesGenerationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuesOperationalCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_day_one_generation_and_late_member_catch_up_are_idempotent(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 03:00:00'));
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB', 'name' => 'Wajib', 'category' => 'WAJIB',
            'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
        ]);
        $member = CooperativeMember::factory()->active()->create(['joined_at' => '2026-09-01', 'tanggal_aktif' => '2026-09-01']);
        $future = CooperativeMember::factory()->active()->create(['joined_at' => '2026-11-01', 'tanggal_aktif' => '2026-11-01']);
        $inactive = CooperativeMember::factory()->create(['status' => 'PENDING']);
        $this->artisan('cooperative:generate-monthly-dues')->assertSuccessful();
        $this->assertDatabaseCount('cooperative_dues_invoices', 1);
        $original = CooperativeDuesInvoice::query()->firstOrFail();
        $this->assertSame('2026-10-10', $original->due_date->toDateString());
        $this->artisan('cooperative:generate-monthly-dues')->assertSuccessful();
        $this->assertDatabaseCount('cooperative_dues_invoices', 1);
        $this->travelTo(CarbonImmutable::parse('2026-10-28 10:00:00'));
        $late = CooperativeMember::factory()->active()->create(['joined_at' => '2026-10-28', 'tanggal_aktif' => '2026-10-28']);
        $this->artisan('cooperative:generate-monthly-dues', ['--member' => $late->id])->assertSuccessful();
        $this->artisan('cooperative:generate-monthly-dues', ['--member' => $late->id])->assertSuccessful();
        $this->assertDatabaseCount('cooperative_dues_invoices', 2);
        $this->assertDatabaseHas('cooperative_dues_invoices', ['cooperative_member_id' => $late->id, 'period' => '2026-10']);
        $this->assertDatabaseMissing('cooperative_dues_invoices', ['cooperative_member_id' => $future->id]);
        $this->assertDatabaseMissing('cooperative_dues_invoices', ['cooperative_member_id' => $inactive->id]);
        $this->assertDatabaseCount('cooperative_payments', 0);
        $this->assertDatabaseCount('cooperative_ledger_entries', 0);
        $this->artisan('cooperative:generate-monthly-dues', ['--member' => $late->id, '--period' => '2026-09'])->assertFailed();
        $this->assertSame(0, app(DuesGenerationService::class)->generateForPeriod('2026-10'));
        $this->assertSame($original->id, $member->invoices()->first()->id);
    }

    public function test_bank_collection_window_is_inclusive_across_month_lengths_and_years(): void
    {
        $service = app(DuesGenerationService::class);
        foreach ([['2026-10', '2026-10-25', '2026-11-07'], ['2026-02', '2026-02-25', '2026-03-07'], ['2028-02', '2028-02-25', '2028-03-07'], ['2026-12', '2026-12-25', '2027-01-07']] as [$period, $openingDate, $closingDate]) {
            $month = CarbonImmutable::createFromFormat('!Y-m', $period);
            $opens = CarbonImmutable::parse($openingDate)->startOfDay();
            $closes = CarbonImmutable::parse($closingDate)->endOfDay();
            $this->assertFalse($service->isAutodebitWindowOpen($period, $opens->subSecond()));
            $this->assertTrue($service->isAutodebitWindowOpen($period, $opens));
            $this->assertTrue($service->isAutodebitWindowOpen($period, $month->endOfMonth()));
            $this->assertTrue($service->isAutodebitWindowOpen($period, $month->addMonth()->startOfMonth()));
            $this->assertTrue($service->isAutodebitWindowOpen($period, $closes));
            $this->assertFalse($service->isAutodebitWindowOpen($period, $closes->addSecond()));
        }
        $this->assertDatabaseCount('cooperative_payments', 0);
    }

    public function test_february_generation_on_a_longer_month_day_does_not_overflow(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-31'));
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB', 'name' => 'Wajib', 'category' => 'WAJIB',
            'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
        ]);
        CooperativeMember::factory()->active()->create(['joined_at' => '2026-01-01', 'tanggal_aktif' => '2026-01-01']);
        $this->assertSame(1, app(DuesGenerationService::class)->generateForPeriod('2026-02'));
        $this->assertSame('2026-02-10', CooperativeDuesInvoice::query()->firstOrFail()->due_date->toDateString());
        $this->assertSame(0, app(DuesGenerationService::class)->generateForPeriod('2026-02'));
    }

    public function test_scheduler_keeps_generation_on_day_one_without_bank_execution(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'cooperative:generate-monthly-dues'));
        $this->assertNotNull($event);
        $this->assertSame('0 3 1 * *', $event->expression);
        $this->assertDatabaseCount('cooperative_payments', 0);
    }
}
