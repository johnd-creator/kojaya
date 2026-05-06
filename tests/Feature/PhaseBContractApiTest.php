<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Employee;
use App\Models\MobileDeviceToken;
use App\Models\User;
use App\Services\Integrations\PushNotificationService;
use App\Services\OpenApi\OpenApiGenerator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseBContractApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: array<int, string>}>
     */
    public static function personaAbilityProvider(): array
    {
        return [
            'Anggota - member app' => ['Anggota', 'member', ['profile:read', 'member:read', 'member:write', 'cooperative:read', 'cooperative:write']],
            'Employee - ess app' => ['Employee', 'ess', ['profile:read', 'ess:read', 'ess:write', 'attendance:read', 'attendance:write', 'payroll:read']],
            'Teknisi - technician app' => ['Technician', 'technician', ['profile:read', 'work-orders:read', 'work-orders:write']],
            'Pengurus Koperasi - default app' => ['Pengurus Koperasi', null, ['profile:read', 'cooperative:read', 'cooperative:write', 'pos:read', 'pos:write', 'reports:read']],
            'Kasir Koperasi - default app' => ['Kasir Koperasi', null, ['profile:read', 'cooperative:read', 'cooperative:write', 'pos:read', 'pos:write', 'reports:read']],
            'System Admin - all abilities' => ['System Admin', null, ['*']],
        ];
    }

    /**
     * @dataProvider personaAbilityProvider
     *
     * @param  array<int, string>  $expectedAbilities
     */
    #[ \PHPUnit\Framework\Attributes\DataProvider('personaAbilityProvider') ]
    public function test_persona_login_returns_correct_sanctum_abilities(string $role, ?string $app, array $expectedAbilities): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole($role);

        if ($role === 'Anggota') {
            CooperativeMember::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        } elseif ($role === 'Employee') {
            Employee::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        }

        $payload = [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Test Device',
        ];

        if ($app !== null) {
            $payload['app'] = $app;
        }

        $response = $this->postJson('/api/auth/login', $payload)
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer');

        $responseAbilities = $response->json('abilities');
        sort($responseAbilities);
        sort($expectedAbilities);

        $this->assertSame($expectedAbilities, $responseAbilities, "Role '{$role}' with app '{$app}' should get exactly these abilities.");
    }

    public function test_openapi_spec_is_valid_and_has_security_scheme(): void
    {
        $generator = new OpenApiGenerator;
        $spec = $generator->generate();

        $this->assertSame('3.0.3', $spec['openapi']);
        $this->assertNotEmpty($spec['paths']);

        $this->assertArrayHasKey('components', $spec);
        $this->assertArrayHasKey('securitySchemes', $spec['components']);
        $this->assertArrayHasKey('bearerAuth', $spec['components']['securitySchemes']);
        $this->assertSame('http', $spec['components']['securitySchemes']['bearerAuth']['type']);
        $this->assertArrayHasKey('responses', $spec['components']);
        $this->assertArrayHasKey('CreatePaymentChargeRequest', $spec['components']['schemas']);
        $this->assertArrayHasKey('PaymentGatewayWebhookRequest', $spec['components']['schemas']);
        $this->assertArrayHasKey('RegisterDeviceTokenRequest', $spec['components']['schemas']);

        $this->assertNotEmpty($spec['tags']);
        $tagNames = array_column($spec['tags'], 'name');
        $this->assertContains('Member', $tagNames);
        $this->assertContains('ESS', $tagNames);
        $this->assertContains('Technician', $tagNames);
        $this->assertContains('Auth', $tagNames);
        $this->assertContains('Integration', $tagNames);
    }

    public function test_openapi_persona_tagging_separates_route_groups(): void
    {
        $generator = new OpenApiGenerator;
        $spec = $generator->generate();

        $paths = $spec['paths'];

        foreach ($paths as $path => $operations) {
            foreach ($operations as $op) {
                $this->assertArrayHasKey('tags', $op, "Path {$path} must have tags");
                $this->assertNotEmpty($op['tags'], "Path {$path} must have at least one tag");
            }
        }

        $memberPaths = $this->filterPathsByTag($paths, 'Member');
        $this->assertNotEmpty($memberPaths, 'Member routes must be tagged');

        $essPaths = $this->filterPathsByTag($paths, 'ESS');
        $this->assertNotEmpty($essPaths, 'ESS routes must be tagged');

        $techPaths = $this->filterPathsByTag($paths, 'Technician');
        $this->assertNotEmpty($techPaths, 'Technician routes must be tagged');
    }

    public function test_write_endpoints_have_request_body_schema(): void
    {
        $generator = new OpenApiGenerator;
        $spec = $generator->generate();

        $writeEndpoints = $this->filterPathsByTag($spec['paths'], 'Member');
        $foundRequestBody = false;

        foreach ($writeEndpoints as $path => $operations) {
            foreach ($operations as $op) {
                if (($op['requestBody'] ?? null) !== null) {
                    $foundRequestBody = true;
                }
            }
        }

        $coopEndpoints = $this->filterPathsByTag($spec['paths'], 'Cooperative');
        foreach ($coopEndpoints as $path => $operations) {
            foreach ($operations as $op) {
                if (($op['requestBody'] ?? null) !== null) {
                    $foundRequestBody = true;
                }
            }
        }

        $this->assertTrue($foundRequestBody, 'At least one write endpoint must have requestBody');
    }

    public function test_unauthenticated_user_cannot_access_member_protected_routes(): void
    {
        $this->getJson('/api/v1/member/dashboard')
            ->assertStatus(401);

        $this->getJson('/api/v1/member/profile')
            ->assertStatus(401);

        $this->postJson('/api/v1/member/payments/proof')
            ->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_ess_protected_routes(): void
    {
        $this->getJson('/api/ess/dashboard')
            ->assertStatus(401);

        $this->postJson('/api/ess/attendance/check-in')
            ->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_technician_protected_routes(): void
    {
        $this->getJson('/api/technician/work-orders')
            ->assertStatus(401);

        $this->postJson('/api/technician/work-orders/1/start')
            ->assertStatus(401);
    }

    public function test_wrong_ability_token_is_rejected_on_member_write_endpoints(): void
    {
        $user = User::factory()->create();
        CooperativeMember::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        $token = $user->createToken('test', ['profile:read', 'member:read'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/member/payments/proof')
            ->assertStatus(403);
    }

    public function test_wrong_ability_token_is_rejected_on_ess_write_endpoints(): void
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        $token = $user->createToken('test', ['profile:read', 'ess:read'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/ess/attendance/check-in')
            ->assertStatus(403);
    }

    public function test_member_cannot_access_ess_routes(): void
    {
        $user = User::factory()->create();
        CooperativeMember::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->getJson('/api/ess/dashboard')
            ->assertStatus(403);
    }

    public function test_ess_user_cannot_access_member_routes(): void
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        Sanctum::actingAs($user, ['ess:read', 'ess:write']);

        $this->getJson('/api/v1/member/dashboard')
            ->assertStatus(403);
    }

    public function test_payment_gateway_charge_requires_member_ownership(): void
    {
        $owner = User::factory()->create();
        CooperativeMember::factory()->create(['user_id' => $owner->id, 'status' => 'ACTIVE']);

        $other = User::factory()->create();
        CooperativeMember::factory()->create(['user_id' => $other->id, 'status' => 'ACTIVE']);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $owner->cooperativeMember->id,
            'amount' => 50000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($other, ['member:read', 'member:write']);

        $this->postJson('/api/payments/charge', [
            'cooperative_payment_id' => $payment->id,
            'channel' => 'QRIS',
        ])->assertStatus(403);
    }

    public function test_openapi_json_endpoint_is_accessible(): void
    {
        $response = $this->getJson('/api/openapi.json')
            ->assertOk();

        $response->assertJsonPath('openapi', '3.0.3');
        $response->assertJsonPath('info.title', 'Kojaya API');
    }

    public function test_openapi_documents_phase_b_integration_contracts(): void
    {
        $spec = (new OpenApiGenerator)->generate();

        $this->assertSame(
            '#/components/schemas/CreatePaymentChargeRequest',
            $spec['paths']['/api/payments/charge']['post']['requestBody']['content']['application/json']['schema']['$ref'],
        );
        $this->assertSame(
            '#/components/schemas/RegisterDeviceTokenRequest',
            $spec['paths']['/api/devices/push-token']['post']['requestBody']['content']['application/json']['schema']['$ref'],
        );
        $this->assertSame(
            '#/components/schemas/PaymentGatewayWebhookRequest',
            $spec['paths']['/api/payments/webhook']['post']['requestBody']['content']['application/json']['schema']['$ref'],
        );

        $this->assertSame([], $spec['paths']['/api/payments/webhook']['post']['security']);
        $this->assertContains('member:write', $spec['paths']['/api/payments/charge']['post']['x-required-abilities']);
    }

    public function test_midtrans_webhook_requires_valid_signature_and_is_idempotent(): void
    {
        config(['services.midtrans.server_key' => 'midtrans-server-key']);

        $memberUser = User::factory()->create();
        $member = CooperativeMember::factory()->active()->create(['user_id' => $memberUser->id]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-1-PAIDTEST',
            'gateway_status' => 'PENDING',
            'paid_at' => now(),
            'status' => 'PENDING',
        ]);

        $payload = [
            'order_id' => $payment->gateway_reference,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'reconciliation_reference' => 'MIDTRANS-SETTLEMENT-1',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'midtrans-server-key');

        $this->postJson('/api/payments/webhook', [
            ...$payload,
            'signature_key' => 'invalid-signature',
        ])->assertAccepted();

        $this->assertDatabaseHas('cooperative_payments', [
            'id' => $payment->id,
            'status' => 'PENDING',
            'gateway_status' => 'PENDING',
        ]);

        $this->postJson('/api/payments/webhook', $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.gateway_status', 'PAID')
            ->assertJsonPath('data.reconciliation_reference', 'MIDTRANS-SETTLEMENT-1');

        $this->postJson('/api/payments/webhook', $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.reconciliation_reference', 'MIDTRANS-SETTLEMENT-1');

        $this->assertSame(1, $memberUser->notifications()->count());
    }

    public function test_fcm_push_uses_legacy_endpoint_payload_and_revokes_invalid_tokens(): void
    {
        config([
            'services.fcm.server_key' => 'fcm-server-key',
            'services.fcm.endpoint' => 'https://fcm.test/send',
        ]);

        $user = User::factory()->create();
        $validToken = MobileDeviceToken::query()->create([
            'user_id' => $user->id,
            'app' => 'member',
            'device_id' => 'android-valid',
            'platform' => 'android',
            'push_token' => 'valid-fcm-token',
            'last_seen_at' => now(),
        ]);
        $invalidToken = MobileDeviceToken::query()->create([
            'user_id' => $user->id,
            'app' => 'member',
            'device_id' => 'android-invalid',
            'platform' => 'android',
            'push_token' => 'invalid-fcm-token',
            'last_seen_at' => now(),
        ]);

        Http::fake([
            'https://fcm.test/send' => Http::sequence()
                ->push(['success' => 1, 'failure' => 0, 'results' => [['message_id' => 'msg-1']]], 200)
                ->push(['success' => 0, 'failure' => 1, 'results' => [['error' => 'NotRegistered']]], 200),
        ]);

        $sent = app(PushNotificationService::class)->send($user, 'Pembayaran diterima', 'Pembayaran berhasil.', [
            'payment_id' => 55,
        ]);

        $this->assertSame(1, $sent);
        $this->assertNull($validToken->refresh()->revoked_at);
        $this->assertNotNull($invalidToken->refresh()->revoked_at);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://fcm.test/send'
                && $request->hasHeader('Authorization', 'key=fcm-server-key')
                && $request['to'] === 'valid-fcm-token'
                && $request['notification']['title'] === 'Pembayaran diterima'
                && $request['data']['payment_id'] === '55';
        });
    }

    /**
     * @param  array<string, mixed>  $paths
     * @return array<string, mixed>
     */
    private function filterPathsByTag(array $paths, string $tag): array
    {
        return array_filter($paths, function (array $operations) use ($tag): bool {
            foreach ($operations as $op) {
                if (in_array($tag, $op['tags'] ?? [], true)) {
                    return true;
                }
            }

            return false;
        });
    }
}
