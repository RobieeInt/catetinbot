<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-gray-800">Transaksi</h1>
        <button wire:click="exportCsv"
                class="flex items-center gap-1.5 text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-xl shadow-sm transition-colors">
            <span>⬇</span><span class="hidden sm:inline">Export CSV</span>
        </button>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            <input wire:model.live.debounce.400ms="search"
                   placeholder="🔍 Cari..."
                   class="col-span-2 sm:col-span-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-gray-50">
            <select wire:model.live="typeFilter" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Semua tipe</option>
                <option value="expense">Pengeluaran</option>
                <option value="income">Pemasukan</option>
            </select>
            <select wire:model.live="category" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Semua kategori</option>
                @foreach($this->categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
            <select wire:model.live="walletId" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Semua dompet</option>
                @foreach($wallets as $w)
                <option value="{{ $w->id }}">{{ $w->name }}</option>
                @endforeach
            </select>
            <input wire:model.live="dateFrom" type="date" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <input wire:model.live="dateTo"   type="date" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
    </div>

    {{-- Mobile: card list --}}
    <div class="space-y-2 sm:hidden">
        @forelse($transactions as $tx)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $tx->type==='income'?'bg-green-100 text-green-700':'bg-red-100 text-red-600' }}">
                            {{ $tx->type==='income'?'Masuk':'Keluar' }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $tx->date }}</span>
                    </div>
                    <div class="font-semibold text-gray-800 truncate">{{ $tx->merchant ?: ($tx->note ?: '-') }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $tx->category }} @if($tx->wallet_name)· {{ $tx->wallet_name }}@endif</div>
                </div>
                <div class="shrink-0 text-right">
                    <div class="font-bold text-base {{ $tx->type==='income'?'text-green-600':'text-red-500' }}">
                        {{ $tx->type==='income'?'+':'-' }}{{ rp((int)$tx->total) }}
                    </div>
                </div>
            </div>
            <div class="flex gap-3 mt-3 pt-3 border-t border-gray-50">
                <button wire:click="openEdit({{ $tx->id }})" class="text-xs text-blue-600 font-medium">Edit</button>
                @if($tx->receipt_path)
                <button wire:click="viewReceipt({{ $tx->id }})" class="text-xs text-purple-600 font-medium">Lihat Struk</button>
                @endif
                <button wire:click="deleteTransaction({{ $tx->id }})" wire:confirm="Hapus transaksi ini?"
                        class="text-xs text-red-500 font-medium ml-auto">Hapus</button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center text-gray-400">
            <div class="text-3xl mb-2">📭</div>
            Tidak ada transaksi
        </div>
        @endforelse
    </div>

    {{-- Desktop: table --}}
    <div class="hidden sm:block bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tanggal</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Keterangan</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Kategori</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Dompet</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-xs">{{ $tx->date }}</td>
                        <td class="px-4 py-3 max-w-[200px]">
                            @if($tx->merchant)<div class="font-medium text-gray-800 truncate">{{ $tx->merchant }}</div>@endif
                            <div class="text-xs text-gray-400 truncate">{{ $tx->note ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">{{ $tx->category }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $tx->wallet_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold whitespace-nowrap {{ $tx->type==='income'?'text-green-600':'text-red-500' }}">
                            {{ $tx->type==='income'?'+':'-' }}{{ rp((int)$tx->total) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-3">
                                <button wire:click="openEdit({{ $tx->id }})" class="text-xs text-blue-600 hover:underline">Edit</button>
                                @if($tx->receipt_path)
                                <button wire:click="viewReceipt({{ $tx->id }})" class="text-xs text-purple-600 hover:underline">Struk</button>
                                @endif
                                <button wire:click="deleteTransaction({{ $tx->id }})" wire:confirm="Hapus transaksi ini?"
                                        class="text-xs text-red-500 hover:underline">Hapus</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">
                        <div class="text-3xl mb-2">📭</div>Tidak ada transaksi
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($lastPage > 1)
    <div class="flex items-center justify-between mt-4 px-1">
        <span class="text-sm text-gray-500">{{ $total }} transaksi</span>
        <div class="flex items-center gap-2">
            <button wire:click="prevPage"
                    @disabled($page <= 1)
                    class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-sm disabled:opacity-40 hover:bg-gray-50 transition-colors">←</button>
            <span class="text-sm text-gray-600 min-w-[60px] text-center">{{ $page }} / {{ $lastPage }}</span>
            <button wire:click="nextPage({{ $lastPage }})"
                    @disabled($page >= $lastPage)
                    class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-sm disabled:opacity-40 hover:bg-gray-50 transition-colors">→</button>
        </div>
    </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-black/50" wire:click="$set('showEditModal',false)"></div>
        <div class="relative bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl shadow-2xl p-6 z-10">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>
            <h3 class="text-lg font-bold text-gray-800 mb-4">Edit Transaksi</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wide">Total (Rp)</label>
                    <input wire:model="editTotal" type="number" min="1"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-blue-400 bg-gray-50">
                    @error('editTotal')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wide">Kategori</label>
                    <select wire:model="editCategory"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-base bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        @foreach($this->categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wide">Catatan</label>
                    <input wire:model="editNote" type="text"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-base bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button wire:click="$set('showEditModal',false)"
                        class="flex-1 border border-gray-200 text-gray-600 py-3 rounded-xl font-medium text-sm">Batal</button>
                <button wire:click="saveEdit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-medium text-sm transition-colors">Simpan</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Receipt Modal --}}
    @if($showReceiptModal)
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-black/70" wire:click="$set('showReceiptModal',false)"></div>
        <div class="relative bg-white w-full sm:max-w-lg rounded-t-3xl sm:rounded-2xl shadow-2xl p-5 z-10">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-4 sm:hidden"></div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-800">Foto Struk</h3>
                <button wire:click="$set('showReceiptModal',false)" class="text-gray-400 hover:text-gray-700 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">✕</button>
            </div>
            <img src="{{ $receiptUrl }}" alt="Struk" class="w-full rounded-xl mb-4 max-h-[60vh] object-contain bg-gray-100">
            <a href="{{ $receiptUrl }}" download
               class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-sm font-medium transition-colors">
                ⬇ Download Struk
            </a>
        </div>
    </div>
    @endif
</div>
