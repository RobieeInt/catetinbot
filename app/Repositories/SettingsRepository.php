<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class SettingsRepository
{
    public function getBudgets(string $chatId): object
    {
        $settings = DB::selectOne('SELECT * FROM settings WHERE chat_id = ? LIMIT 1', [$chatId]);
        if (!$settings) {
            return (object) ['daily' => 0, 'weekly' => 0, 'monthly' => 0];
        }
        return (object) [
            'daily'   => (int) $settings->daily_budget,
            'weekly'  => (int) $settings->weekly_budget,
            'monthly' => (int) $settings->monthly_budget,
        ];
    }

    public function setBudget(string $chatId, string $period, int $amount): void
    {
        $column = match ($period) {
            'harian'   => 'daily_budget',
            'mingguan' => 'weekly_budget',
            'bulanan'  => 'monthly_budget',
            default    => 'weekly_budget',
        };

        $existing = DB::selectOne('SELECT id FROM settings WHERE chat_id = ? LIMIT 1', [$chatId]);
        if ($existing) {
            DB::statement("UPDATE settings SET {$column} = ?, updated_at = ? WHERE chat_id = ?", [$amount, now(), $chatId]);
        } else {
            DB::table('settings')->insert([
                'chat_id'        => $chatId,
                'daily_budget'   => 0,
                'weekly_budget'  => 0,
                'monthly_budget' => 0,
                $column          => $amount,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}
