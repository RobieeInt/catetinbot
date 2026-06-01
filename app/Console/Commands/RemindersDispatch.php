<?php

namespace App\Console\Commands;

use App\Repositories\ReminderRepository;
use App\Repositories\ActivityLogRepository;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemindersDispatch extends Command
{
    protected $signature   = 'reminders:dispatch';
    protected $description = 'Kirim reminder yang sudah jatuh waktu';

    public function handle(TelegramService $telegram, ActivityLogRepository $activityLog): void
    {
        $repo = new ReminderRepository($activityLog);
        $now  = now(config('app.timezone'));

        $stages = [
            ['minutes' => 20, 'column' => 'notified_20', 'label' => '20 menit lagi'],
            ['minutes' => 10, 'column' => 'notified_10', 'label' => '10 menit lagi'],
            ['minutes' => 5,  'column' => 'notified_5',  'label' => '5 menit lagi'],
        ];

        $sent = 0;

        foreach ($stages as $stage) {
            $threshold = $now->copy()->addMinutes($stage['minutes'])->format('Y-m-d H:i:s');
            $reminders = DB::select(
                "SELECT * FROM reminders WHERE notified = 0 AND active = 1 AND {$stage['column']} = 0 AND remind_at <= ?",
                [$threshold]
            );

            foreach ($reminders as $reminder) {
                try {
                    $telegram->sendMessage(
                        (string) $reminder->chat_id,
                        "⏰ <b>Pengingat ({$stage['label']}):</b> {$reminder->task}"
                    );
                    DB::statement(
                        "UPDATE reminders SET {$stage['column']} = 1, updated_at = ? WHERE id = ?",
                        [now(), $reminder->id]
                    );
                    $sent++;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('RemindersDispatch failed', [
                        'reminder_id' => $reminder->id,
                        'stage'       => $stage['label'],
                        'error'       => $e->getMessage(),
                    ]);
                }
            }
        }

        // Kirim notif utama + mark done ketika waktunya tiba
        $due = DB::select(
            'SELECT * FROM reminders WHERE notified = 0 AND active = 1 AND remind_at <= ?',
            [$now->format('Y-m-d H:i:s')]
        );

        foreach ($due as $reminder) {
            try {
                $telegram->sendMessage(
                    (string) $reminder->chat_id,
                    "🔔 <b>Waktunya!</b> {$reminder->task}"
                );
                $repo->markNotifiedOrReschedule($reminder);
                $sent++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('RemindersDispatch due failed', [
                    'reminder_id' => $reminder->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        if ($sent > 0) {
            $this->line("[reminders:dispatch] Sent {$sent} notification(s)");
        }
    }
}
