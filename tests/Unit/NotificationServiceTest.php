<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class NotificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure dummy values
        Config::set('services.telegram.token', 'dummy_token');
        Config::set('services.telegram.owner_chat_id', '12345');
        Config::set('services.whatsapp.access_token', 'dummy_token');
        Config::set('services.whatsapp.phone_number_id', 'dummy_phone_id');
        Config::set('services.whatsapp.recipient_phone', '62812345678');
    }

    public function test_sends_to_telegram_only_when_configured()
    {
        Config::set('services.notification.channel', 'telegram');
        Http::fake();

        $whatsappService = new WhatsAppService();
        $notificationService = new NotificationService($whatsappService);
        $notificationService->sendMessage('12345', 'Hello World');

        // Should call Telegram endpoint
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org/botdummy_token/sendMessage')
                && $request['chat_id'] === '12345'
                && $request['text'] === 'Hello World';
        });

        // Should NOT call WhatsApp endpoint
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com');
        });
    }

    public function test_sends_to_whatsapp_only_when_configured()
    {
        Config::set('services.notification.channel', 'whatsapp');
        Http::fake();

        $whatsappService = new WhatsAppService();
        $notificationService = new NotificationService($whatsappService);
        $notificationService->sendMessage('12345', 'Hello World');

        // Should NOT call Telegram endpoint
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org');
        });

        // Should call WhatsApp endpoint
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com/v20.0/dummy_phone_id/messages')
                && $request['to'] === '62812345678'
                && $request['text']['body'] === 'Hello World';
        });
    }

    public function test_sends_to_both_when_configured()
    {
        Config::set('services.notification.channel', 'both');
        Http::fake();

        $whatsappService = new WhatsAppService();
        $notificationService = new NotificationService($whatsappService);
        $notificationService->sendMessage('12345', 'Hello World');

        // Should call Telegram
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org/botdummy_token/sendMessage');
        });

        // Should call WhatsApp
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com/v20.0/dummy_phone_id/messages');
        });
    }
}
