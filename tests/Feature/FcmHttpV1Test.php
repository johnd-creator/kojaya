<?php

namespace Tests\Feature;

use App\Models\MobileDeviceToken;
use App\Models\User;
use App\Services\Integrations\FcmAccessTokenProvider;
use App\Services\Integrations\PushNotificationService;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\CreatesTestRsaJwk;
use Tests\TestCase;

class FcmHttpV1Test extends TestCase
{
    use CreatesTestRsaJwk;

    private string $credentialPath;

    private array $keyPair;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->keyPair = $this->fakeRsaJwk();
        $this->credentialPath = tempnam(sys_get_temp_dir(), 'kojaya-fcm-test-');
        file_put_contents($this->credentialPath, json_encode([
            'type' => 'service_account',
            'client_email' => 'synthetic@kojaya-test.iam.gserviceaccount.com',
            'private_key' => $this->keyPair['private_key'],
            'token_uri' => 'https://untrusted.invalid/token',
        ], JSON_THROW_ON_ERROR));
        config([
            'services.fcm.project_id' => 'kojaya-test',
            'services.fcm.service_account_path' => $this->credentialPath,
            'services.fcm.server_key' => null,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->credentialPath) && is_file($this->credentialPath)) {
            unlink($this->credentialPath);
        }
        parent::tearDown();
    }

    public function test_signed_assertion_fixed_audience_and_process_local_expiry_refresh(): void
    {
        Http::fake(['https://oauth2.googleapis.com/token' => Http::sequence()
            ->push(['access_token' => 'synthetic-first', 'token_type' => 'Bearer', 'expires_in' => 3600])
            ->push(['access_token' => 'synthetic-second', 'token_type' => 'Bearer', 'expires_in' => 3600])]);
        $provider = app(FcmAccessTokenProvider::class);
        $provider->validateConfiguration();
        Http::assertNothingSent();
        $this->assertSame('synthetic-first', $provider->accessToken());
        $this->assertSame('synthetic-first', $provider->accessToken());
        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            $claims = JWT::decode($request['assertion'], JWK::parseKey($this->keyPair['jwk'], 'RS256'));

            return $request->url() === 'https://oauth2.googleapis.com/token'
                && $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
                && $claims->aud === 'https://oauth2.googleapis.com/token'
                && $claims->scope === 'https://www.googleapis.com/auth/firebase.messaging'
                && $claims->iss === 'synthetic@kojaya-test.iam.gserviceaccount.com'
                && $claims->exp - $claims->iat === 3600;
        });
        $this->travel(3541)->seconds();
        $this->assertSame('synthetic-second', $provider->accessToken());
        Http::assertSentCount(2);
    }

    public function test_http_v1_data_only_payload_binds_the_recipient_and_preserves_content(): void
    {
        $this->fakeAuthentication();
        Http::fake(['https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/kojaya-test/messages/test-1'])]);
        $device = $this->device();
        $this->assertSame(1, app(PushNotificationService::class)->send($device->user, 'Title', 'Message', ['count' => 3, 'nested' => ['id' => 1]]));
        Http::assertSent(fn ($request): bool => $request->url() === 'https://fcm.googleapis.com/v1/projects/kojaya-test/messages:send'
            && $request->hasHeader('Authorization', 'Bearer synthetic-access')
            && $request['message']['token'] === 'synthetic-device-token'
            && ! isset($request['message']['notification'])
            && $request['message']['android'] === ['priority' => 'high', 'ttl' => '3600s']
            && $request['message']['data'] === ['count' => '3', 'nested' => '{"id":1}',
                'recipient_user_id' => (string) $device->user_id, 'kojaya_push_version' => '1',
                'title' => 'Title', 'body' => 'Message']);
        $this->assertNull($device->fresh()->revoked_at);
    }

    public function test_callers_cannot_override_recipient_or_protocol_fields(): void
    {
        $this->fakeAuthentication();
        Http::fake(['https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/kojaya-test/messages/reserved'])]);
        $device = $this->device();
        app(PushNotificationService::class)->send($device->user, 'Actual title', 'Actual body', [
            'recipient_user_id' => '99999', 'kojaya_push_version' => 'unsupported',
            'title' => 'Forged title', 'body' => 'Forged body',
        ]);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'messages:send') && $request['message']['data'] === [
            'recipient_user_id' => (string) $device->user_id, 'kojaya_push_version' => '1',
            'title' => 'Actual title', 'body' => 'Actual body',
        ]);
    }

    #[DataProvider('failedResponses')]
    public function test_provider_errors_do_not_revoke_valid_devices_or_claim_delivery(int $status, array $body, bool $revoked): void
    {
        $this->fakeAuthentication();
        Http::fake(['https://fcm.googleapis.com/*' => Http::response($body, $status)]);
        Log::spy();
        $device = $this->device();
        $this->assertSame(0, app(PushNotificationService::class)->send($device->user, 'private-title', 'private-body'));
        $this->assertSame($revoked, $device->fresh()->revoked_at !== null);
        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context = []): bool {
            $serialized = json_encode([$message, $context]);
            foreach (['synthetic-device-token', 'synthetic-access', 'provider-secret', 'private-title', 'private-body'] as $secret) {
                $this->assertStringNotContainsString($secret, $serialized);
            }

            return true;
        });
    }

    public static function failedResponses(): array
    {
        return [
            'unregistered' => [404, ['error' => ['details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED']]]], true],
            'generic not found' => [404, ['error' => ['message' => 'provider-secret']], false],
            'invalid payload' => [400, ['error' => ['status' => 'INVALID_ARGUMENT', 'message' => 'provider-secret']], false],
            'unauthenticated' => [401, ['error' => ['message' => 'provider-secret']], false],
            'wrong sender' => [403, ['error' => ['status' => 'SENDER_ID_MISMATCH']], false],
            'quota' => [429, ['error' => ['message' => 'provider-secret']], false],
            'unavailable' => [503, ['error' => ['message' => 'provider-secret']], false],
            'invalid success' => [200, [], false],
        ];
    }

    public function test_authentication_failure_is_sanitized_and_never_sends_a_message(): void
    {
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['error_description' => 'provider-secret'], 400)]);
        $provider = app(FcmAccessTokenProvider::class);
        try {
            $provider->accessToken();
            $this->fail('Expected authentication failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('FCM authentication failed.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'fcm.googleapis.com'));
    }

    public function test_invalid_credentials_and_project_fail_without_network(): void
    {
        config(['services.fcm.project_id' => '../wrong-project']);
        try {
            app(FcmAccessTokenProvider::class)->validateConfiguration();
            $this->fail('Invalid project accepted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('FCM project configuration is invalid.', $exception->getMessage());
        }
        config(['services.fcm.project_id' => 'kojaya-test']);
        file_put_contents($this->credentialPath, '{invalid-secret');
        $this->expectExceptionMessage('FCM service account configuration is invalid.');
        Http::assertNothingSent();
        app(FcmAccessTokenProvider::class)->validateConfiguration();
    }

    public function test_empty_configuration_never_uses_legacy_key_or_network(): void
    {
        config(['services.fcm.project_id' => null, 'services.fcm.service_account_path' => null, 'services.fcm.server_key' => 'legacy-secret']);
        $device = $this->device();
        $this->assertSame(0, app(PushNotificationService::class)->send($device->user, 'Title', 'Body'));
        Http::assertNothingSent();
    }

    public function test_transport_failure_keeps_token_and_fails_outbox_delivery_safely(): void
    {
        $this->fakeAuthentication();
        Http::fake(['https://fcm.googleapis.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('provider-secret')]);
        $device = $this->device();
        try {
            app(PushNotificationService::class)->sendOrFail($device->user, 'Title', 'Body');
            $this->fail('Transport failure must not count as delivery.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Push notification delivery failed for one or more active Android tokens.', $exception->getMessage());
        }
        $this->assertNull($device->fresh()->revoked_at);
    }

    private function fakeAuthentication(): void
    {
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'synthetic-access', 'token_type' => 'Bearer', 'expires_in' => 3600])]);
    }

    public function test_partial_delivery_remains_retryable_and_honors_provider_delay(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->travelTo(now()->startOfSecond());
        $this->fakeAuthentication();
        Http::fake(['https://fcm.googleapis.com/*' => Http::sequence()
            ->push(['name' => 'projects/kojaya-test/messages/success'])
            ->push(['error' => ['message' => 'provider-secret']], 429, ['Retry-After' => '180'])]);
        $first = $this->device();
        $second = $first->replicate();
        $second->device_id = 'second-device';
        $second->push_token = 'second-token';
        $second->save();
        $outbox = \App\Models\NotificationOutbox::factory()->create([
            'user_id' => $first->user_id, 'channel' => 'push', 'available_at' => now(), 'max_attempts' => 3,
        ]);
        (new \App\Jobs\ProcessNotificationOutbox($outbox->id))->handle(
            app(\App\Services\NotificationService::class),
            app(PushNotificationService::class),
            app(\App\Services\Integrations\WhatsAppNotificationService::class),
        );
        $outbox->refresh();
        $this->assertSame('pending', $outbox->status);
        $this->assertSame(1, $outbox->attempts);
        $this->assertTrue($outbox->available_at->equalTo(now()->addSeconds(180)));
        $this->assertStringNotContainsString('provider-secret', $outbox->last_error);
        $this->assertNull($second->refresh()->revoked_at);
    }

    public function test_routing_data_is_always_present_and_unregistered_device_is_not_retried(): void
    {
        $this->fakeAuthentication();
        Http::fake(['https://fcm.googleapis.com/*' => Http::response(['error' => ['details' => [[
            '@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED',
        ]]]], 404)]);
        $device = $this->device();
        $this->assertSame(0, app(PushNotificationService::class)->sendOrFail($device->user, 'Title', 'Body'));
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'messages:send')
            && $request['message']['data']['recipient_user_id'] === (string) $device->user_id
            && ! isset($request['message']['notification']));
        $this->assertNotNull($device->refresh()->revoked_at);
    }

    public function test_rejected_access_token_is_refreshed_for_the_next_send(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::sequence()
                ->push(['access_token' => 'synthetic-first', 'token_type' => 'Bearer', 'expires_in' => 3600])
                ->push(['access_token' => 'synthetic-second', 'token_type' => 'Bearer', 'expires_in' => 3600]),
            'https://fcm.googleapis.com/*' => Http::sequence()->push([], 401)->push(['name' => 'projects/kojaya-test/messages/success']),
        ]);
        $device = $this->device();
        $service = app(PushNotificationService::class);
        $this->assertSame(0, $service->send($device->user, 'Title', 'Body'));
        $this->assertSame(1, $service->send($device->user, 'Title', 'Body'));
        Http::assertSentCount(4);
    }

    public function test_public_root_service_account_is_rejected(): void
    {
        $publicFile = tempnam(public_path(), 'synthetic-fcm-');
        try {
            file_put_contents($publicFile, file_get_contents($this->credentialPath));
            config(['services.fcm.service_account_path' => $publicFile]);
            $this->expectExceptionMessage('FCM service account configuration is invalid.');
            app(FcmAccessTokenProvider::class)->validateConfiguration();
        } finally {
            unlink($publicFile);
            Http::assertNothingSent();
        }
    }

    public function test_configuration_preflight_requires_no_provider_request(): void
    {
        config([
            'app.version' => '1.0.0-rc.5',
            'app.api_contract_version' => '1.0.0',
            'security.pii_allow_schema_rollback' => false,
            'security.legacy_ability_fallback_enabled' => false,
            'services.midtrans.merchant_id' => null,
            'services.midtrans.server_key' => null,
            'services.midtrans.client_key' => null,
            'services.whatsapp.access_token' => null,
            'services.whatsapp.phone_number_id' => null,
        ]);
        $this->artisan('app:release-preflight', ['--strict-release-candidate' => true, '--require-android-push' => true])
            ->expectsOutput('integrations.fcm.service_account: PASS')
            ->expectsOutput('integrations.fcm.required: PASS')
            ->assertSuccessful();
        Http::assertNothingSent();
    }

    #[DataProvider('invalidTokenResponses')]
    public function test_invalid_oauth_success_responses_fail_closed(array $body): void
    {
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response($body)]);
        $this->expectExceptionMessage('FCM authentication failed.');
        app(FcmAccessTokenProvider::class)->accessToken();
    }

    public static function invalidTokenResponses(): array
    {
        return [
            'empty body' => [[]],
            'wrong type' => [['access_token' => 'synthetic', 'token_type' => 'Basic', 'expires_in' => 3600]],
            'expired' => [['access_token' => 'synthetic', 'token_type' => 'Bearer', 'expires_in' => 0]],
            'header injection' => [['access_token' => "synthetic\r\nsecret", 'token_type' => 'Bearer', 'expires_in' => 3600]],
        ];
    }

    private function device(): MobileDeviceToken
    {
        return MobileDeviceToken::query()->create([
            'user_id' => User::factory()->create()->id,
            'app' => 'member',
            'device_id' => 'synthetic-device',
            'platform' => 'android',
            'push_token' => 'synthetic-device-token',
            'last_seen_at' => now(),
        ]);
    }
}
