<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Repositories\ActivityLogRepository;

class SubscriptionRepository
{
    public function __construct(
        private ActivityLogRepository $activityLog
    ) {}

    public function all(string $chatId): array
    {
        return DB::select('SELECT * FROM subscriptions WHERE chat_id = ? ORDER BY day_of_month ASC', [$chatId]);
    }

    public function create(string $chatId, string $name, int $amount, string $category, ?int $walletId, int $dayOfMonth, int $reminderDaysBefore = 2): int
    {
        $id = DB::table('subscriptions')->insertGetId([
            'chat_id'               => $chatId,
            'name'                  => $name,
            'amount'                => $amount,
            'category'              => $category,
            'wallet_id'             => $walletId,
            'day_of_month'          => $dayOfMonth,
            'reminder_days_before'  => $reminderDaysBefore,
            'active'                => true,
            'last_charged_month'    => null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $this->activityLog->log($chatId, 'create_subscription', [
            'subscription_id' => $id,
            'name'            => $name,
            'amount'          => $amount,
        ]);

        return $id;
    }

    public function dueToCharge(string $today): array
    {
        $day   = (int) date('j', strtotime($today));
        $month = date('Y-m', strtotime($today));

        return DB::select(
            'SELECT * FROM subscriptions WHERE active = 1 AND day_of_month = ? AND (last_charged_month != ? OR last_charged_month IS NULL)',
            [$day, $month]
        );
    }

    public function markCharged(int $id, string $month): void
    {
        DB::statement('UPDATE subscriptions SET last_charged_month = ?, updated_at = ? WHERE id = ?', [$month, now(), $id]);
    }

    public function dueReminder(string $date): array
    {
        return DB::select(
            'SELECT s.* FROM subscriptions s
             WHERE s.active = 1
             AND DATE_ADD(?, INTERVAL s.reminder_days_before DAY) = STR_TO_DATE(CONCAT(YEAR(?), \'-\', LPAD(s.day_of_month, 2, \'0\'), \'-01\'), \'%Y-%m-%d\')',
            [$date, $date]
        );
    }

    public function dueReminderSimple(string $targetDay): array
    {
        return DB::select(
            'SELECT * FROM subscriptions WHERE active = 1 AND day_of_month = ?',
            [$targetDay]
        );
    }

    public function deleteById(int $id): void
    {
        DB::statement('DELETE FROM subscriptions WHERE id = ?', [$id]);
    }

    public function toggle(int $id, bool $active): void
    {
        DB::statement('UPDATE subscriptions SET active = ?, updated_at = ? WHERE id = ?', [(int) $active, now(), $id]);
    }
}
