<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\WalletRepository;
use Carbon\Carbon;

class FinanceService
{
    public function __construct(
        private TransactionRepository $txRepo,
        private SettingsRepository    $settingsRepo,
        private WalletRepository      $walletRepo
    ) {}

    public function buildBudgetStatus(string $chatId): string
    {
        $budgets = $this->settingsRepo->getBudgets($chatId);
        $lines   = [];

        $periods = [
            'harian'   => ['label' => 'Hari ini',   'start' => today_wita(), 'end' => today_wita(), 'budget' => $budgets->daily],
            'mingguan' => ['label' => 'Minggu ini',  'start' => week_start(), 'end' => week_end(),   'budget' => $budgets->weekly],
            'bulanan'  => ['label' => 'Bulan ini',   'start' => month_start(),'end' => month_end(),  'budget' => $budgets->monthly],
        ];

        $sarcasms = [
            "Selamat, kamu resmi bokek! 🎉",
            "Mantap jiwa, dompet nangis duluan.",
            "Budget bukan pajangan, bro.",
            "Uang terbang, kamu ketawa. Elegan.",
            "Nabung? Ngga kenal tuh.",
        ];

        foreach ($periods as $p) {
            if ($p['budget'] <= 0) {
                continue;
            }
            $spent  = $this->txRepo->totalByRange($chatId, 'expense', $p['start'], $p['end']);
            $pct    = round(($spent / $p['budget']) * 100);
            $over   = $spent - $p['budget'];

            if ($pct >= 100) {
                $taunt   = $sarcasms[array_rand($sarcasms)];
                $lines[] = "🚨 <b>{$p['label']}:</b> " . rp($spent) . ' / ' . rp($p['budget']) . " ({$pct}%)\n"
                         . "   ↳ <i>Overbudget " . rp($over) . "! {$taunt}</i>";
            } elseif ($pct >= 80) {
                $lines[] = "⚠️ <b>{$p['label']}:</b> " . rp($spent) . ' / ' . rp($p['budget']) . " ({$pct}%) — sisa " . rp($p['budget'] - $spent);
            } else {
                $lines[] = "✅ <b>{$p['label']}:</b> " . rp($spent) . ' / ' . rp($p['budget']) . " ({$pct}%)";
            }
        }

        return implode("\n", $lines);
    }

    public function buildRecap(string $chatId, string $period = 'mingguan'): string
    {
        ['start' => $start, 'end' => $end, 'label' => $label] = $this->periodRange($period);
        ['start' => $prevStart, 'end' => $prevEnd] = $this->prevPeriodRange($period);

        $expense     = $this->txRepo->totalByRange($chatId, 'expense', $start, $end);
        $income      = $this->txRepo->totalByRange($chatId, 'income',  $start, $end);
        $net         = $income - $expense;
        $prevExpense = $this->txRepo->totalByRange($chatId, 'expense', $prevStart, $prevEnd);

        $lines = ["📊 <b>Rekap {$label}</b>\n"];
        $lines[] = '💸 Pengeluaran: ' . rp($expense);
        $lines[] = '💰 Pemasukan: '   . rp($income);
        $lines[] = '📈 Net: '         . rp($net);

        if ($prevExpense > 0) {
            $diff    = $expense - $prevExpense;
            $diffPct = round(abs($diff / $prevExpense) * 100);
            $arrow   = $diff > 0 ? '⬆️' : '⬇️';
            $lines[] = "{$arrow} vs periode lalu: " . ($diff > 0 ? '+' : '') . rp($diff) . " ({$diffPct}%)";
        }

        $categories = $this->txRepo->categoryBreakdown($chatId, $start, $end);
        if (!empty($categories)) {
            $lines[] = "\n<b>Kategori:</b>";
            foreach ($categories as $cat) {
                $lines[] = "  · {$cat->category}: " . rp((int) $cat->sum);
            }
        }

        $items = $this->txRepo->itemBreakdown($chatId, $start, $end);
        if (!empty($items)) {
            $lines[]  = "\n<b>Top Item:</b>";
            $topItems = array_slice($items, 0, 3);
            foreach ($topItems as $item) {
                $lines[] = "  · {$item->name} — " . rp((int) $item->total) . " ({$item->times}x)";
            }
        }

        return implode("\n", $lines);
    }

