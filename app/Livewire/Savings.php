<?php

namespace App\Livewire;

use App\Repositories\SavingsRepository;
use Livewire\Component;

class Savings extends Component
{
    public bool   $showAddGoal   = false;
    public bool   $showDeposit   = false;
    public string $newName       = '';
    public string $newTarget     = '';
    public string $newDeadline   = '';
    public string $depositGoal   = '';
    public string $depositAmount = '';



    public function mount(): void
    {

    }

    public function addGoal(): void
    {
        $this->validate([
            'newName'   => 'required|string|max:100',
            'newTarget' => 'required|integer|min:1',
        ]);
        app(SavingsRepository::class)->create(
            $this->chatId(), $this->newName, (int) $this->newTarget,
            $this->newDeadline ?: null
        );
        $this->reset(['newName', 'newTarget', 'newDeadline']);
        $this->showAddGoal = false;
    }

    public function addDeposit(): void
    {
        $this->validate([
            'depositGoal'   => 'required|string',
            'depositAmount' => 'required|integer|min:1',
        ]);
        app(SavingsRepository::class)->addProgress($this->chatId(), $this->depositGoal, (int) $this->depositAmount);
        $this->reset(['depositGoal', 'depositAmount']);
        $this->showDeposit = false;
    }

    public function openDeposit(string $name): void
    {
        $this->depositGoal = $name;
        $this->showDeposit = true;
    }

    public function deleteGoal(int $id): void
    {
        app(SavingsRepository::class)->deleteById($id);
    }

    public function render(): \Illuminate\View\View
    {
        $goals = app(SavingsRepository::class)->all($this->chatId());
        return view('livewire.savings', compact('goals'))->layout('layouts.dashboard');
    }

    private function chatId(): string
    {
        return (string) config('services.telegram.owner_chat_id');
    }

}
