<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Repositories\ActivityLogRepository;

class TransferRepository
{
    public function __construct(
        private ActivityLogRepository $activityLog
    ) {}

    public function create(string $chatId, int $fromWalletId, int $toWalletId, int $amount, ?string $note = null): int
    {
        $id = DB::table('wallet_transfers')->insertGetId([
            'chat_id'        => $chatId,
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id'   => $toWalletId,
            'amount'         => $amount,
            'transfer_date'  => now(),
            'note'           => $note,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->activityLog->log($chatId, 'transfer_wallet', [
            'transfer_id'    => $id,
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id'   => $toWalletId,
            'amount'         => $amount,
        ]);

        return $id;
    }

    public function history(string $chatId): array
    {
        return DB::select(
            'SELECT wt.*,
                    w1.name AS from_wallet_name,
                    w2.name AS to_wallet_name
             FROM wallet_transfers wt
             LEFT JOIN wallets w1 ON w1.id = wt.from_wallet_id
             LEFT JOIN wallets w2 ON w2.id = wt.to_wallet_id
             WHERE wt.chat_id = ?
             ORDER BY wt.transfer_date DESC',
            [$chatId]
        );
    }

    public function deleteById(int $id): void
    {
        DB::statement('DELETE FROM wallet_transfers WHERE id = ?', [$id]);
    }
}
