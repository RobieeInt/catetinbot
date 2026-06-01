<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Repositories\ActivityLogRepository;

class TransactionRepository
{
    public function __construct(
        private ActivityLogRepository $activityLog
    ) {}

    public function create(array $data): object
    {
        DB::beginTransaction();
        try {
            $id = DB::table('transactions')->insertGetId([
                'chat_id'    => $data['chat_id'],
                'type'       => $data['type'],
                'wallet_id'  => $data['wallet_id'] ?? null,
                'date'       => $data['date'],
                'total'      => $data['total'],
                'category'   => $data['category'],
                'merchant'   => $data['merchant'] ?? null,
                'note'       => $data['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    DB::table('transaction_items')->insert([
                        'transaction_id' => $id,
                        'name'           => $item['name'] ?? 'Tidak terdeteksi',
                        'qty'            => $item['qty'] ?? 1,
                        'price'          => $item['price'],
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }

            if (!empty($data['receipt_path'])) {
                DB::table('receipt_images')->insert([
                    'transaction_id' => $id,
                    'file_path'      => $data['receipt_path'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            $this->activityLog->log($data['chat_id'], 'create_transaction', [
                'transaction_id' => $id,
                'type'           => $data['type'],
                'total'          => $data['total'],
                'category'       => $data['category'],
            ]);

            DB::commit();

            $this->flushCache($data['chat_id']);

            return (object) ['id' => $id];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function totalByRange(string $chatId, string $type, string $start, string $end): int
    {
        $result = DB::selectOne(
            'SELECT COALESCE(SUM(total), 0) AS total FROM transactions WHERE chat_id = ? AND type = ? AND date BETWEEN ? AND ?',
            [$chatId, $type, $start, $end]
        );
        return (int) $result->total;
    }

    public function categoryBreakdown(string $chatId, string $start, string $end): array
    {
        return DB::select(
            'SELECT category, SUM(total) AS sum FROM transactions WHERE chat_id = ? AND type = ? AND date BETWEEN ? AND ? GROUP BY category ORDER BY sum DESC',
            [$chatId, 'expense', $start, $end]
        );
    }

    public function itemBreakdown(string $chatId, string $start, string $end): array
    {
        return DB::select(
            'SELECT ti.name, SUM(ti.price) AS total, SUM(ti.qty) AS qty, COUNT(ti.id) AS times
             FROM transaction_items ti
             JOIN transactions t ON t.id = ti.transaction_id
             WHERE t.chat_id = ? AND t.date BETWEEN ? AND ?
             GROUP BY ti.name
             ORDER BY total DESC',
            [$chatId, $start, $end]
        );
    }

    public function listTransactions(string $chatId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where  = ['t.chat_id = ?'];
        $params = [$chatId];

        if (!empty($filters['type'])) {
            $where[]  = 't.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['category'])) {
            $where[]  = 't.category = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['wallet_id'])) {
            $where[]  = 't.wallet_id = ?';
            $params[] = $filters['wallet_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 't.date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 't.date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(t.merchant LIKE ? OR t.note LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        $params[]    = $limit;
        $params[]    = $offset;

        return DB::select(
            "SELECT t.*, w.name AS wallet_name,
                    (SELECT file_path FROM receipt_images ri WHERE ri.transaction_id = t.id LIMIT 1) AS receipt_path
             FROM transactions t
             LEFT JOIN wallets w ON w.id = t.wallet_id
             WHERE {$whereClause}
             ORDER BY t.date DESC, t.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public function countTransactions(string $chatId, array $filters = []): int
    {
        $where  = ['chat_id = ?'];
        $params = [$chatId];

        if (!empty($filters['type'])) {
            $where[]  = 'type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['category'])) {
            $where[]  = 'category = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['wallet_id'])) {
            $where[]  = 'wallet_id = ?';
            $params[] = $filters['wallet_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(merchant LIKE ? OR note LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        $result      = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM transactions WHERE {$whereClause}",
            $params
        );
        return (int) $result->cnt;
    }

    public function find(int $id): ?object
    {
        $tx = DB::selectOne(
            'SELECT t.*, w.name AS wallet_name FROM transactions t LEFT JOIN wallets w ON w.id = t.wallet_id WHERE t.id = ?',
            [$id]
        );
        if (!$tx) {
            return null;
        }
        $tx->items   = DB::select('SELECT * FROM transaction_items WHERE transaction_id = ?', [$id]);
        $tx->receipt = DB::selectOne('SELECT * FROM receipt_images WHERE transaction_id = ? LIMIT 1', [$id]);
        return $tx;
    }

    public function update(int $id, array $data): void
    {
        $tx = DB::selectOne('SELECT chat_id FROM transactions WHERE id = ?', [$id]);
        if (!$tx) {
            return;
        }

        $fields = [];
        $params = [];
        $allowed = ['total', 'category', 'note', 'merchant', 'date', 'wallet_id', 'type'];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $fields[] = "{$key} = ?";
                $params[] = $data[$key];
            }
        }
        if (empty($fields)) {
            return;
        }

        $fields[]  = 'updated_at = ?';
        $params[]  = now();
        $params[]  = $id;

        DB::statement('UPDATE transactions SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);

        $this->activityLog->log($tx->chat_id, 'edit_transaction', ['transaction_id' => $id, 'changes' => $data]);
        $this->flushCache($tx->chat_id);
    }

    public function deleteById(int $id): void
    {
        $tx = DB::selectOne('SELECT chat_id FROM transactions WHERE id = ?', [$id]);
        if (!$tx) {
            return;
        }

        DB::statement('DELETE FROM transaction_items WHERE transaction_id = ?', [$id]);
        DB::statement('DELETE FROM receipt_images WHERE transaction_id = ?', [$id]);
        DB::statement('DELETE FROM transactions WHERE id = ?', [$id]);

        $this->activityLog->log($tx->chat_id, 'delete_transaction', ['transaction_id' => $id]);
        $this->flushCache($tx->chat_id);
    }

    public function lastTransaction(string $chatId): ?object
    {
        return DB::selectOne(
            'SELECT * FROM transactions WHERE chat_id = ? ORDER BY created_at DESC LIMIT 1',
            [$chatId]
        );
    }

    public function undoLast(string $chatId): string
    {
        $tx = $this->lastTransaction($chatId);
        if (!$tx) {
            return 'not_found';
        }

        $diffMinutes = now()->diffInMinutes(\Carbon\Carbon::parse($tx->created_at), false);
        if ($diffMinutes < -5) {
            return 'expired';
        }

        DB::statement('DELETE FROM transaction_items WHERE transaction_id = ?', [$tx->id]);
        DB::statement('DELETE FROM receipt_images WHERE transaction_id = ?', [$tx->id]);
        DB::statement('DELETE FROM transactions WHERE id = ?', [$tx->id]);

        $this->activityLog->log($chatId, 'undo_transaction', ['transaction_id' => $tx->id]);
        $this->flushCache($chatId);

        return 'ok';
    }

    public function trendLast30Days(string $chatId): array
    {
        return DB::select(
            'SELECT date, SUM(CASE WHEN type = ? THEN total ELSE 0 END) AS expense,
                    SUM(CASE WHEN type = ? THEN total ELSE 0 END) AS income
             FROM transactions
             WHERE chat_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
             GROUP BY date ORDER BY date ASC',
            ['expense', 'income', $chatId]
        );
    }

    public function trendMonthly(string $chatId, int $months = 6): array
    {
        return DB::select(
            'SELECT DATE_FORMAT(date, ?) AS month,
                    SUM(CASE WHEN type = ? THEN total ELSE 0 END) AS expense,
                    SUM(CASE WHEN type = ? THEN total ELSE 0 END) AS income
             FROM transactions
             WHERE chat_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month ORDER BY month ASC',
            ['%Y-%m', 'expense', 'income', $chatId, $months]
        );
    }

    private function flushCache(string $chatId): void
    {
        $keys = ['overview_cards', 'category_breakdown', 'item_breakdown', 'chart_30days', 'chart_monthly'];
        foreach ($keys as $key) {
            Cache::forget("dash:{$chatId}:{$key}");
        }
    }
}
