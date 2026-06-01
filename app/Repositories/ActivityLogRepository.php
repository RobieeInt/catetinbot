<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ActivityLogRepository
{
    public function log(string $chatId, string $action, array $payload): void
    {
        try {
            DB::table('activity_logs')->insert([
                'chat_id'    => $chatId,
                'action'     => $action,
                'payload'    => json_encode($payload),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ActivityLog::log failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function recent(string $chatId, int $limit = 50): array
    {
        return DB::select(
            'SELECT * FROM activity_logs WHERE chat_id = ? ORDER BY created_at DESC LIMIT ?',
            [$chatId, $limit]
        );
    }
}
