<?php

namespace App\Console\Commands;

use App\Repositories\SubscriptionRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\ActivityLogRepository;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SubscriptionsRun extends Command
{
    protected $signature   = 'subscriptions:run';
    protected $description = 'Auto-charge langganan & kirim reminder jatuh tempo';

    public function handle(TelegramService $telegram, ActivityLogRepository $activityLog): void
    {
        $today    = today_wita();
        $month    = now(config('app.timezone'))->format('Y-m');
        $todayDay = (int) now(config('app.timezone'))->format('j');

        $subRepo = new SubscriptionRepository($activityLog);
        $txRepo  = new TransactionRepository($activityLog);

        $allActive = DB::select('SELECT * FROM subscriptions WHERE active = 1');

        foreach ($allActive as $sub) {
            $chargeDay = (int) $sub->day_of_month;
            $daysUntil = $chargeDay - $todayDay;

            // Reminder H-3
            if ($daysUntil === 3) {
                try {
                    $telegram->sendMessage(
                        (string) $sub->chat_id,
                        "📅 <b>Reminder Langganan</b>\n"
                        . "3 hari lagi: <b>{$sub->name}</b>\n"
                        . "Nominal: " . rp((int) $sub->amount) . " (tgl {$chargeDay})"
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('SubscriptionsRun reminder H-3 failed', ['sub_id' => $sub->id, 'error' => $e->getMessage()]);
                }
            }

            // Reminder H-1
            if ($daysUntil === 1) {
                try {
                    $telegram->sendMessage(
                        (string) $sub->chat_id,
                        "⚠️ <b>Reminder Langganan</b>\n"
                        . "Besok tagih: <b>{$sub->name}</b>\n"
                        . "Nominal: " . rp((int) $sub->amount) . " — siapkan saldonya!"
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('SubscriptionsRun reminder H-1 failed', ['sub_id' => $sub->id, 'error' => $e->getMessage()]);
                }
            }

            // Hari H — auto-charge
            if ($daysUntil === 0) {
                $alreadyCharged = DB::selectOne(
                    'SELECT id FROM subscriptions WHERE id = ? AND last_charged_month = ?',
                    [$sub->id, $month]
                );

                if ($alreadyCharged) {
                    continue;
                }

                try {
                    $txRepo->create([
                        'chat_id'   => $sub->chat_id,
                        'type'      => 'expense',
                        'wallet_id' => $sub->wallet_id,
                        'date'      => $today,
                        'total'     => (int) $sub->amount,
                        'category'  => $sub->category,
                        'note'      => "Langganan: {$sub->name}",
                    ]);
                    $subRepo->markCharged((int) $sub->id, $month);
                    $telegram->sendMessage(
                        (string) $sub->chat_id,
                        "💳 <b>Langganan otomatis dicatat:</b> {$sub->name}\n"
                        . "Nominal: " . rp((int) $sub->amount)
                    );
                    $this->line("[subscriptions:run] Charged: {$sub->name} ({$sub->chat_id})");
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('SubscriptionsRun charge failed', ['sub_id' => $sub->id, 'error' => $e->getMessage()]);
                }
            }
        }
    }
}
