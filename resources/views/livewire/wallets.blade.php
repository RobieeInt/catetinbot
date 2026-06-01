<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-gray-800">Dompet</h1>
        <button wire:click="$set('showForm',true)"
                class="flex items-center gap-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl shadow-sm transition-colors">
            + Dompet
        </button>
    </div>

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-4 mb-4 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Wallet cards --}}
    <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($wallets as $w)
        @php $bal = $balances[$w->id] ?? 0; @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-gray-500 mb-1 font-medium uppercase tracking-wide">{{ $w->name }}</p>
                    <p class="text-2xl font-bold {{ $bal<0?'text-red-500':'text-gray-900' }}">{{ rp($bal) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Saldo awal: {{ rp((int)$w->initial_balance) }}</p>
                </div>
                @if($w->name !== 'Cash')
                <button wire:click="deleteWallet({{ $w->id }})"
                        wire:confirm="Hapus dompet {{ $w->name }}? (Hanya bisa jika saldo 0)"
                        class="text-gray-300 hover:text-red-400 transition-colors p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-10 text-gray-400">Belum ada dompet</div>
        @endforelse
    </div>

    {{-- Transfer history --}}
    <h2 class="text-base font-semibold text-gray-700 mb-3">Riwayat Transfer</h2>

    {{-- Mobile cards --}}
    <div class="space-y-2 sm:hidden">
        @forelse($transfers as $t)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-800">{{ $t->from_wallet_name }} → {{ $t->to_wallet_name }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($t->transfer_date)->setTimezone(config('app.timezone'))->format('d M Y, H:i') }}</div>
                    @if($t->note)<div class="text-xs text-gray-500 mt-0.5">{{ $t->note }}</div>@endif
                </div>
                <div class="text-base font-bold text-blue-600">{{ rp((int)$t->amount) }}</div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-center text-gray-400 text-sm">Belum ada transfer</div>
        @endforelse
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Waktu</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Dari</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Ke</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Nominal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($transfers as $t)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ \Carbon\Carbon::parse($t->transfer_date)->setTimezone(config('app.timezone'))->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $t->from_wallet_name }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $t->to_wallet_name }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ rp((int)$t->amount) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $t->note ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada transfer</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Wallet Modal --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
        <div class="absolute inset-0 bg-black/50" wire:click="$set('showForm',false)"></div>
        <div class="relative bg-white w-full sm:max-w-sm rounded-t-3xl sm:rounded-2xl shadow-2xl p-6 z-10">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>
            <h3 class="text-lg font-bold text-gray-800 mb-4">Tambah Dompet</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wide">Nama Dompet</label>
                    <input wire:model="newName" placeholder="Contoh: BCA, GoPay, DANA"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('newName')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5 uppercase tracking-wide">Saldo Awal (Rp)</label>
                    <input wire:model="newBalance" type="number" min="0" placeholder="0"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('newBalance')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button wire:click="$set('showForm',false)"
                        class="flex-1 border border-gray-200 text-gray-600 py-3 rounded-xl font-medium text-sm">Batal</button>
                <button wire:click="addWallet"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-medium text-sm transition-colors">Simpan</button>
            </div>
        </div>
    </div>
    @endif
</div>
