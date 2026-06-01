<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Repositories\ActivityLogRepository;
use Carbon\Carbon;

class ReminderRepository
{
    public function __construct(
        private ActivityLogRepository $activityLog
    ) {}

    public function create(string $chatId, string $task, string $remindAt, string $repeat = 'none'): void
    {
        DB::table('reminders')->insert([
            'chat_id'    => $chatId,
            'task'       => $task,
            'remind_at'  => $remindAt,
            'repeat'     => $repeat,
            'notified'   => false,
            'active'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->activityLog->log($chatId, 'create_reminder', [
            'task'      => $task,
            'remind_at' => $remindAt,
            'repeat'    => $repeat,
        ]);
    }

    public function due(string $thresholdDatetime): array
    {
        return DB::select(
            'SELECT * FROM reminders WHERE notified = 0 AND active = 1 AND remind_at <= ?',
            [$thresholdDatetime]
        );
    }

    public function markNotifiedOrReschedule(object $reminder): void
    {
        if ($reminder->repeat === 'none') {
            DB::statement('UPDATE reminders SET notified = 1, updated_at = ? WHERE id = ?', [now(), $reminder->id]);
            return;
        }

        $next = Carbon::parse($reminder->remind_at);
        $next = match ($reminder->repeat) {
            'daily'   => $next->addDay(),
            'weekly'  => $next->addWeek(),
            'monthly' => $next->addMonth(),
            default   => $next->addDay(),
        };

        DB::statement(
            'UPDATE reminders SET remind_at = ?, notified = 0, notified_20 = 0, notified_10 = 0, notified_5 = 0, updated_at = ? WHERE id = ?',
            [$next->format('Y-m-d H:i:s'), now(), $reminder->id]
        );
    }

    public function pending(string $chatId): array
    {
        return DB::select(
            'SELECT * FROM reminders WHERE chat_id = ? AND active = 1 AND notified = 0 AND remind_at >= ? ORDER BY remind_at ASC',
            [$chatId, now()->format('Y-m-d H:i:s')]
        );
    }

    public function cancelLast(string $chatId): ?object
    {
        $reminder = DB::selectOne(
            'SELECT * FROM reminders WHERE chat_id = ? AND active = 1 ORDER BY created_at DESC LIMIT 1',
            [$chatId]
        );
        if (!$reminder) {
            return null;
        }
        DB::statement('UPDATE reminders SET active = 0, updated_at = ? WHERE id = ?', [now(), $reminder->id]);
        return $reminder;
    }

    public function allForOwner(string $chatId): array
    {
        return DB::select(
            'SELECT * FROM reminders WHERE chat_id = ? ORDER BY remind_at ASC',
            [$chatId]
        );
    }

    public function deleteById(int $id): void
    {
        DB::statement('DELETE FROM reminders WHERE id = ?', [$id]);
    }
}
