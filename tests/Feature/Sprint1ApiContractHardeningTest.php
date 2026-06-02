<?php

namespace Tests\Feature;

use App\Http\Resources\AttendanceResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\LoanInstallmentResource;
use App\Http\Resources\LoanResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\PayrollResource;
use App\Http\Resources\PointTransactionResource;
use App\Http\Resources\PosTransactionResource;
use App\Http\Resources\RewardResource;
use App\Http\Resources\SavingsLedgerResource;
use App\Http\Resources\VendorResource;
use App\Http\Resources\WorkOrderResource;
use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Sprint1ApiContractHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_env_example_exists_for_ci_bootstrap(): void
    {
        $contents = file_get_contents(base_path('.env.example'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('APP_KEY=', $contents);
        $this->assertStringContainsString('DB_CONNECTION=pgsql', $contents);
        $this->assertStringContainsString('SANCTUM_TOKEN_EXPIRATION=43200', $contents);
        $this->assertStringContainsString('MIDTRANS_SERVER_KEY=', $contents);
    }

    public function test_mobile_api_success_responses_include_success_envelope_without_breaking_data_payload(): void
    {
        Role::firstOrCreate(['name' => 'Anggota']);
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.member.id', $member->id);
    }

    public function test_mobile_api_validation_errors_include_standard_error_envelope(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'message',
                'errors',
                'error',
                'error_code',
                'error_details',
            ]);
    }

    public function test_mobile_api_resource_transformers_exist_for_primary_domains(): void
    {
        $resources = [
            MemberResource::class,
            SavingsLedgerResource::class,
            LoanResource::class,
            LoanInstallmentResource::class,
            PosTransactionResource::class,
            PointTransactionResource::class,
            RewardResource::class,
            EmployeeResource::class,
            AttendanceResource::class,
            PayrollResource::class,
            WorkOrderResource::class,
            VendorResource::class,
        ];

        foreach ($resources as $resource) {
            $this->assertTrue(class_exists($resource), "{$resource} is missing.");
        }
    }
}