    public function buildSisaText(string $chatId, string $period = 'mingguan'): string
    {
        $budgets = $this->settingsRepo->getBudgets($chatId);
        ['start' => $start, 'end' => $end, 'label' => $label] = $this->periodRange($period);

        $budgetMap = [
            'harian'   => $budgets->daily,
            'mingguan' => $budgets->weekly,
            'bulanan'  => $budgets->monthly,
        ];
        $budget = $budgetMap[$period] ?? $budgets->weekly;

        if ($budget <= 0) {
            return "⚠️ Budget {$label} belum diset.\nKirim: <b>budget harian/mingguan/bulanan [nominal]</b>";
        }

        $spent = $this->txRepo->totalByRange($chatId, 'expense', $start, $end);
        $sisa  = $budget - $spent;
        $pct   = round(($spent / $budget) * 100);
        $icon  = $pct >= 100 ? '🚨' : ($pct >= 80 ? '⚠️' : '✅');

        if ($sisa <= 0) {
            $over     = $spent - $budget;
            $sarcasms = [
                "Udah overbudget, masih mau tau sisa? Sisa apanya? 😂",
                "Sisa budget: -" . rp(abs($sisa)) . ". Iya, minus. Serius.",
                "Kamu overbudget " . rp($over) . ". Budget udah minta resign.",
                "Minus " . rp(abs($sisa)) . ". Dompet sudah tidak bisa bicara.",
            ];
            return "🚨 <b>OVERBUDGET {$label}!</b>\n"
                 . "Terpakai: " . rp($spent) . " / " . rp($budget) . " ({$pct}%)\n"
                 . "Lebih " . rp($over) . " dari budget.\n"
                 . "<i>" . $sarcasms[array_rand($sarcasms)] . "</i>";
        }

        return "{$icon} <b>Sisa budget {$label}:</b> " . rp($sisa) . "\nTerpakai: " . rp($spent) . ' / ' . rp($budget) . " ({$pct}%)";
    }

    public function buildSaldoText(string $chatId, ?string $walletName = null): string
    {
        if ($walletName) {
            $wallet = $this->walletRepo->findByName($chatId, $walletName);
            if (!$wallet) {
                return "❌ Dompet <b>{$walletName}</b> tidak ditemukan.";
            }
            $saldo = $this->walletRepo->balance((int) $wallet->id);
            return "💼 <b>{$wallet->name}:</b> " . rp($saldo);
        }

        $wallets = $this->walletRepo->all($chatId);
        if (empty($wallets)) {
            return "💼 Belum ada dompet. Transaksi pertama otomatis buat dompet Cash.";
        }

        $lines = ['💼 <b>Saldo Dompet:</b>'];
        $total = 0;
        foreach ($wallets as $w) {
            $saldo  = $this->walletRepo->balance((int) $w->id);
            $total += $saldo;
            $lines[] = "  · {$w->name}: " . rp($saldo);
        }
        $lines[] = "\n<b>Total:</b> " . rp($total);

        return implode("\n", $lines);
    }

    public function periodRange(string $period): array
    {
        return match ($period) {
            'harian'  => ['start' => today_wita(), 'end' => today_wita(), 'label' => 'Hari Ini'],
            'bulanan' => ['start' => month_start(), 'end' => month_end(), 'label' => 'Bulan Ini'],
            default   => ['start' => week_start(),  'end' => week_end(),  'label' => 'Minggu Ini'],
        };
    }

    private function prevPeriodRange(string $period): array
    {
        $now = Carbon::now('Asia/Makassar');
        return match ($period) {
            'harian'  => [
                'start' => $now->copy()->subDay()->format('Y-m-d'),
                'end'   => $now->copy()->subDay()->format('Y-m-d'),
            ],
            'bulanan' => [
                'start' => $now->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
                'end'   => $now->copy()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
            default   => [
                'start' => $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
                'end'   => $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d'),
            ],
        };
    }
}
