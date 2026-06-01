<?php

namespace App\Livewire;

use App\Repositories\WalletRepository;
use App\Repositories\TransferRepository;
use App\Repositories\ActivityLogRepository;
use Livewire\Component;

class Wallets extends Component
{
    public string $newName    = '';
    public string $newBalance = '0';
    public bool   $showForm   = false;



    public function mount(): void
    {

    }

    public function addWallet(): void
    {
        $this->validate([
            'newName'    => 'required|string|max:100',
            'newBalance' => 'required|integer|min:0',
        ]);
        app(WalletRepository::class)->create($this->chatId(), $this->newName, (int) $this->newBalance);
        $this->newName    = '';
        $this->newBalance = '0';
        $this->showForm   = false;
    }

    public function deleteWallet(int $id): void
    {
        $walletRepo = app(WalletRepository::class);
        $saldo = $walletRepo->balance($id);
        if ($saldo !== 0) {
            session()->flash('error', 'Hanya dompet dengan saldo 0 yang bisa dihapus.');
            return;
        }
        $walletRepo->deleteById($id);
    }

    public function render(): \Illuminate\View\View
    {
        $walletRepo = app(WalletRepository::class);
        $wallets    = $walletRepo->all($this->chatId());
        $balances   = [];
        foreach ($wallets as $w) {
            $balances[$w->id] = $walletRepo->balance((int) $w->id);
        }

        $activityLog = app(ActivityLogRepository::class);
        $transfers   = (new TransferRepository($activityLog))->history($this->chatId());

        return view('livewire.wallets', compact('wallets', 'balances', 'transfers'))
            ->layout('layouts.dashboard');
    }

    private function chatId(): string
    {
        return (string) config('services.telegram.owner_chat_id');
    }

}
