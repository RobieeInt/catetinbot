<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Repositories\ActivityLogRepository;

class DebtRepository
{
    public function __construct(
        private ActivityLogRepository $activityLog
    ) {}

    public function create(string $chatId, string $type, string $person, int $amount, ?string $note, ?string $dueDate): int
    {
        $id = DB::table('debts')->insertGetId([
            'chat_id'    => $chatId,
            'type'       => $type,
            'person'     => $person,
            'amount'     => $amount,
            'note'       => $note,
            'due_date'   => $dueDate,
            'settled'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function open(string $chatId, ?string $type = null): array
    {
        if ($type) {
            return DB::select(
                'SELECT * FROM debts WHERE chat_id = ? AND type = ? AND settled = 0 ORDER BY due_date ASC, created_at ASC',
                [$chatId, $type]
            );
        }
        return DB::select(
            'SELECT * FROM debts WHERE chat_id = ? AND settled = 0 ORDER BY type ASC, due_date ASC',
            [$chatId]
        );
    }

    public function settleByPerson(string $chatId, string $type, string $person): ?object
    {
        $debt = DB::selectOne(
            'SELECT * FROM debts WHERE chat_id = ? AND type = ? AND LOWER(person) = LOWER(?) AND settled = 0 LIMIT 1',
            [$chatId, $type, $person]
        );
        if (!$debt) {
            return null;
        }

        DB::statement(
            'UPDATE debts SET settled = 1, updated_at = ? WHERE id = ?',
            [now(), $debt->id]
        );

        $this->activityLog->log($chatId, 'settle_debt', [
            'debt_id' => $debt->id,
            'type'    => $type,
            'person'  => $person,
            'amount'  => $debt->amount,
        ]);

        return $debt;
    }

    public function dueSoon(string $chatId, string $date): array
    {
        return DB::select(
            'SELECT * FROM debts WHERE chat_id = ? AND settled = 0 AND due_date = ?',
            [$chatId, $date]
        );
    }

    public function dueSoonAll(string $date): array
    {
        return DB::select(
            'SELECT * FROM debts WHERE settled = 0 AND due_date = ?',
            [$date]
        );
    }

    public function deleteById(int $id): void
    {
        DB::statement('DELETE FROM debts WHERE id = ?', [$id]);
    }

    public function all(string $chatId): array
    {
        return DB::select(
            'SELECT * FROM debts WHERE chat_id = ? ORDER BY settled ASC, due_date ASC, created_at ASC',
            [$chatId]
        );
    }
}
