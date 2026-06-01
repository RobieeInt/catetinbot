<?php

namespace App\Console\Commands;

use App\Repositories\ReminderRepository;
use App\Repositories\ActivityLogRepository;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class RemindersDispatch extends Command
{
    protected $signature   = 'reminders:dispatch';
    protected $description = 'Kirim reminder yang sudah jatuh waktu';

    public function handle(TelegramService $telegram, ActivityLogRepository $activityLog): void
    {
        $leadMinutes = (int) env('REMINDER_LEAD_MINUTES', 20);
        $threshold   = now('Asia/Makassar')->addMinutes($leadMinutes)->format('Y-m-d H:i:s');

        $repo      = new ReminderRepository($activityLog);
        $reminders = $repo->due($threshold);

        foreach ($reminders as $reminder) {
            try {
                $telegram->sendMessage(
                    (string) $reminder->chat_id,
                    "⏰ <b>Pengingat:</b> {$reminder->task}"
                );
                $repo->markNotifiedOrReschedule($reminder);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('RemindersDispatch failed', [
                    'reminder_id' => $reminder->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        if (!empty($reminders)) {
            $this->line('[reminders:dispatch] Sent ' . count($reminders) . ' reminder(s)');
        }
    }
}
