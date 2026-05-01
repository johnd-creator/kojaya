<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $user->email_verified_at = now();
        $user->save();
        $this->actingAs($user);
    }

    public function test_can_list_audit_logs(): void
    {
        AuditLog::factory()->count(5)->create();

        $response = $this->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_can_filter_audit_logs_by_action(): void
    {
        AuditLog::factory()->create(['action' => 'CREATE']);
        AuditLog::factory()->create(['action' => 'UPDATE']);
        AuditLog::factory()->create(['action' => 'DELETE']);

        $response = $this->getJson('/api/audit-logs?action=CREATE');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_audit_logs_by_module(): void
    {
        AuditLog::factory()->create(['module' => 'employees']);
        AuditLog::factory()->create(['module' => 'certificates']);

        $response = $this->getJson('/api/audit-logs?module=employees');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_single_audit_log(): void
    {
        $log = AuditLog::factory()->create();

        $response = $this->getJson("/api/audit-logs/{$log->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $log->id,
                'action' => $log->action,
                'module' => $log->module,
            ]);
    }

    public function test_can_get_audit_history_for_subject(): void
    {
        $subjectType = 'App\Models\Employee';
        $subjectId = 1;

        AuditLog::factory()->count(3)->create([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
        ]);

        $encodedType = urlencode($subjectType);
        $response = $this->getJson("/api/audit-logs/history/{$encodedType}/{$subjectId}");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_export_audit_logs(): void
    {
        AuditLog::factory()->count(3)->create();

        $response = $this->getJson('/api/audit-logs/export');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'exported_at',
            ]);
    }
}
