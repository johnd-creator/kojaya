<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
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
     * @return array<string, array{0: string, 1: string|null, 2: array<int, string>, 3: bool}>
     */
    public static function personaAbilityProvider(): array
    {
        return [
            'Anggota - member app' => ['Anggota', 'member', ['profile:read', 'member:read', 'member:write'], true],
            'Anggota - default app' => ['Anggota', null, ['profile:read', 'member:read', 'member:write'], true],
            'Employee - ess app' => ['Employee', 'ess', ['profile:read', 'ess:read', 'ess:write', 'attendance:read', 'attendance:write', 'payroll:read'], true],
            'Teknisi - technician app' => ['Technician', 'technician', ['profile:read', 'work-orders:read', 'work-orders:write'], true],
            'Pengurus Koperasi - default app' => ['Pengurus Koperasi', null, ['profile:read', 'cooperative:read', 'cooperative:write', 'cooperative.member.read', 'cooperative.member.write', 'cooperative.member.verify', 'cooperative.member.approve', 'cooperative.member.export', 'cooperative.resignation.review', 'cooperative.dues.read', 'cooperative.dues.write', 'cooperative.payment.read', 'cooperative.payment.record', 'cooperative.loan.read', 'cooperative.loan.write', 'cooperative.loan.approve', 'cooperative.ledger.read', 'cooperative.ledger.write', 'cooperative.report.read', 'cooperative.pos.read', 'cooperative.pos.write', 'cooperative.settings.write', 'pos:read', 'pos:write', 'reports:read'], true],
            'Kasir Koperasi - default app' => ['Kasir Koperasi', null, ['profile:read', 'cooperative:read', 'cooperative:write', 'cooperative.member.read', 'cooperative.payment.read', 'cooperative.payment.record', 'cooperative.loan.read', 'cooperative.report.read', 'cooperative.pos.read', 'cooperative.pos.write', 'pos:read', 'pos:write', 'reports:read'], true],
            'System Admin - member app (scoped, no wildcard)' => ['System Admin', 'member', ['profile:read'], true],
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

    public function test_document05_resource_and_pagination_contracts_are_explicit(): void
    {
        $spec = (new OpenApiGenerator)->generate();

        $this->assertSame(
            '#/components/schemas/PaginatedMemberInvoiceResponse',
            $spec['paths']['/api/v1/dues/invoices']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
        $this->assertSame(
            '#/components/schemas/CooperativePaymentResponse',
            $spec['paths']['/api/v1/dues/payments']['post']['responses']['201']['content']['application/json']['schema']['$ref'],
        );
        $this->assertSame(
            '#/components/schemas/BatchCooperativePaymentResponse',
            $spec['paths']['/api/v1/dues/payments/batch']['post']['responses']['201']['content']['application/json']['schema']['$ref'],
        );
        $this->assertSame(
            '#/components/schemas/PaginatedLoanResponse',
            $spec['paths']['/api/v1/loans']['get']['responses']['200']['content']['application/json']['schema']['$ref'],
        );
        $duesParameters = collect($spec['paths']['/api/v1/dues/invoices']['get']['parameters'])->keyBy('name');
        $notificationParameters = collect($spec['paths']['/api/notifications/recent']['get']['parameters'])->keyBy('name');

        $this->assertSame(50, $duesParameters['per_page']['schema']['maximum']);
        $this->assertSame(10, $notificationParameters['limit']['schema']['maximum']);
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
        $this->assertContains('member:read', $spec['paths']['/api/v1/member/payments/{payment}/status']['get']['x-required-abilities']);
        $this->assertSame(
            'binary',
            $spec['paths']['/api/v1/member/payments/{payment}/qris-image']['get']['responses']['200']['content']['image/png']['schema']['format'],
        );
    }

    public function test_midtrans_qris_charge_returns_safe_member_scoped_qr_contract(): void
    {
        config([
            'services.midtrans.server_key' => 'midtrans-server-key',
            'services.midtrans.is_production' => false,
            'services.midtrans.qris_acquirer' => 'shopeepay',
        ]);

        [$memberUser, $member, $invoice] = $this->disposableMemberInvoice();
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'paid_at' => now(),
            'status' => 'PENDING',
        ]);

        Http::fake(function ($request) {
            $payload = $request->data();

            $this->assertSame('https://api.sandbox.midtrans.com/v2/charge', $request->url());
            $this->assertSame('qris', $payload['payment_type'] ?? null);
            $this->assertSame('shopeepay', $payload['qris']['acquirer'] ?? null);

            return Http::response([
                'status_code' => '201',
                'transaction_status' => 'pending',
                'order_id' => $payload['transaction_details']['order_id'],
                'gross_amount' => '100000.00',
                'actions' => [
                    [
                        'name' => 'generate-qr-code-v2',
                        'method' => 'GET',
                        'url' => 'https://api.sandbox.midtrans.com/v2/qris/qr-code',
                    ],
                ],
                'expiry_time' => '2026-06-29 10:00:00',
            ], 201);
        });

        Sanctum::actingAs($memberUser, ['member:write']);

        $this->postJson('/api/payments/charge', [
            'cooperative_payment_id' => $payment->id,
            'channel' => 'QRIS',
        ])
            ->assertCreated()
            ->assertJsonPath('data.provider', 'midtrans')
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.channel', 'QRIS')
            ->assertJsonPath('data.amount', 100000)
            ->assertJsonPath('data.qr_image_url', '/api/v1/member/payments/'.$payment->id.'/qris-image')
            ->assertJsonPath('data.poll_after_seconds', 5)
            ->assertJsonMissingPath('data.gateway_payload')
            ->assertJsonMissingPath('data.qr_string')
            ->assertJsonMissingPath('data.instructions.qr_action_url');

        $payment->refresh();

        $this->assertSame('midtrans', $payment->gateway_provider);
        $this->assertSame('PENDING', $payment->gateway_status);
        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/qr-code', $payment->gateway_payload['actions'][0]['url'] ?? null);
    }

    public function test_member_qris_image_endpoint_is_authenticated_member_scoped_and_returns_bytes(): void
    {
        [$memberUser, $member, $invoice] = $this->disposableMemberInvoice();
        $otherUser = User::factory()->create();
        CooperativeMember::factory()->active()->create(['user_id' => $otherUser->id]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-QRIS-IMAGE',
            'gateway_status' => 'PENDING',
            'paid_at' => now(),
            'status' => 'PENDING',
        ]);
        $payment->forceFill([
            'gateway_payload' => [
                'presentation' => [
                    'provider' => 'midtrans',
                    'reference' => 'KOJ-QRIS-IMAGE',
                    'status' => 'PENDING',
                    'channel' => 'QRIS',
                    'amount' => 100000,
                    'qr_string' => '00020101021226620016ID.CO.SHOPEE.WWW01189360091800218840800210ID1020304050303UMI51440014ID.CO.QRIS.WWW0215ID2020020304050303UMI52045411530336054061000005802ID5906KOJAYA6013JAKARTA PUSAT6304ABCD',
                    'qr_image_url' => '/api/v1/member/payments/'.$payment->id.'/qris-image',
                    'poll_after_seconds' => 5,
                ],
            ],
        ])->save();

        $this->getJson('/api/v1/member/payments/'.$payment->id.'/qris-image')
            ->assertUnauthorized();

        Sanctum::actingAs($otherUser, ['member:read']);
        $this->get('/api/v1/member/payments/'.$payment->id.'/qris-image')
            ->assertForbidden();

        Sanctum::actingAs($memberUser, ['member:read']);
        $response = $this->get('/api/v1/member/payments/'.$payment->id.'/qris-image');

        $response->assertOk();
        $this->assertStringStartsWith('image/png', $response->headers->get('Content-Type') ?? '');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_member_qris_image_rejects_non_allowlisted_midtrans_action_url(): void
    {
        [$memberUser, $member, $invoice] = $this->disposableMemberInvoice();
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-QRIS-SSRF',
            'gateway_status' => 'PENDING',
            'gateway_payload' => [
                'actions' => [
                    [
                        'name' => 'generate-qr-code-v2',
                        'method' => 'GET',
                        'url' => 'https://example.invalid/qris.png',
                    ],
                ],
            ],
            'paid_at' => now(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($memberUser, ['member:read']);

        $this->get('/api/v1/member/payments/'.$payment->id.'/qris-image')
            ->assertNotFound();
    }

    public function test_member_qris_image_rejects_redirects_oversized_and_non_image_midtrans_responses(): void
    {
        config(['services.midtrans.is_production' => false]);

        [$memberUser, $member, $invoice] = $this->disposableMemberInvoice();
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-QRIS-BAD-IMAGE',
            'gateway_status' => 'PENDING',
            'gateway_payload' => [
                'actions' => [
                    [
                        'name' => 'generate-qr-code-v2',
                        'method' => 'GET',
                        'url' => 'https://api.sandbox.midtrans.com/v2/qris/qr-code',
                    ],
                ],
            ],
            'paid_at' => now(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($memberUser, ['member:read']);

        Http::fake([
            'api.sandbox.midtrans.com/*' => Http::response('', 302, [
                'Location' => 'https://api.sandbox.midtrans.com/redirected.png',
            ]),
        ]);
        $this->get('/api/v1/member/payments/'.$payment->id.'/qris-image')
            ->assertStatus(502);

        Http::fake([
            'api.sandbox.midtrans.com/*' => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);
        $this->get('/api/v1/member/payments/'.$payment->id.'/qris-image')
            ->assertStatus(502);

        Http::fake([
            'api.sandbox.midtrans.com/*' => Http::response(str_repeat('x', 262145), 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);
        $this->get('/api/v1/member/payments/'.$payment->id.'/qris-image')
            ->assertStatus(502);
    }

    public function test_payment_gateway_charge_rejects_non_qris_channel(): void
    {
        [$memberUser, $member, $invoice] = $this->disposableMemberInvoice();
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'paid_at' => now(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($memberUser, ['member:write']);

        $this->postJson('/api/payments/charge', [
            'cooperative_payment_id' => $payment->id,
            'channel' => 'TRANSFER',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('channel');
    }

    public function test_payment_gateway_charge_requires_dues_invoice_payment(): void
    {
        [$memberUser, $member, $invoice] = $this->disposableMemberInvoice();
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'paid_at' => now(),
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($memberUser, ['member:write']);

        $this->postJson('/api/payments/charge', [
            'cooperative_payment_id' => $payment->id,
            'channel' => 'QRIS',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cooperative_payment_id');
    }

    public function test_midtrans_webhook_requires_valid_signature_and_is_idempotent(): void
    {
        config(['services.midtrans.server_key' => 'midtrans-server-key']);

        [$memberUser, $member, $invoice] = $this->disposableMemberInvoice();
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-1-PAIDTEST',
            'gateway_status' => 'PENDING',
            'gateway_payload' => [
                'actions' => [
                    [
                        'name' => 'generate-qr-code-v2',
                        'url' => 'https://api.sandbox.midtrans.com/v2/qris/qr-code',
                    ],
                ],
                'presentation' => [
                    'qr_image_url' => '/api/v1/member/payments/1/qris-image',
                ],
            ],
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
        ])->assertBadRequest();

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

        $payment->refresh();
        $invoice->refresh();
        $reconciledAt = $payment->reconciled_at?->toIso8601String();

        $this->assertSame(100000.0, (float) $invoice->paid_amount);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/qr-code', $payment->gateway_payload['actions'][0]['url'] ?? null);
        $this->assertSame('settlement', $payment->gateway_payload['latest_webhook']['transaction_status'] ?? null);
        $this->assertSame('/api/v1/member/payments/1/qris-image', $payment->gateway_payload['presentation']['qr_image_url'] ?? null);

        $this->assertTrue(
            $memberUser->notifications()
                ->where('type', 'App\\Notifications\\CooperativeDatabaseNotification')
                ->where('data->event_type', 'member.payment.approved')
                ->exists()
        );

        $this->assertSame($reconciledAt, $payment->refresh()->reconciled_at?->toIso8601String());
        $this->assertSame(100000.0, (float) $invoice->refresh()->paid_amount);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_midtrans_webhook_accepts_signed_notification_without_payment_type(): void
    {
        config(['services.midtrans.server_key' => 'midtrans-server-key']);

        [$memberUser, $member, $invoice] = $this->disposableMemberInvoice();
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'gateway_provider' => 'midtrans',
            'gateway_reference' => 'KOJ-1-NOPAYTYPE',
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
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'midtrans-server-key');

        $this->postJson('/api/payments/webhook', $payload)
            ->assertOk()
            ->assertJsonPath('data.gateway_status', 'PAID')
            ->assertJsonPath('data.status', 'APPROVED');

        $this->assertDatabaseHas('cooperative_payments', [
            'id' => $payment->id,
            'gateway_status' => 'PAID',
            'status' => 'APPROVED',
        ]);
        $this->assertTrue($memberUser->exists);
    }

    /**
     * @return array{0: User, 1: CooperativeMember, 2: CooperativeDuesInvoice}
     */
    private function disposableMemberInvoice(): array
    {
        $memberUser = User::factory()->create();
        $member = CooperativeMember::factory()->active()->create(['user_id' => $memberUser->id]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB-'.strtoupper(fake()->bothify('???###')),
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);

        return [$memberUser, $member, $invoice];
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

    public function test_dues_pagination_caps_per_page_at_50_runtime(): void
    {
        $org = \App\Models\Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);
        $type = CooperativeContributionType::query()->create([
            'organization_id' => $member->organization_id,
            'code' => 'WAJIB_PAGINATION',
            'name' => 'Iuran Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 55; $i++) {
            CooperativeDuesInvoice::query()->create([
                'cooperative_member_id' => $member->id,
                'cooperative_contribution_type_id' => $type->id,
                'organization_id' => $member->organization_id,
                'period' => now()->subMonthsNoOverflow($i)->format('Y-m'),
                'amount' => 100000,
                'paid_amount' => 0,
                'due_date' => now()->addWeek()->toDateString(),
                'status' => 'UNPAID',
            ]);
        }

        Sanctum::actingAs($admin, ['cooperative.dues.read', 'cooperative:read']);

        $response = $this->getJson('/api/v1/dues/invoices?per_page=999999');
        $response->assertOk();
        $this->assertSame(50, $response->json('meta.per_page'));
        $this->assertCount(50, $response->json('data'));

        $response = $this->getJson('/api/v1/dues/invoices?per_page=51');
        $response->assertOk();
        $this->assertSame(50, $response->json('meta.per_page'));
    }

    public function test_dues_pagination_non_numeric_and_zero_uses_resolver_defaults(): void
    {
        $org = \App\Models\Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);
        $type = CooperativeContributionType::query()->create([
            'organization_id' => $member->organization_id,
            'code' => 'WAJIB_DEFAULTS',
            'name' => 'Iuran Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 3; $i++) {
            CooperativeDuesInvoice::query()->create([
                'cooperative_member_id' => $member->id,
                'cooperative_contribution_type_id' => $type->id,
                'organization_id' => $member->organization_id,
                'period' => now()->subMonths($i)->format('Y-m'),
                'amount' => 100000,
                'paid_amount' => 0,
                'due_date' => now()->addWeek()->toDateString(),
                'status' => 'UNPAID',
            ]);
        }

        Sanctum::actingAs($admin, ['cooperative.dues.read', 'cooperative:read']);

        $nonNumeric = $this->getJson('/api/v1/dues/invoices?per_page=abc');
        $nonNumeric->assertOk();
        $this->assertSame(15, $nonNumeric->json('meta.per_page'), 'Non-numeric per_page must use default.');

        $zero = $this->getJson('/api/v1/dues/invoices?per_page=0');
        $zero->assertOk();
        $this->assertSame(1, $zero->json('meta.per_page'), 'Zero per_page must clamp to minimum 1.');

        $negative = $this->getJson('/api/v1/dues/invoices?per_page=-5');
        $negative->assertOk();
        $this->assertSame(1, $negative->json('meta.per_page'), 'Negative per_page must clamp to minimum 1.');
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
