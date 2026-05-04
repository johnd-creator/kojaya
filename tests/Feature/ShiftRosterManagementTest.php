<?php

namespace Tests\Feature;

use App\Models\ShiftRoster;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShiftRosterManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_shift_roster_for_month(): void
    {
        $user = User::factory()->create();

        WorkShift::factory()->create(['name' => 'Shift Pagi', 'type' => 'SHIFT', 'start_time' => '06:00', 'end_time' => '14:00']);
        WorkShift::factory()->create(['name' => 'Shift Siang', 'type' => 'SHIFT', 'start_time' => '14:00', 'end_time' => '22:00']);
        WorkShift::factory()->create(['name' => 'Shift Malam', 'type' => 'SHIFT', 'start_time' => '22:00', 'end_time' => '06:00']);

        $this->actingAs($user)
            ->from(route('shift-rosters.index'))
            ->post(route('shift-rosters.generate'), [
                'year' => 2026,
                'month' => 1,
            ])
            ->assertRedirect(route('shift-rosters.index'));

        $this->assertDatabaseCount('shift_rosters', 124);
    }

    public function test_user_can_view_and_filter_shift_roster_index(): void
    {
        $user = User::factory()->create();
        $shift = WorkShift::factory()->create(['type' => 'SHIFT']);
        ShiftRoster::factory()->create(['date' => '2026-02-01', 'shift_group' => 'A', 'work_shift_id' => $shift->id]);
        ShiftRoster::factory()->create(['date' => '2026-02-02', 'shift_group' => 'B', 'work_shift_id' => $shift->id]);
        ShiftRoster::factory()->create(['date' => '2026-03-01', 'shift_group' => 'C', 'work_shift_id' => $shift->id]);

        $this->actingAs($user)
            ->get(route('shift-rosters.index', ['year' => 2026, 'month' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ShiftRoster/Index')
                ->where('year', 2026)
                ->where('month', 2)
                ->where('daysInMonth', 28)
                ->has('rosters.2026-02-01', 1)
                ->has('rosters.2026-02-02', 1)
            );
    }

    public function test_user_can_update_roster_entry_to_off_day(): void
    {
        $user = User::factory()->create();
        $shift = WorkShift::factory()->create(['type' => 'SHIFT']);
        $roster = ShiftRoster::factory()->create([
            'date' => '2026-02-10',
            'shift_group' => 'A',
            'work_shift_id' => $shift->id,
            'is_off_day' => false,
        ]);

        $this->actingAs($user)
            ->from(route('shift-rosters.index'))
            ->put(route('shift-rosters.update', $roster->id), [
                'work_shift_id' => null,
                'is_off_day' => true,
                'notes' => 'Libur pengganti',
            ])
            ->assertRedirect(route('shift-rosters.index'));

        $roster->refresh();
        $this->assertTrue($roster->is_off_day);
        $this->assertNull($roster->work_shift_id);
        $this->assertSame('Libur pengganti', $roster->notes);
    }
}
