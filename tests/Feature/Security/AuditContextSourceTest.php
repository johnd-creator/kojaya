<?php

namespace Tests\Feature\Security;

use App\Jobs\GeneratePosReportPdf;
use App\Models\AuditLog;
use App\Models\BackgroundJob;
use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\PosSalesReportService;
use App\Support\AuditContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AuditContextSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_http_operation_records_source_http(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        $this->actingAs($user)
            ->withHeader('X-Correlation-ID', 'aabbccdd-1111-2222-3333-444455556666')
            ->put(route('cooperative.members.update', $member), [
                'name' => $member->name,
                'nama_anggota' => $member->nama_anggota,
                'email' => $member->email,
                'no_telp' => $member->no_telp,
                'phone' => $member->phone,
                'jenis_anggota' => $member->jenis_anggota,
                'jenis_kelamin' => $member->jenis_kelamin,
                'kategori' => $member->kategori,
                'autodebet' => $member->autodebet,
            ])->assertRedirect();

        $audit = AuditLog::query()->where('action', 'member.profile.updated')->latest('id')->firstOrFail();
        $this->assertSame('http', $audit->source);
        $this->assertSame('aabbccdd-1111-2222-3333-444455556666', $audit->correlation_id);
        $this->assertSame((string) $user->id, (string) $audit->user_id);
    }

    public function test_webhook_route_records_source_webhook(): void
    {
        $intent = MemberPaymentIntent::factory()->create(['gateway_status' => 'PENDING']);

        $this->postSignedMidtransWebhook(
            orderId: $intent->gateway_reference,
            transactionStatus: 'cancel',
            grossAmount: $intent->amount,
            statusCode: '410',
            headers: ['X-Correlation-ID' => '22223333-4444-5555-6666-777788889999'],
        )->assertOk();

        $audit = AuditLog::query()->where('action', 'gateway.CANCELLED')->latest('id')->firstOrFail();
        $this->assertSame('webhook', $audit->source);
        $this->assertNull($audit->user_id);
        $this->assertSame('22223333-4444-5555-6666-777788889999', $audit->correlation_id);
        $this->assertSame((string) $intent->member->organization_id, (string) $audit->organization_id);
    }

    public function test_scheduler_records_source_scheduler(): void
    {
        $intent = MemberPaymentIntent::factory()->create([
            'payable_type' => MemberPaymentIntent::PAYABLE_STORE_ORDER,
            'reservation_status' => MemberPaymentIntent::RESERVATION_RESERVED,
            'expires_at' => now()->subMinute(),
            'metadata' => ['items' => []],
        ]);

        $this->artisan('orders:expire-reservations', ['--limit' => 1])
            ->assertExitCode(0);

        $audit = AuditLog::query()->where('action', 'reservation.expiry.completed')->latest('id')->firstOrFail();
        $this->assertSame('scheduler', $audit->source);
        $this->assertNull($audit->user_id);
        $this->assertSame((string) $intent->member->organization_id, (string) $audit->organization_id);
    }

    public function test_cli_backfill_records_source_cli(): void
    {
        CooperativeMember::factory()->active()->create([
            'identity_number' => '1601234567890001',
        ]);

        $this->artisan('members:backfill-sensitive-data', [
            '--dry-run' => true,
            '--limit' => 1,
        ])->assertExitCode(0);

        $audit = AuditLog::query()->where('action', 'member.pii.backfill.completed')->latest('id')->firstOrFail();
        $this->assertSame('cli', $audit->source);
        $this->assertNull($audit->user_id);
    }

    public function test_queue_context_records_source_queue(): void
    {
        $org = Organization::factory()->create();
        $context = AuditContext::forQueue($org->id);

        app(AuditLogService::class)->log('test.queue.source', 'test', null, ['new' => ['ok' => true]], $context);

        $audit = AuditLog::query()->where('action', 'test.queue.source')->sole();
        $this->assertSame('queue', $audit->source);
        $this->assertNull($audit->user_id);
        $this->assertSame($org->id, $audit->organization_id);
    }

    public function test_real_pdf_queue_failure_records_queue_source_without_actor(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_pos_reports');
        $job = BackgroundJob::factory()->create(['user_id' => $user->id]);
        $service = \Mockery::mock(PosSalesReportService::class);
        $service->shouldReceive('setScopeCeiling')->byDefault();
        $service->shouldReceive('summaryForPeriod')->andThrow(new \RuntimeException('simulated report failure'));

        try {
            (new GeneratePosReportPdf($job->id))->handle($service, app(AuditLogService::class));
            $this->fail('Expected queue report failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('simulated report failure', $exception->getMessage());
        }

        $audit = AuditLog::query()->where('action', 'pos.report.pdf.failed')->latest('id')->firstOrFail();
        $this->assertSame('queue', $audit->source);
        $this->assertNull($audit->user_id);
        $this->assertSame((string) $org->id, (string) $audit->organization_id);
    }

    public function test_correlation_id_is_propagated(): void
    {
        $correlationId = '11112222-3333-4444-5555-666677778888';

        $context = AuditContext::forQueue(correlationId: $correlationId);

        app(AuditLogService::class)->log('test.correlation.propagation', 'test', null, ['new' => ['ok' => true]], $context);

        $audit = AuditLog::query()->where('action', 'test.correlation.propagation')->sole();
        $this->assertSame($correlationId, $audit->correlation_id);
    }

    public function test_correlation_id_accepted_from_request_header(): void
    {
        $correlationId = '99998888-7777-6666-5555-444433332222';

        $member = CooperativeMember::factory()->active()->create();
        $user = User::factory()->create(['organization_id' => $member->organization_id]);
        $user->assignRole('Admin Koperasi');

        $this->actingAs($user)
            ->withHeader('X-Correlation-ID', $correlationId)
            ->put(route('cooperative.members.update', $member), [
                'name' => $member->name,
                'nama_anggota' => $member->nama_anggota,
                'email' => $member->email,
                'no_telp' => $member->no_telp,
                'phone' => $member->phone,
                'jenis_anggota' => $member->jenis_anggota,
                'jenis_kelamin' => $member->jenis_kelamin,
                'kategori' => $member->kategori,
                'autodebet' => $member->autodebet,
            ]);

        $audit = AuditLog::query()->where('action', 'member.profile.updated')->latest('id')->firstOrFail();
        $this->assertSame($correlationId, $audit->correlation_id);
    }

    public function test_actor_is_null_for_machine_operations(): void
    {
        $context = AuditContext::forScheduler();

        app(AuditLogService::class)->log('test.machine.actor', 'test', null, ['new' => ['ok' => true]], $context);

        $audit = AuditLog::query()->where('action', 'test.machine.actor')->sole();
        $this->assertNull($audit->user_id);
        $this->assertSame([], $audit->actor_roles);
    }

    public function test_organization_derived_from_subject_when_no_actor(): void
    {
        $org = Organization::factory()->create();
        $member = \App\Models\CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        $context = AuditContext::forScheduler();

        app(AuditLogService::class)->log('test.org.from.subject', 'test', $member, ['new' => ['ok' => true]], $context);

        $audit = AuditLog::query()->where('action', 'test.org.from.subject')->sole();
        $this->assertSame($org->id, $audit->organization_id);
    }

    public function test_gateway_metadata_appears_in_redacted_new_values(): void
    {
        $context = AuditContext::fromWebhook(request());

        app(AuditLogService::class)->log('test.gateway.metadata', 'payment', null, [
            'new' => [
                'gateway_status' => 'SETTLEMENT',
                'reservation_status' => 'CONFIRMED',
                'settlement_status' => 'SETTLED',
                'incident_type' => 'PAYMENT_RECEIVED',
                'manual_resolution' => false,
                'gateway_payload' => ['secret' => 'should-be-redacted'],
            ],
        ], $context);

        $audit = AuditLog::query()->where('action', 'test.gateway.metadata')->sole();
        $encoded = json_encode($audit->new_values);

        $this->assertSame('SETTLEMENT', $audit->new_values['gateway_status']);
        $this->assertSame('CONFIRMED', $audit->new_values['reservation_status']);
        $this->assertSame('SETTLED', $audit->new_values['settlement_status']);
        $this->assertSame('PAYMENT_RECEIVED', $audit->new_values['incident_type']);
        $this->assertFalse($audit->new_values['manual_resolution']);
        $this->assertSame('[REDACTED]', $audit->new_values['gateway_payload']);
        $this->assertStringNotContainsString('should-be-redacted', $encoded);
    }

    public function test_no_raw_gateway_payload_persisted(): void
    {
        $context = AuditContext::fromWebhook(request());

        app(AuditLogService::class)->log('test.redaction.gateway', 'payment', null, [
            'new' => [
                'gateway_status' => 'SETTLEMENT',
                'reservation_status' => 'CONFIRMED',
                'settlement_status' => 'SETTLED',
                'incident_type' => 'PAYMENT_RECEIVED',
                'manual_resolution' => false,
                'identity_number' => 'identity-sentinel',
                'NIK' => 'nik-sentinel',
                'NpWp' => 'npwp-sentinel',
                'noRekening' => 'account-sentinel',
                'bank_account_number' => 'bank-number-sentinel',
                'accountHolder' => 'account-holder-sentinel',
                'token' => 'token-sentinel',
                'Authorization' => 'authorization-sentinel',
                'secret' => 'secret-sentinel',
                'password' => 'password-sentinel',
                'qrString' => 'qr-sentinel',
                'gateway_payload' => ['raw' => 'gateway-sentinel'],
                'ciphertext' => 'ciphertext-sentinel',
                'blindIndex' => 'blind-index-sentinel',
            ],
        ], $context);

        $audit = AuditLog::query()->where('action', 'test.redaction.gateway')->sole();
        $encoded = json_encode($audit->new_values);

        foreach ([
            'identity-sentinel',
            'nik-sentinel',
            'npwp-sentinel',
            'account-sentinel',
            'bank-number-sentinel',
            'account-holder-sentinel',
            'token-sentinel',
            'authorization-sentinel',
            'secret-sentinel',
            'password-sentinel',
            'qr-sentinel',
            'gateway-sentinel',
            'ciphertext-sentinel',
            'blind-index-sentinel',
        ] as $sentinel) {
            $this->assertStringNotContainsString($sentinel, $encoded);
        }

        $this->assertSame('SETTLEMENT', $audit->new_values['gateway_status']);
        $this->assertSame('CONFIRMED', $audit->new_values['reservation_status']);
        $this->assertSame('SETTLED', $audit->new_values['settlement_status']);
        $this->assertSame('PAYMENT_RECEIVED', $audit->new_values['incident_type']);
        $this->assertFalse($audit->new_values['manual_resolution']);
    }

    public function test_sensitive_aliases_are_redacted_in_old_new_and_reason_while_safe_siblings_remain(): void
    {
        $secret = 'document05-secret-sentinel';

        app(AuditLogService::class)->log('test.redaction.aliases', 'payment', null, [
            'old' => [
                'access_token' => $secret,
                'refreshToken' => $secret,
                'id_token' => $secret,
                'api_key' => $secret,
                'clientSecret' => $secret,
                'private_key' => $secret,
                'server_key' => $secret,
                'signatureKey' => $secret,
                'webhook_payload' => $secret,
                'rawPayload' => $secret,
                'authorization_header' => $secret,
                'headers' => ['Authorization' => $secret],
                'credentials' => $secret,
                'ciphertext' => $secret,
                'blind_index' => $secret,
                'gateway_payload' => $secret,
                'safe_status' => 'SETTLEMENT',
                'tokens_revoked' => 2,
                'token_count' => 3,
                'authorization_result' => 'allowed',
            ],
            'new' => [
                'ACCESS_TOKEN' => $secret,
                'Refresh_Token' => $secret,
                'IdToken' => $secret,
                'apiKey' => $secret,
                'client_secret' => $secret,
                'privateKey' => $secret,
                'serverKey' => $secret,
                'signature_key' => $secret,
                'WebhookPayload' => $secret,
                'raw_payload' => $secret,
                'Authorization' => $secret,
                'headers' => ['authorization' => $secret],
                'credentials' => $secret,
                'ciphertext' => $secret,
                'blindIndex' => $secret,
                'gateway_payload' => $secret,
                'gateway_status' => 'CONFIRMED',
            ],
            'reason' => $secret,
        ]);

        $audit = AuditLog::query()->where('action', 'test.redaction.aliases')->sole();
        $encoded = json_encode([
            'old' => $audit->old_values,
            'new' => $audit->new_values,
            'reason' => $audit->reason,
        ], JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString($secret, $encoded);
        $this->assertSame('SETTLEMENT', $audit->old_values['safe_status']);
        $this->assertSame(2, $audit->old_values['tokens_revoked']);
        $this->assertSame(3, $audit->old_values['token_count']);
        $this->assertSame('allowed', $audit->old_values['authorization_result']);
        $this->assertSame('CONFIRMED', $audit->new_values['gateway_status']);
    }

    public function test_unknown_top_level_audit_change_keys_are_rejected_fail_closed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('canonical keys');

        app(AuditLogService::class)->log('test.redaction.flat-rejected', 'test', null, [
            'gateway_status' => 'SETTLEMENT',
            'access_token' => 'flat-secret-sentinel',
        ]);
    }

    public function test_valid_sources_list_is_comprehensive(): void
    {
        $this->assertContains('http', AuditContext::VALID_SOURCES);
        $this->assertContains('webhook', AuditContext::VALID_SOURCES);
        $this->assertContains('queue', AuditContext::VALID_SOURCES);
        $this->assertContains('cli', AuditContext::VALID_SOURCES);
        $this->assertContains('scheduler', AuditContext::VALID_SOURCES);
        $this->assertContains('system', AuditContext::VALID_SOURCES);
        $this->assertContains('domain', AuditContext::VALID_SOURCES);
    }

    public function test_arbitrary_audit_source_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AuditContext(
            actorId: null,
            actorRoles: [],
            organizationId: null,
            correlationId: '11112222-3333-4444-5555-666677778888',
            ip: null,
            userAgent: null,
            source: 'untrusted-source',
        );
    }
}
