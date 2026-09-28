<?php

namespace App\Services\Integrations;

use App\Exceptions\PushDeliveryException;
use App\Models\MobileDeviceToken;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotificationService
{
    private bool $retryableFailure = false;

    private int $retryAfterSeconds = 60;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly FcmAccessTokenProvider $accessTokenProvider,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string $title, string $message, array $data = []): int
    {
        $this->retryableFailure = false;
        $this->retryAfterSeconds = 60;
        $tokens = MobileDeviceToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->orderBy('id')
            ->get();

        $this->notificationService->sendDatabase($user, $title, $message, [
            'channel' => 'push',
            ...$data,
        ]);

        $fcmTargets = $tokens->where('platform', 'android')->whereNotNull('push_token');
        $sent = 0;

        foreach ($fcmTargets as $token) {
            $result = $this->sendFcm($token->push_token, $title, $message, $data);

            if ($result['success']) {
                $sent++;
            }

            if ($result['invalid_token']) {
                $token->forceFill(['revoked_at' => now()])->save();
            } elseif (! $result['success']) {
                $this->retryableFailure = true;
            }
        }

        foreach ($tokens->where('platform', 'ios') as $token) {
            Log::info('Push notification (APNs placeholder)', [
                'user_id' => $user->id,
                'device_token_id' => $token->id,
            ]);
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendOrFail(User $user, string $title, string $message, array $data = []): int
    {
        $sent = $this->send($user, $title, $message, $data);

        if ($this->retryableFailure) {
            throw new PushDeliveryException($this->retryAfterSeconds);
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, invalid_token: bool, fcm_message_id: string|null}
     */
    private function sendFcm(string $pushToken, string $title, string $message, array $data = []): array
    {
        if (! $this->accessTokenProvider->isConfigured()) {
            Log::info('FCM disabled (HTTP v1 credentials are not configured)');

            return ['success' => false, 'invalid_token' => false, 'fcm_message_id' => null];
        }

        $messagePayload = [
            'token' => $pushToken,
            'notification' => [
                'title' => $title,
                'body' => $message,
            ],
        ];
        try {
            if ($data !== []) {
                $messagePayload['data'] = collect($data)
                    ->map(fn (mixed $value): string => is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR))
                    ->all();
            }

            $project = $this->accessTokenProvider->projectId();
            $response = Http::withToken($this->accessTokenProvider->accessToken())
                ->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(15)
                ->post('https://fcm.googleapis.com/v1/projects/'.$project.'/messages:send', [
                    'message' => $messagePayload,
                ]);

            if (! $response->successful()) {
                $retryAfter = $response->header('Retry-After');
                $retryAt = ctype_digit($retryAfter) ? now()->timestamp + (int) $retryAfter : strtotime($retryAfter);
                if ($retryAt !== false) {
                    $this->retryAfterSeconds = max($this->retryAfterSeconds, $retryAt - now()->timestamp);
                }
                if ($response->status() === 401) {
                    $this->accessTokenProvider->forgetToken();
                }

                // A payload INVALID_ARGUMENT must not revoke a valid device token.
                $unregistered = $response->status() === 404 && collect($response->json('error.details', []))
                    ->contains(fn (mixed $detail): bool => is_array($detail)
                        && ($detail['@type'] ?? null) === 'type.googleapis.com/google.firebase.fcm.v1.FcmError'
                        && ($detail['errorCode'] ?? null) === 'UNREGISTERED');
                Log::warning('FCM push failed', ['status' => $response->status()]);

                return ['success' => false, 'invalid_token' => $unregistered, 'fcm_message_id' => null];
            }

            $messageId = $response->json('name');
            if (! is_string($messageId) || ! str_starts_with($messageId, 'projects/'.$project.'/messages/')) {
                throw new \RuntimeException('Invalid FCM acknowledgment.');
            }

            Log::info('FCM push accepted');

            return ['success' => true, 'invalid_token' => false, 'fcm_message_id' => $messageId];
        } catch (Throwable) {
            // Do not persist request URLs/bodies, bearer tokens or provider exceptions.
            Log::warning('FCM push failed (configuration, transport or response)');

            return ['success' => false, 'invalid_token' => false, 'fcm_message_id' => null];
        }
    }
}
