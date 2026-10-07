<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePeriodLock;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\DuesGenerationService;
use App\Services\Cooperative\MemberValidationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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
        $this->assertSame('2026-11-10', $original->due_date->toDateString());
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
        $this->assertSame('2026-03-10', CooperativeDuesInvoice::query()->firstOrFail()->due_date->toDateString());
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

    public function test_monthly_generator_does_not_create_once_pokok_invoice(): void
    {
        CooperativeContributionType::query()->create([
            'code' => 'POKOK', 'name' => 'Pokok', 'category' => 'POKOK',
            'default_amount' => 100000, 'frequency' => 'ONCE', 'is_active' => true,
        ]);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB', 'name' => 'Wajib', 'category' => 'WAJIB',
            'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
        ]);
        $member = CooperativeMember::factory()->active()->create([
            'joined_at' => '2026-10-01',
            'tanggal_aktif' => '2026-10-01',
        ]);

        $created = app(DuesGenerationService::class)->generateForPeriod('2026-10');

        $this->assertSame(1, $created);
        $this->assertDatabaseCount('cooperative_dues_invoices', 1);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'period' => '2026-10',
        ]);
        $invoice = CooperativeDuesInvoice::query()->firstOrFail();
        $this->assertSame('WAJIB', $invoice->contributionType->code);
    }

    public function test_period_lock_blocks_monthly_dues_generation_and_catch_up(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00:00'));
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB', 'name' => 'Wajib', 'category' => 'WAJIB',
            'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
        ]);
        $member = CooperativeMember::factory()->active()->create([
            'joined_at' => '2026-10-01',
            'tanggal_aktif' => '2026-10-01',
        ]);

        CooperativePeriodLock::query()->create([
            'period' => '2026-10',
            'module' => 'COOPERATIVE',
            'status' => 'LOCKED',
            'locked_at' => now(),
            'locked_by' => 1,
        ]);

        $this->expectException(ValidationException::class);
        app(DuesGenerationService::class)->catchUpCurrentPeriod($member);
    }

    public function test_existing_previous_period_invoice_does_not_block_current_period_monthly_invoice(): void
    {
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB', 'name' => 'Wajib', 'category' => 'WAJIB',
            'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
        ]);
        $member = CooperativeMember::factory()->active()->create([
            'joined_at' => '2026-09-01',
            'tanggal_aktif' => '2026-09-01',
        ]);

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-09',
            'amount' => 50000,
            'paid_amount' => 50000,
            'due_date' => '2026-10-10',
            'status' => 'PAID',
        ]);

        $this->assertSame(1, app(DuesGenerationService::class)->generateForPeriod('2026-10'));
        $this->assertDatabaseCount('cooperative_dues_invoices', 2);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'period' => '2026-10',
            'status' => 'UNPAID',
        ]);
    }

    public function test_opening_balance_pokok_does_not_cause_catch_up_to_fabricate_pokok_invoice(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00:00'));
        CooperativeContributionType::query()->create([
            'code' => 'POKOK', 'name' => 'Pokok', 'category' => 'POKOK',
            'default_amount' => 100000, 'frequency' => 'ONCE', 'is_active' => true,
        ]);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB', 'name' => 'Wajib', 'category' => 'WAJIB',
            'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
        ]);
        $member = CooperativeMember::factory()->active()->create([
            'joined_at' => '2026-10-01',
            'tanggal_aktif' => '2026-10-01',
        ]);

        $created = app(DuesGenerationService::class)->catchUpCurrentPeriod($member);

        $this->assertSame(1, $created);
        $this->assertDatabaseCount('cooperative_dues_invoices', 1);
        $invoice = CooperativeDuesInvoice::query()->firstOrFail();
        $this->assertSame('WAJIB', $invoice->contributionType->code);
    }

    public function test_final_approval_after_day_one_produces_exactly_one_current_wajib_without_pokok(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-10-15 14:00:00'));
        CooperativeContributionType::query()->create([
            'code' => 'POKOK', 'name' => 'Pokok', 'category' => 'POKOK',
            'default_amount' => 100000, 'frequency' => 'ONCE', 'is_active' => true,
        ]);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB', 'name' => 'Wajib', 'category' => 'WAJIB',
            'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
        ]);

        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('Admin Koperasi');
        $pengurus = User::factory()->create(['organization_id' => $organization->id]);
        $pengurus->assignRole('Pengurus Koperasi');

        $member = CooperativeMember::factory()->pending()->create([
            'organization_id' => $organization->id,
            'joined_at' => '2026-10-10',
            'tanggal_aktif' => '2026-10-10',
        ]);

        $validationService = app(MemberValidationService::class);
        $validationService->verifyByAdmin($member, $admin);
        $validationService->approveFinal($member->refresh(), $pengurus, 'Approved final');

        $this->assertSame('ACTIVE', $member->refresh()->status);
        $this->assertDatabaseCount('cooperative_dues_invoices', 1);
        $invoice = CooperativeDuesInvoice::query()->firstOrFail();
        $this->assertSame($member->id, $invoice->cooperative_member_id);
        $this->assertSame('2026-10', $invoice->period);
        $this->assertSame('WAJIB', $invoice->contributionType->code);

        // Repeated catch-up or generation is idempotent
        $this->assertSame(0, app(DuesGenerationService::class)->catchUpCurrentPeriod($member));
        $this->assertDatabaseCount('cooperative_dues_invoices', 1);
    }

    public function test_activation_catch_up_creates_current_wajib_and_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-10-20 09:00:00'));
        CooperativeContributionType::query()->create([
            'code' => 'POKOK', 'name' => 'Pokok', 'category' => 'POKOK',
            'default_amount' => 100000, 'frequency' => 'ONCE', 'is_active' => true,
        ]);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB', 'name' => 'Wajib', 'category' => 'WAJIB',
            'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
        ]);

        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'INACTIVE',
            'validation_status' => 'INACTIVE',
            'joined_at' => '2026-10-15',
            'tanggal_aktif' => '2026-10-15',
        ]);

        $this->actingAs($user)
            ->post(route('cooperative.members.activate', $member))
            ->assertRedirect();

        $this->assertSame('ACTIVE', $member->refresh()->status);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'period' => '2026-10',
            'status' => 'UNPAID',
        ]);

        $initialCount = CooperativeDuesInvoice::query()->count();
        $this->assertSame(0, app(DuesGenerationService::class)->catchUpCurrentPeriod($member));
        $this->assertSame($initialCount, CooperativeDuesInvoice::query()->count());
    }

    public function test_monthly_dues_due_date_and_collection_window_align_with_operational_cycle(): void
    {
        $service = app(DuesGenerationService::class);

        // Required due date mappings
        $this->assertSame('2026-10-10', $service->dueDateForPeriod('2026-09')->toDateString());
        $this->assertSame('2026-11-10', $service->dueDateForPeriod('2026-10')->toDateString());
        $this->assertSame('2027-01-10', $service->dueDateForPeriod('2026-12')->toDateString());
        $this->assertSame('2028-03-10', $service->dueDateForPeriod('2028-02')->toDateString());

        // Month-end execution date must not change the result
        foreach (['2026-01-31', '2026-03-31', '2026-10-31', '2026-12-31'] as $mockDate) {
            $this->travelTo(CarbonImmutable::parse($mockDate));
            $this->assertSame('2026-11-10', $service->dueDateForPeriod('2026-10')->toDateString());
            $this->assertSame('2027-01-10', $service->dueDateForPeriod('2026-12')->toDateString());
            $this->assertSame('2028-03-10', $service->dueDateForPeriod('2028-02')->toDateString());
        }

        // Collection windows remain intact
        $octWindow = $service->autodebitWindow('2026-10');
        $this->assertSame('2026-10-25 00:00:00', $octWindow['opens_at']->toDateTimeString());
        $this->assertSame('2026-11-07 23:59:59', $octWindow['closes_at']->toDateTimeString());

        $decWindow = $service->autodebitWindow('2026-12');
        $this->assertSame('2026-12-25 00:00:00', $decWindow['opens_at']->toDateTimeString());
        $this->assertSame('2027-01-07 23:59:59', $decWindow['closes_at']->toDateTimeString());

        // Controller monthlyDuesInfo and persisted invoice use the SAME domain result
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $organization = app(\App\Services\Cooperative\CooperativeHeadOfficeResolver::class)->resolve();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');

        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB', 'name' => 'Simpanan Wajib', 'category' => 'WAJIB',
            'default_amount' => 50000, 'frequency' => 'MONTHLY', 'is_active' => true,
        ]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'joined_at' => '2026-10-01',
            'tanggal_aktif' => '2026-10-01',
        ]);

        $service->generateForPeriod('2026-10');
        $persistedInvoice = CooperativeDuesInvoice::query()
            ->where('cooperative_member_id', $member->id)
            ->where('period', '2026-10')
            ->firstOrFail();

        $expectedDueDate = $service->dueDateForPeriod('2026-10')->toDateString();
        $this->assertSame($expectedDueDate, $persistedInvoice->due_date->toDateString());
        $this->assertSame('2026-11-10', $persistedInvoice->due_date->toDateString());

        $response = $this->actingAs($user)->get(route('cooperative.dues.index', ['period' => '2026-10']));
        $response->assertOk();
        $monthlyDuesInfo = $response->viewData('page')['props']['monthlyDuesInfo'];
        $this->assertSame($expectedDueDate, $monthlyDuesInfo['due_date']);
        $this->assertSame($persistedInvoice->due_date->toDateString(), $monthlyDuesInfo['due_date']);
    }
}
