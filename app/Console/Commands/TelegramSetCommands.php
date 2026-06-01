<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetCommands extends Command
{
    protected $signature   = 'telegram:set-commands';
    protected $description = 'Set Telegram bot command list';

    public function handle(TelegramService $telegram): void
    {
        $commands = [
            ['command' => 'start',        'description' => 'Mulai & lihat panduan'],
            ['command' => 'help',         'description' => 'Panduan penggunaan'],
            ['command' => 'rekap',        'description' => 'Rekap keuangan minggu ini'],
            ['command' => 'rekap_bulanan','description' => 'Rekap keuangan bulan ini'],
            ['command' => 'sisa',         'description' => 'Sisa budget minggu ini'],
            ['command' => 'saldo',        'description' => 'Cek saldo semua dompet'],
            ['command' => 'undo',         'description' => 'Batalkan transaksi terakhir (maks 5 menit)'],
        ];

        $result = $telegram->setCommands($commands);

        if ($result['ok'] ?? false) {
            $this->info('✅ Bot commands berhasil diset!');
        } else {
            $this->error('❌ Gagal: ' . ($result['description'] ?? 'Unknown error'));
        }
    }
}
