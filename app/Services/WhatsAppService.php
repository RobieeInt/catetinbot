<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private ?string $accessToken;
    private ?string $phoneNumberId;
    private ?string $defaultRecipientPhone;

    public function __construct()
    {
        $this->accessToken           = config('services.whatsapp.access_token');
        $this->phoneNumberId         = config('services.whatsapp.phone_number_id');
        $this->defaultRecipientPhone = config('services.whatsapp.recipient_phone');
    }

    public function sendMessage(string $chatId, string $text): void
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning('WhatsAppService: Access token or Phone Number ID is not configured.');
            return;
        }

        $recipientPhone = $this->resolvePhoneNumber($chatId);
        if (empty($recipientPhone)) {
            Log::warning('WhatsAppService: Recipient phone number could not be resolved.', ['chat_id' => $chatId]);
            return;
        }

        $formattedText = $this->formatForWhatsApp($text);
        $url = "https://graph.facebook.com/v25.0/{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $recipientPhone,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $formattedText,
            ],
        ];

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('WhatsAppService::sendMessage HTTP error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'to'     => $recipientPhone,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsAppService::sendMessage exception', [
                'error'   => $e->getMessage(),
                'chat_id' => $chatId,
                'to'      => $recipientPhone,
            ]);
        }
    }

    private function resolvePhoneNumber(string $chatId): ?string
    {
        $ownerTelegram = config('services.telegram.owner_chat_id');
        if (!empty($ownerTelegram) && (string) $chatId === (string) $ownerTelegram) {
            return $this->defaultRecipientPhone;
        }

        // If chatId is numeric and looks like a phone number, return it
        if (preg_match('/^[0-9]{10,15}$/', $chatId)) {
            return $chatId;
        }

        return $this->defaultRecipientPhone;
    }

    private function formatForWhatsApp(string $html): string
    {
        // Replace HTML bold tags with WhatsApp bold stars
        $text = preg_replace('/<(?:b|strong)>(.*?)<\/(?:b|strong)>/i', '*$1*', $html);

        // Replace HTML italic tags with WhatsApp underscores
        $text = preg_replace('/<(?:i|em)>(.*?)<\/(?:i|em)>/i', '_$1_', $text);

        // Replace HTML strikethrough tags
        $text = preg_replace('/<(?:s|strike)>(.*?)<\/(?:s|strike)>/i', '~$1~', $text);

        // Replace HTML code tags
        $text = preg_replace('/<code>(.*?)<\/code>/i', '`$1`', $text);

        // Replace <br> and <br /> with newlines
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

        // Remove any remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities (like &nbsp;, &amp;, &lt;, &gt;, etc.)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }
}
