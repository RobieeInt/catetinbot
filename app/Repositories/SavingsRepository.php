<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class SavingsRepository
{
    public function all(string $chatId): array
    {
        return DB::select(
            'SELECT * FROM savings_goals WHERE chat_id = ? ORDER BY created_at ASC',
            [$chatId]
        );
    }

    public function create(string $chatId, string $name, int $target, ?string $deadline = null): int
    {
        return DB::table('savings_goals')->insertGetId([
            'chat_id'       => $chatId,
            'name'          => $name,
            'target_amount' => $target,
            'saved_amount'  => 0,
            'deadline'      => $deadline,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function addProgress(string $chatId, string $name, int $amount): ?object
    {
        $goal = $this->findByName($chatId, $name);
        if (!$goal) {
            return null;
        }

        $newAmount = (int) $goal->saved_amount + $amount;
        DB::statement(
            'UPDATE savings_goals SET saved_amount = ?, updated_at = ? WHERE id = ?',
            [$newAmount, now(), $goal->id]
        );

        return $this->findByName($chatId, $name);
    }

    public function findByName(string $chatId, string $name): ?object
    {
        return DB::selectOne(
            'SELECT * FROM savings_goals WHERE chat_id = ? AND LOWER(name) = LOWER(?) LIMIT 1',
            [$chatId, $name]
        );
    }

    public function deleteById(int $id): void
    {
        DB::statement('DELETE FROM savings_goals WHERE id = ?', [$id]);
    }
}
