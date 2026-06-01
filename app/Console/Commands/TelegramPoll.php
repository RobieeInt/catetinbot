<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\TelegramWebhookController;
use App\Services\TelegramService;
use App\Services\GeminiService;
use App\Services\FinanceService;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\ReminderRepository;
use App\Repositories\TransferRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\DebtRepository;
use App\Repositories\SavingsRepository;
use App\Repositories\ActivityLogRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TelegramPoll extends Command
{
    protected $signature   = 'telegram:poll';
    protected $description = 'Poll Telegram updates (development mode)';

    private int $offset = 0;

    public function handle(): void
    {
        $this->info('🤖 Telegram bot polling started... (Ctrl+C untuk stop)');

        $telegram = app(TelegramService::class);

        // Hapus webhook jika ada
        $telegram->deleteWebhook();

        // Load offset dari cache
        $this->offset = (int) Cache::get('telegram_poll_offset', 0);

        while (true) {
            try {
                // Dispatch reminders setiap iterasi
                $this->call('reminders:dispatch', [], $this->output);

                $updates = $telegram->getUpdates($this->offset);

                foreach ($updates as $update) {
                    $updateId     = $update['update_id'];
                    $this->offset = $updateId + 1;
                    Cache::put('telegram_poll_offset', $this->offset, now()->addDay());

                    $chatId = $update['message']['chat']['id'] ?? 'unknown';
                    $text   = $update['message']['text'] ?? '[media]';
                    $this->line("[" . now('Asia/Makassar')->format('H:i:s') . "] #{$updateId} chat:{$chatId} → {$text}");

                    $controller = $this->makeController();
                    $controller->process($update);
                }

                // Polling tiap 1 detik
                sleep(1);
            } catch (\Throwable $e) {
                $this->error('Poll error: ' . $e->getMessage());
                sleep(3);
            }
        }
    }

    private function makeController(): TelegramWebhookController
    {
        $activityLog = app(ActivityLogRepository::class);

        return new TelegramWebhookController(
            app(TelegramService::class),
            app(GeminiService::class),
            new FinanceService(
                new TransactionRepository($activityLog),
                app(SettingsRepository::class),
                app(WalletRepository::class)
            ),
            new TransactionRepository($activityLog),
            app(WalletRepository::class),
            app(SettingsRepository::class),
            new ReminderRepository($activityLog),
            new TransferRepository($activityLog),
            new SubscriptionRepository($activityLog),
            new DebtRepository($activityLog),
            app(SavingsRepository::class)
        );
    }
}
