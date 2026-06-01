<?php

namespace App\Livewire;

use App\Repositories\TransactionRepository;
use App\Repositories\ActivityLogRepository;
use Livewire\Component;

class ItemBreakdown extends Component
{
    public string $period   = 'mingguan';
    public string $dateFrom = '';
    public string $dateTo   = '';
    public string $search   = '';
    public string $sortBy   = 'total';



    public function mount(): void
    {

    }

    public function render(): \Illuminate\View\View
    {
        [$start, $end] = $this->resolveRange();

        $items = app(TransactionRepository::class)->itemBreakdown($this->chatId(), $start, $end);

        if ($this->search) {
            $q     = strtolower($this->search);
            $items = array_filter($items, fn($i) => str_contains(strtolower($i->name), $q));
        }

        usort($items, fn($a, $b) => $this->sortBy === 'qty'
            ? (int) $b->qty <=> (int) $a->qty
            : (int) $b->total <=> (int) $a->total);

        return view('livewire.item-breakdown', ['items' => array_values($items), 'start' => $start, 'end' => $end])
            ->layout('layouts.dashboard');
    }

    private function resolveRange(): array
    {
        if ($this->period === 'custom' && $this->dateFrom && $this->dateTo) {
            return [$this->dateFrom, $this->dateTo];
        }
        return match ($this->period) {
            'harian'  => [today_wita(), today_wita()],
            'bulanan' => [month_start(), month_end()],
            default   => [week_start(), week_end()],
        };
    }

    private function chatId(): string
    {
        return (string) config('services.telegram.owner_chat_id');
    }

}
