<?php

namespace App\Services\Integrations;

use App\Models\MobileDeviceToken;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string $title, string $message, array $data = []): int
    {
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
            }
        }

        foreach ($tokens->where('platform', 'ios') as $token) {
            Log::info('Push notification (APNs placeholder)', [
                'user_id' => $user->id,
                'device_id' => $token->device_id,
                'push_token' => $token->push_token,
                'title' => $title,
            ]);
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, invalid_token: bool, fcm_message_id: string|null}
     */
    private function sendFcm(string $pushToken, string $title, string $message, array $data = []): array
    {
        if (! config('services.fcm.server_key')) {
            Log::info('FCM disabled (no server key configured)', [
                'push_token' => $pushToken,
                'title' => $title,
            ]);

            return ['success' => false, 'invalid_token' => false, 'fcm_message_id' => null];
        }

        $payload = [
            'to' => $pushToken,
            'notification' => [
                'title' => $title,
                'body' => $message,
            ],
            'data' => collect($data)
                ->map(fn (mixed $value): string => is_scalar($value) ? (string) $value : json_encode($value))
                ->all(),
        ];

        $response = Http::withHeaders([
            'Authorization' => 'key='.config('services.fcm.server_key'),
            'Content-Type' => 'application/json',
        ])
            ->post((string) config('services.fcm.endpoint', self::FCM_URL), $payload);

        $body = $response->json() ?: [];
        $result = $body['results'][0] ?? [];

        if (! $response->successful() || (int) ($body['failure'] ?? 0) > 0) {
            Log::error('FCM push failed', [
                'push_token' => $pushToken,
                'status' => $response->status(),
                'body' => $body,
            ]);

            $errorType = $result['error'] ?? $body['error']['details'][0]['errorCode'] ?? null;
            $invalidToken = in_array($errorType, ['NotRegistered', 'InvalidRegistration', 'UNREGISTERED', 'INVALID_ARGUMENT'], true);

            return ['success' => false, 'invalid_token' => $invalidToken, 'fcm_message_id' => null];
        }

        $messageId = $result['message_id'] ?? $body['name'] ?? null;

        Log::info('FCM push sent', [
            'push_token' => $pushToken,
            'fcm_message_id' => $messageId,
            'title' => $title,
        ]);

        return ['success' => true, 'invalid_token' => false, 'fcm_message_id' => $messageId];
    }
}
