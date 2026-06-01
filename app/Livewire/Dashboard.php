<?php

namespace App\Livewire;

use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\ReminderRepository;
use App\Repositories\DebtRepository;
use App\Repositories\ActivityLogRepository;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{


    public function mount(): void
    {

    }

    public function render(): \Illuminate\View\View
    {
        $chatId = $this->chatId();

        $overview = Cache::remember("dash:{$chatId}:overview_cards", 300, function () use ($chatId) {
            return $this->buildOverview($chatId);
        });

        $chart30 = Cache::remember("dash:{$chatId}:chart_30days", 300, function () use ($chatId) {
            return json_decode(json_encode(app(TransactionRepository::class)->trendLast30Days($chatId)), true);
        });

        $chartMonthly = Cache::remember("dash:{$chatId}:chart_monthly", 300, function () use ($chatId) {
            return json_decode(json_encode(app(TransactionRepository::class)->trendMonthly($chatId, 6)), true);
        });

        $categoryBreakdown = Cache::remember("dash:{$chatId}:category_breakdown", 300, function () use ($chatId) {
            return json_decode(json_encode(app(TransactionRepository::class)->categoryBreakdown($chatId, week_start(), week_end())), true);
        });

        $wallets   = app(WalletRepository::class)->all($chatId);
        $walletRepo = app(WalletRepository::class);
        $walletBalances = [];
        foreach ($wallets as $w) {
            $walletBalances[$w->id] = $walletRepo->balance((int) $w->id);
        }

        $reminders  = app(ReminderRepository::class)->pending($chatId);
        $debtRepo   = app(DebtRepository::class);
        $openDebts  = $debtRepo->open($chatId);

        return view('livewire.dashboard', compact(
            'overview', 'chart30', 'chartMonthly', 'categoryBreakdown',
            'wallets', 'walletBalances', 'reminders', 'openDebts'
        ))->layout('layouts.dashboard');
    }

    private function buildOverview(string $chatId): array
    {
        $txRepo       = app(TransactionRepository::class);
        $settingsRepo = app(SettingsRepository::class);
        $budgets      = $settingsRepo->getBudgets($chatId);

        $today = today_wita();

        $data = [];
        $periods = [
            ['key' => 'daily',   'label' => 'Hari Ini',   'start' => $today,        'end' => $today,        'budget' => $budgets->daily],
            ['key' => 'weekly',  'label' => 'Minggu Ini', 'start' => week_start(),  'end' => week_end(),    'budget' => $budgets->weekly],
            ['key' => 'monthly', 'label' => 'Bulan Ini',  'start' => month_start(), 'end' => month_end(),   'budget' => $budgets->monthly],
        ];

        foreach ($periods as $p) {
            $expense = $txRepo->totalByRange($chatId, 'expense', $p['start'], $p['end']);
            $income  = $txRepo->totalByRange($chatId, 'income',  $p['start'], $p['end']);
            $pct     = $p['budget'] > 0 ? min(100, round(($expense / $p['budget']) * 100)) : 0;
            $status  = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'ok');

            $data[$p['key']] = [
                'label'   => $p['label'],
                'expense' => $expense,
                'income'  => $income,
                'net'     => $income - $expense,
                'budget'  => $p['budget'],
                'pct'     => $pct,
                'status'  => $status,
            ];
        }

        return $data;
    }

    private function chatId(): string
    {
        return (string) config('services.telegram.owner_chat_id');
    }

}
