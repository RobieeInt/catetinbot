<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class WalletRepository
{
    public function all(string $chatId): array
    {
        return DB::select('SELECT * FROM wallets WHERE chat_id = ? ORDER BY id ASC', [$chatId]);
    }

    public function create(string $chatId, string $name, int $initialBalance = 0): int
    {
        return DB::table('wallets')->insertGetId([
            'chat_id'         => $chatId,
            'name'            => $name,
            'initial_balance' => $initialBalance,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    public function findByName(string $chatId, string $name): ?object
    {
        return DB::selectOne(
            'SELECT * FROM wallets WHERE chat_id = ? AND LOWER(name) = LOWER(?) LIMIT 1',
            [$chatId, $name]
        );
    }

    public function findById(int $id): ?object
    {
        return DB::selectOne('SELECT * FROM wallets WHERE id = ? LIMIT 1', [$id]);
    }

    public function balance(int $walletId): int
    {
        $wallet = DB::selectOne('SELECT initial_balance FROM wallets WHERE id = ?', [$walletId]);
        if (!$wallet) {
            return 0;
        }

        $income = DB::selectOne(
            'SELECT COALESCE(SUM(total), 0) AS total FROM transactions WHERE wallet_id = ? AND type = ?',
            [$walletId, 'income']
        );
        $expense = DB::selectOne(
            'SELECT COALESCE(SUM(total), 0) AS total FROM transactions WHERE wallet_id = ? AND type = ?',
            [$walletId, 'expense']
        );

        // Masuk dari transfer
        $transferIn = DB::selectOne(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_transfers WHERE to_wallet_id = ?',
            [$walletId]
        );
        // Keluar dari transfer
        $transferOut = DB::selectOne(
            'SELECT COALESCE(SUM(amount), 0) AS total FROM wallet_transfers WHERE from_wallet_id = ?',
            [$walletId]
        );

        return (int) $wallet->initial_balance
            + (int) $income->total
            - (int) $expense->total
            + (int) $transferIn->total
            - (int) $transferOut->total;
    }

    public function deleteById(int $id): void
    {
        DB::statement('DELETE FROM wallets WHERE id = ?', [$id]);
    }

    public function ensureDefaultWallet(string $chatId): object
    {
        $wallet = $this->findByName($chatId, 'Cash');
        if (!$wallet) {
            $id     = $this->create($chatId, 'Cash', 0);
            $wallet = $this->findById($id);
        }
        return $wallet;
    }
}
