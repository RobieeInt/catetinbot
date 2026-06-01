<?php

namespace App\Livewire;

use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\ActivityLogRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpensesTable extends Component
{
    public string $search     = '';
    public string $typeFilter = '';
    public string $category   = '';
    public string $walletId   = '';
    public string $dateFrom   = '';
    public string $dateTo     = '';
    public int    $page       = 1;
    public int    $perPage    = 20;

    public bool   $showEditModal    = false;
    public bool   $showReceiptModal = false;
    public int    $editId           = 0;
    public string $editTotal        = '';
    public string $editCategory     = '';
    public string $editNote         = '';
    public string $receiptUrl       = '';

    public array $categories = [
        'Makanan & Minuman', 'Belanja Harian', 'Transport',
        'Tagihan', 'Kesehatan', 'Hiburan', 'Lainnya',
    ];



    public function mount(): void
    {

    }

    public function updatingSearch(): void     { $this->page = 1; }
    public function updatingTypeFilter(): void { $this->page = 1; }
    public function updatingCategory(): void   { $this->page = 1; }
    public function updatingWalletId(): void   { $this->page = 1; }
    public function updatingDateFrom(): void   { $this->page = 1; }
    public function updatingDateTo(): void     { $this->page = 1; }

    public function nextPage(int $lastPage): void
    {
        if ($this->page < $lastPage) $this->page++;
    }

    public function prevPage(): void
    {
        if ($this->page > 1) $this->page--;
    }

    public function openEdit(int $id): void
    {
        $tx = $this->makeTxRepo()->find($id);
        if (!$tx) return;
        $this->editId        = $id;
        $this->editTotal     = (string) $tx->total;
        $this->editCategory  = $tx->category;
        $this->editNote      = $tx->note ?? '';
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editTotal'    => 'required|integer|min:1',
            'editCategory' => 'required|string',
        ]);
        $this->makeTxRepo()->update($this->editId, [
            'total'    => (int) $this->editTotal,
            'category' => $this->editCategory,
            'note'     => $this->editNote,
        ]);
        $this->flushCache();
        $this->showEditModal = false;
    }

    public function deleteTransaction(int $id): void
    {
        $this->makeTxRepo()->deleteById($id);
        $this->flushCache();
    }

    public function viewReceipt(int $id): void
    {
        $tx = $this->makeTxRepo()->find($id);
        if (!$tx || !$tx->receipt) return;
        $this->receiptUrl       = Storage::url($tx->receipt->file_path);
        $this->showReceiptModal = true;
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = $this->makeTxRepo()->listTransactions($this->chatId(), $this->buildFilters(), 10000, 0);
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tanggal', 'Merchant', 'Catatan', 'Kategori', 'Dompet', 'Tipe', 'Total']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->date, $r->merchant ?? '', $r->note ?? '', $r->category, $r->wallet_name ?? '', $r->type, $r->total]);
            }
            fclose($out);
        }, 'transactions_' . now()->format('Ymd_His') . '.csv');
    }

    public function render(): \Illuminate\View\View
    {
        $filters     = $this->buildFilters();
        $txRepo      = $this->makeTxRepo();
        $offset      = ($this->page - 1) * $this->perPage;
        $total       = $txRepo->countTransactions($this->chatId(), $filters);
        $transactions = $txRepo->listTransactions($this->chatId(), $filters, $this->perPage, $offset);
        $wallets     = app(WalletRepository::class)->all($this->chatId());
        $lastPage    = max(1, (int) ceil($total / $this->perPage));

        return view('livewire.expenses-table', compact(
            'transactions', 'wallets', 'total', 'lastPage'
        ))->layout('layouts.dashboard');
    }

    private function buildFilters(): array
    {
        $f = [];
        if ($this->search)     $f['search']   = $this->search;
        if ($this->typeFilter) $f['type']      = $this->typeFilter;
        if ($this->category)   $f['category']  = $this->category;
        if ($this->walletId)   $f['wallet_id'] = $this->walletId;
        if ($this->dateFrom)   $f['date_from'] = $this->dateFrom;
        if ($this->dateTo)     $f['date_to']   = $this->dateTo;
        return $f;
    }

    private function makeTxRepo(): TransactionRepository
    {
        return new TransactionRepository(app(ActivityLogRepository::class));
    }

    private function flushCache(): void
    {
        foreach (['overview_cards', 'category_breakdown', 'item_breakdown', 'chart_30days', 'chart_monthly'] as $k) {
            Cache::forget("dash:{$this->chatId()}:{$k}");
        }
    }

    private function chatId(): string
    {
        return (string) env('OWNER_CHAT_ID', '');
    }

}
