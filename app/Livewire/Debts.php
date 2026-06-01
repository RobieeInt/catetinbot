<?php

namespace App\Livewire;

use App\Repositories\DebtRepository;
use Livewire\Component;

class Debts extends Component
{
    public string $activeTab   = 'utang';
    public bool   $showForm    = false;
    public string $newType     = 'utang';
    public string $newPerson   = '';
    public string $newAmount   = '';
    public string $newNote     = '';
    public string $newDueDate  = '';



    public function mount(): void
    {

    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->newType   = $tab;
    }

    public function addDebt(): void
    {
        $this->validate([
            'newPerson' => 'required|string|max:100',
            'newAmount' => 'required|integer|min:1',
            'newType'   => 'required|in:utang,piutang',
        ]);

        app(DebtRepository::class)->create(
            $this->chatId(),
            $this->newType,
            $this->newPerson,
            (int) $this->newAmount,
            $this->newNote ?: null,
            $this->newDueDate ?: null
        );

        $this->reset(['newPerson', 'newAmount', 'newNote', 'newDueDate']);
        $this->showForm = false;
    }

    public function settle(string $type, string $person): void
    {
        app(DebtRepository::class)->settleByPerson($this->chatId(), $type, $person);
    }

    public function deleteDebt(int $id): void
    {
        app(DebtRepository::class)->deleteById($id);
    }

    public function render(): \Illuminate\View\View
    {
        $debtRepo = app(DebtRepository::class);
        $all      = $debtRepo->all($this->chatId());

        $utang    = array_filter($all, fn($d) => $d->type === 'utang');
        $piutang  = array_filter($all, fn($d) => $d->type === 'piutang');

        return view('livewire.debts', [
            'utangList'   => array_values($utang),
            'piutangList' => array_values($piutang),
        ])->layout('layouts.dashboard');
    }

    private function chatId(): string
    {
        return (string) config('services.telegram.owner_chat_id');
    }

}
