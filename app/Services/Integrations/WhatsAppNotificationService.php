<?php

namespace App\Services\Integrations;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string $title, string $message, array $data = []): bool
    {
        $recipient = $this->recipientPhone($user);

        if ($recipient === null) {
            Log::info('WhatsApp notification skipped: no opted-in phone number', [
                'user_id' => $user->id,
                'title' => $title,
            ]);

            return false;
        }

        if (! $this->isConfigured()) {
            Log::info('WhatsApp notification disabled: credentials are not configured', [
                'user_id' => $user->id,
                'to' => $recipient,
                'title' => $title,
            ]);

            return false;
        }

        $response = Http::withToken((string) config('services.whatsapp.access_token'))
            ->acceptJson()
            ->post($this->endpoint(), [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $this->messageBody($title, $message, $data),
                ],
            ]);

        if (! $response->successful()) {
            Log::error('WhatsApp notification failed', [
                'user_id' => $user->id,
                'to' => $recipient,
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ]);

            return false;
        }

        Log::info('WhatsApp notification sent', [
            'user_id' => $user->id,
            'to' => $recipient,
            'title' => $title,
            'provider_message_id' => $response->json('messages.0.id'),
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendOrFail(User $user, string $title, string $message, array $data = []): bool
    {
        $preference = $user->notificationPreference;

        if (! $preference?->isChannelEnabled('whatsapp')) {
            return true;
        }

        if ($this->recipientPhone($user) === null) {
            return true;
        }

        if (! $this->isConfigured()) {
            return true;
        }

        if (! $this->send($user, $title, $message, $data)) {
            throw new \RuntimeException('WhatsApp notification delivery failed.');
        }

        return true;
    }

    public function recipientPhone(User $user): ?string
    {
        $preference = $user->notificationPreference;

        if (! $preference?->isChannelEnabled('whatsapp')) {
            return null;
        }

        $phone = $preference->whatsapp_phone ?: $user->cooperativeMember?->phone;

        if (! $phone) {
            return null;
        }

        return $this->normalizePhone($phone);
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '0')) {
            return (string) config('services.whatsapp.default_country_code', '62').substr($digits, 1);
        }

        return $digits;
    }

    private function isConfigured(): bool
    {
        return filled(config('services.whatsapp.access_token'))
            && filled(config('services.whatsapp.phone_number_id'));
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.whatsapp.endpoint'), '/')
            .'/'.config('services.whatsapp.phone_number_id')
            .'/messages';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function messageBody(string $title, string $message, array $data): string
    {
        $reference = $data['reference'] ?? $data['invoice_id'] ?? $data['leave_id'] ?? null;

        return trim($title."\n\n".$message.($reference ? "\n\nRef: {$reference}" : ''));
    }
}
