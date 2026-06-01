<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-gray-800">Tabungan</h1>
        <div class="flex gap-2">
            <button wire:click="$set('showDeposit',true)"
                class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-xl text-sm font-medium">+ Setor</button>
            <button wire:click="$set('showAddGoal',true)"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-xl text-sm font-medium">+ Goal</button>
        </div>
    </div>

    {{-- Goal cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @forelse($goals as $g)
        @php
            $pct = $g->target_amount > 0 ? min(100, round(($g->saved_amount / $g->target_amount) * 100)) : 0;
            $color = $pct >= 100 ? 'bg-green-500' : ($pct >= 60 ? 'bg-blue-500' : 'bg-yellow-400');
        @endphp
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex justify-between items-start mb-3">
                <div class="min-w-0">
                    <h3 class="font-semibold text-gray-800 text-sm truncate">{{ $g->name }}</h3>
                    @if($g->deadline)
                    <p class="text-xs text-gray-400 mt-0.5">Target: {{ \Carbon\Carbon::parse($g->deadline)->format('d M Y') }}</p>
                    @endif
                </div>
                <button wire:click="deleteGoal({{ $g->id }})" wire:confirm="Hapus goal {{ $g->name }}?"
                    class="text-gray-300 hover:text-red-500 text-xl leading-none ml-2 shrink-0">×</button>
            </div>

            <div class="flex justify-between text-sm mb-1.5">
                <span class="font-semibold text-gray-700">{{ rp((int)$g->saved_amount) }}</span>
                <span class="text-gray-400 text-xs">/ {{ rp((int)$g->target_amount) }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                <div class="{{ $color }} h-2.5 rounded-full transition-all" style="width:{{ $pct }}%"></div>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm font-bold {{ $pct >= 100 ? 'text-green-600' : 'text-gray-700' }}">{{ $pct }}%</span>
                <button wire:click="openDeposit('{{ $g->name }}')"
                    class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1 rounded-lg font-medium">+ Setor</button>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12 text-gray-400">
            <div class="text-4xl mb-3">🏦</div>
            <p class="text-sm">Belum ada tujuan tabungan. Tambah goal pertamamu!</p>
        </div>
        @endforelse
    </div>

    {{-- Add Goal Modal --}}
    @if($showAddGoal)
    <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center" wire:click.self="$set('showAddGoal',false)">
        <div class="bg-white w-full sm:max-w-sm sm:mx-4 rounded-t-3xl sm:rounded-2xl shadow-xl p-5 pb-8 sm:pb-5">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>
            <h3 class="text-base font-bold text-gray-800 mb-4">Tambah Goal Tabungan</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Nama Goal</label>
                    <input wire:model="newName" placeholder="Contoh: Laptop, Liburan Bali"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('newName')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Target (Rp)</label>
                    <input wire:model="newTarget" type="number" min="1" inputmode="numeric"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('newTarget')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Deadline (opsional)</label>
                    <input wire:model="newDeadline" type="date"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button wire:click="addGoal"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-medium text-sm">Simpan</button>
                <button wire:click="$set('showAddGoal',false)"
                    class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl hover:bg-gray-50 text-sm">Batal</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Deposit Modal --}}
    @if($showDeposit)
    <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center" wire:click.self="$set('showDeposit',false)">
        <div class="bg-white w-full sm:max-w-sm sm:mx-4 rounded-t-3xl sm:rounded-2xl shadow-xl p-5 pb-8 sm:pb-5">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>
            <h3 class="text-base font-bold text-gray-800 mb-4">Tambah Setoran</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Tujuan Tabungan</label>
                    <select wire:model="depositGoal"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">-- Pilih goal --</option>
                        @foreach($goals as $g)
                        <option value="{{ $g->name }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    @error('depositGoal')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Nominal (Rp)</label>
                    <input wire:model="depositAmount" type="number" min="1" inputmode="numeric"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('depositAmount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button wire:click="addDeposit"
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl font-medium text-sm">Setor</button>
                <button wire:click="$set('showDeposit',false)"
                    class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl hover:bg-gray-50 text-sm">Batal</button>
            </div>
        </div>
    </div>
    @endif
</div>
