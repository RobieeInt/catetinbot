<?php

namespace App\Console\Commands;

use App\Repositories\SubscriptionRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\ActivityLogRepository;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SubscriptionsRun extends Command
{
    protected $signature   = 'subscriptions:run';
    protected $description = 'Auto-charge langganan & kirim reminder jatuh tempo';

    public function handle(TelegramService $telegram, ActivityLogRepository $activityLog): void
    {
        $today  = today_wita();
        $month  = now(config('app.timezone'))->format('Y-m');

        $subRepo = new SubscriptionRepository($activityLog);
        $txRepo  = new TransactionRepository($activityLog);

        // Auto-charge
        $due = $subRepo->dueToCharge($today);
        foreach ($due as $sub) {
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
                    "📅 <b>Langganan otomatis:</b> {$sub->name}\nNominal: " . rp((int) $sub->amount) . ' telah dicatat.'
                );
                $this->line("[subscriptions:run] Charged: {$sub->name} ({$sub->chat_id})");
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('SubscriptionsRun charge failed', [
                    'sub_id' => $sub->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        // Reminder sebelum jatuh tempo
        $todayDay    = (int) now(config('app.timezone'))->format('j');
        $remindToday = $subRepo->dueReminderSimple($todayDay);

        foreach ($remindToday as $sub) {
            // Hanya kirim reminder jika hari ini = hari tagih - reminder_days_before
            $chargeDay    = (int) $sub->day_of_month;
            $reminderDay  = (int) $sub->reminder_days_before;
            $targetDay    = $chargeDay - $reminderDay;

            if ($targetDay <= 0 || $todayDay !== $targetDay) {
                continue;
            }

            try {
                $telegram->sendMessage(
                    (string) $sub->chat_id,
                    "⚠️ <b>Pengingat Langganan:</b> {$sub->name}\n"
                    . "Jatuh tempo tgl {$chargeDay}, " . rp((int) $sub->amount) . '.'
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('SubscriptionsRun reminder failed', [
                    'sub_id' => $sub->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }
    }
}
