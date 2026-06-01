<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature   = 'telegram:set-webhook {url? : HTTPS URL webhook endpoint}';
    protected $description = 'Set Telegram webhook URL';

    public function handle(TelegramService $telegram): void
    {
        $url    = $this->argument('url') ?? config('app.url') . '/api/telegram/webhook';
        $secret = config('services.telegram.secret');

        if (!$secret) {
            $this->warn('⚠️  TELEGRAM_WEBHOOK_SECRET belum diset di .env');
        }

        $this->info("Setting webhook: {$url}");
        $result = $telegram->setWebhook($url, $secret ?? '');

        if ($result['ok'] ?? false) {
            $this->info('✅ Webhook berhasil diset!');
            $this->line('   ' . ($result['description'] ?? ''));
        } else {
            $this->error('❌ Gagal set webhook: ' . ($result['description'] ?? 'Unknown error'));
        }
    }
}
