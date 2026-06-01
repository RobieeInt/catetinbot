<?php

namespace App\Console\Commands;

use App\Repositories\DebtRepository;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class DebtsRemind extends Command
{
    protected $signature   = 'debts:remind';
    protected $description = 'Kirim reminder utang/piutang yang jatuh tempo besok';

    public function handle(TelegramService $telegram): void
    {
        $tomorrow = now('Asia/Makassar')->addDay()->format('Y-m-d');
        $repo     = app(DebtRepository::class);
        $debts    = $repo->dueSoonAll($tomorrow);

        foreach ($debts as $debt) {
            try {
                $typeStr = $debt->type === 'piutang' ? 'Piutang dari' : 'Utang ke';
                $telegram->sendMessage(
                    (string) $debt->chat_id,
                    "⏰ <b>{$typeStr} {$debt->person}</b> jatuh tempo besok!\n"
                    . "Nominal: " . rp((int) $debt->amount)
                );
                $this->line("[debts:remind] Notified: {$debt->type} {$debt->person} ({$debt->chat_id})");
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('DebtsRemind failed', [
                    'debt_id' => $debt->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }
}
