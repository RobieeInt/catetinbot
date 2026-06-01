<?php

namespace App\Livewire;

use App\Repositories\SubscriptionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\ActivityLogRepository;
use Livewire\Component;

class Subscriptions extends Component
{
    public string $newName     = '';
    public string $newAmount   = '';
    public string $newCategory = 'Hiburan';
    public string $newDay      = '';
    public string $newWallet   = '';
    public bool   $showForm    = false;

    public array $categories = [
        'Makanan & Minuman', 'Belanja Harian', 'Transport',
        'Tagihan', 'Kesehatan', 'Hiburan', 'Lainnya',
    ];



    public function mount(): void
    {

    }

    public function addSubscription(): void
    {
        $this->validate([
            'newName'   => 'required|string|max:100',
            'newAmount' => 'required|integer|min:1',
            'newDay'    => 'required|integer|min:1|max:31',
        ]);

        $walletId = null;
        if ($this->newWallet) {
            $wallet   = app(WalletRepository::class)->findByName($this->chatId(), $this->newWallet);
            $walletId = $wallet?->id;
        }

        $activityLog = app(ActivityLogRepository::class);
        (new SubscriptionRepository($activityLog))->create(
            $this->chatId(), $this->newName, (int) $this->newAmount,
            $this->newCategory, $walletId, (int) $this->newDay
        );

        $this->reset(['newName', 'newAmount', 'newDay', 'newWallet']);
        $this->newCategory = 'Hiburan';
        $this->showForm    = false;
    }

    public function toggleActive(int $id, bool $active): void
    {
        $activityLog = app(ActivityLogRepository::class);
        (new SubscriptionRepository($activityLog))->toggle($id, !$active);
    }

    public function deleteSubscription(int $id): void
    {
        $activityLog = app(ActivityLogRepository::class);
        (new SubscriptionRepository($activityLog))->deleteById($id);
    }

    public function render(): \Illuminate\View\View
    {
        $activityLog   = app(ActivityLogRepository::class);
        $subscriptions = (new SubscriptionRepository($activityLog))->all($this->chatId());
        $wallets       = app(WalletRepository::class)->all($this->chatId());

        return view('livewire.subscriptions', compact('subscriptions', 'wallets'))
            ->layout('layouts.dashboard');
    }

    private function chatId(): string
    {
        return (string) config('services.telegram.owner_chat_id');
    }

}
