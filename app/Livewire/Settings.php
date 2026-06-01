<?php

namespace App\Livewire;

use App\Repositories\SettingsRepository;
use Livewire\Component;

class Settings extends Component
{
    public string $daily   = '0';
    public string $weekly  = '0';
    public string $monthly = '0';
    public bool   $saved   = false;



    public function mount(): void
    {
        $budgets       = app(SettingsRepository::class)->getBudgets($this->chatId());
        $this->daily   = (string) $budgets->daily;
        $this->weekly  = (string) $budgets->weekly;
        $this->monthly = (string) $budgets->monthly;
    }

    public function save(): void
    {
        $this->validate([
            'daily'   => 'required|integer|min:0',
            'weekly'  => 'required|integer|min:0',
            'monthly' => 'required|integer|min:0',
        ]);

        $repo = app(SettingsRepository::class);
        $repo->setBudget($this->chatId(), 'harian',   (int) $this->daily);
        $repo->setBudget($this->chatId(), 'mingguan', (int) $this->weekly);
        $repo->setBudget($this->chatId(), 'bulanan',  (int) $this->monthly);

        $this->saved = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings')->layout('layouts.dashboard');
    }

    private function chatId(): string
    {
        return (string) config('services.telegram.owner_chat_id');
    }

}
