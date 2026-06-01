<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use App\Services\FinanceService;
use Illuminate\Console\Command;

class RecapSend extends Command
{
    protected $signature   = 'recap:send {--period=weekly : weekly atau monthly}';
    protected $description = 'Kirim rekap keuangan ke OWNER_CHAT_ID';

    public function handle(TelegramService $telegram, FinanceService $finance): void
    {
        $period  = $this->option('period') === 'monthly' ? 'bulanan' : 'mingguan';
        $chatId  = (string) env('OWNER_CHAT_ID');

        if (!$chatId) {
            $this->error('OWNER_CHAT_ID belum diset di .env');
            return;
        }

        try {
            $text = $finance->buildRecap($chatId, $period);
            $telegram->sendMessage($chatId, $text);
            $this->info("[recap:send] Rekap {$period} terkirim ke {$chatId}");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('RecapSend failed', ['error' => $e->getMessage()]);
            $this->error('Gagal kirim rekap: ' . $e->getMessage());
        }
    }
}
