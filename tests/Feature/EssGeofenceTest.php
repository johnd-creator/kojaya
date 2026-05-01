<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Tests\TestCase;

class EssGeofenceTest extends TestCase
{
    public function test_check_in_outside_geofence_fails(): void
    {
        $org = Organization::factory()->create([
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius' => 100, // meters
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $employee = Employee::factory()->create([
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
            'user_id' => $user->id,
        ]);

        $lat = -6.198000; // ~222m from center (outside)
        $lng = 106.816666;

        $response = $this->actingAs($user)->postJson(route('ess.attendance.check-in'), [
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => 30,
        ]);

        $response->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertNull(Attendance::where('employee_id', $employee->id)->where('date', now()->toDateString())->first());
    }
}
