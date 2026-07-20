<?php

namespace App\Services;

class NotificationService extends TelegramService
{
    private ?string $temporaryChannel = null;
    private WhatsAppService $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        parent::__construct();
        $this->whatsapp = $whatsapp;
    }

    /**
     * Set a temporary channel override for the duration of a request.
     */
    public function setTemporaryChannel(?string $channel): void
    {
        $this->temporaryChannel = $channel;
    }

    /**
     * Send a notification/message to the active channel(s).
     */
    public function sendMessage(string $chatId, string $text, array $extra = []): void
    {
        $channel = $this->temporaryChannel ?? config('services.notification.channel', 'telegram');
        
        \Illuminate\Support\Facades\Log::info("NotificationService::sendMessage called", [
            'chat_id' => $chatId,
            'channel' => $channel
        ]);

        if ($channel === 'telegram' || $channel === 'both') {
            parent::sendMessage($chatId, $text, $extra);
        }

        if ($channel === 'whatsapp' || $channel === 'both') {
            $this->whatsapp->sendMessage($chatId, $text);
        }
    }
}
