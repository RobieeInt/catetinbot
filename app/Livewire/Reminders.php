<?php

namespace App\Livewire;

use App\Repositories\ReminderRepository;
use App\Repositories\ActivityLogRepository;
use Livewire\Component;

class Reminders extends Component
{
    public bool   $showForm   = false;
    public string $newTask    = '';
    public string $newDate    = '';
    public string $newTime    = '08:00';
    public string $newRepeat  = 'none';



    public function mount(): void
    {

    }

    public function addReminder(): void
    {
        $this->validate([
            'newTask' => 'required|string|max:255',
            'newDate' => 'required|date',
            'newTime' => 'required',
        ]);

        $remindAt = $this->newDate . ' ' . $this->newTime . ':00';
        $activityLog = app(ActivityLogRepository::class);
        (new ReminderRepository($activityLog))->create($this->chatId(), $this->newTask, $remindAt, $this->newRepeat);

        $this->reset(['newTask', 'newDate']);
        $this->newTime   = '08:00';
        $this->newRepeat = 'none';
        $this->showForm  = false;
    }

    public function deactivate(int $id): void
    {
        \Illuminate\Support\Facades\DB::statement(
            'UPDATE reminders SET active = 0, updated_at = ? WHERE id = ?', [now(), $id]
        );
    }

    public function deleteReminder(int $id): void
    {
        $activityLog = app(ActivityLogRepository::class);
        (new ReminderRepository($activityLog))->deleteById($id);
    }

    public function render(): \Illuminate\View\View
    {
        $activityLog = app(ActivityLogRepository::class);
        $repo        = new ReminderRepository($activityLog);
        $all         = $repo->allForOwner($this->chatId());

        $active = array_filter($all, fn($r) => $r->active && !$r->notified);
        $past   = array_filter($all, fn($r) => !$r->active || $r->notified);

        return view('livewire.reminders', [
            'activeList' => array_values($active),
            'pastList'   => array_values($past),
        ])->layout('layouts.dashboard');
    }

    private function chatId(): string
    {
        return (string) env('OWNER_CHAT_ID', '');
    }

}
