<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-gray-800">Utang & Piutang</h1>
        <button wire:click="$set('showForm',true)"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium">+ Tambah</button>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-4">
        <button wire:click="setTab('utang')"
            class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-medium transition-colors {{ $activeTab === 'utang' ? 'bg-red-500 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
            💳 Utang ({{ count($utangList) }})
        </button>
        <button wire:click="setTab('piutang')"
            class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-sm font-medium transition-colors {{ $activeTab === 'piutang' ? 'bg-green-500 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
            🤝 Piutang ({{ count($piutangList) }})
        </button>
    </div>

    @php $list = $activeTab === 'utang' ? $utangList : $piutangList; @endphp

    {{-- Mobile: card list --}}
    <div class="sm:hidden space-y-2">
        @forelse($list as $d)
        @php $due = $d->due_date ? \Carbon\Carbon::parse($d->due_date) : null; @endphp
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 {{ $d->settled ? 'opacity-50' : '' }}">
            <div class="flex justify-between items-start">
                <div class="min-w-0">
                    <span class="font-semibold text-gray-800 text-sm block">{{ $d->person }}</span>
                    @if($d->note)
                    <span class="text-xs text-gray-500 block truncate">{{ $d->note }}</span>
                    @endif
                    @if($due)
                    <span class="text-xs {{ !$d->settled && $due->isPast() ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                        Jatuh tempo: {{ $due->format('d M Y') }}
                    </span>
                    @endif
                </div>
                <div class="text-right ml-3 shrink-0">
                    <span class="text-sm font-bold {{ $d->type === 'utang' ? 'text-red-600' : 'text-green-600' }} block">{{ rp((int)$d->amount) }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $d->settled ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $d->settled ? 'Lunas' : 'Belum' }}
                    </span>
                </div>
            </div>
            @if(!$d->settled || true)
            <div class="flex gap-3 mt-3 pt-3 border-t border-gray-100">
                @if(!$d->settled)
                <button wire:click="settle('{{ $d->type }}','{{ $d->person }}')"
                    class="text-xs text-green-600 font-medium">Tandai Lunas</button>
                @endif
                <button wire:click="deleteDebt({{ $d->id }})" wire:confirm="Hapus data ini?"
                    class="text-xs text-red-500 font-medium ml-auto">Hapus</button>
            </div>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center text-gray-400 text-sm">
            Tidak ada data {{ $activeTab }}.
        </div>
        @endforelse
    </div>

    {{-- Desktop: table --}}
    <div class="hidden sm:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-gray-600 font-medium">Orang</th>
                    <th class="text-right px-4 py-3 text-gray-600 font-medium">Nominal</th>
                    <th class="text-left px-4 py-3 text-gray-600 font-medium">Catatan</th>
                    <th class="text-center px-4 py-3 text-gray-600 font-medium">Jatuh Tempo</th>
                    <th class="text-center px-4 py-3 text-gray-600 font-medium">Status</th>
                    <th class="text-center px-4 py-3 text-gray-600 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($list as $d)
                @php $due = $d->due_date ? \Carbon\Carbon::parse($d->due_date) : null; @endphp
                <tr class="hover:bg-gray-50 {{ $d->settled ? 'opacity-50' : '' }}">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $d->person }}</td>
                    <td class="px-4 py-3 text-right font-medium {{ $d->type === 'utang' ? 'text-red-600' : 'text-green-600' }}">{{ rp((int)$d->amount) }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $d->note ?? '-' }}</td>
                    <td class="px-4 py-3 text-center text-gray-500">
                        @if($due)
                        <span class="{{ !$d->settled && $due->isPast() ? 'text-red-600 font-medium' : '' }}">
                            {{ $due->format('d M Y') }}
                        </span>
                        @else -
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $d->settled ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $d->settled ? 'Lunas' : 'Belum' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 justify-center">
                            @if(!$d->settled)
                            <button wire:click="settle('{{ $d->type }}','{{ $d->person }}')"
                                class="text-xs text-green-600 hover:text-green-800">Lunas</button>
                            @endif
                            <button wire:click="deleteDebt({{ $d->id }})" wire:confirm="Hapus data ini?"
                                class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Tidak ada data {{ $activeTab }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Bottom-sheet modal --}}
    @if($showForm)
    <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center" wire:click.self="$set('showForm',false)">
        <div class="bg-white w-full sm:max-w-sm sm:mx-4 rounded-t-3xl sm:rounded-2xl shadow-xl p-5 pb-8 sm:pb-5">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>
            <h3 class="text-base font-bold text-gray-800 mb-4">Tambah Utang/Piutang</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Tipe</label>
                    <select wire:model="newType"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="utang">Utang (aku yang ngutang)</option>
                        <option value="piutang">Piutang (orang ngutang ke aku)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Nama Orang</label>
                    <input wire:model="newPerson"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('newPerson')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Nominal (Rp)</label>
                    <input wire:model="newAmount" type="number" min="1" inputmode="numeric"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('newAmount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Catatan</label>
                    <input wire:model="newNote"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Jatuh Tempo (opsional)</label>
                    <input wire:model="newDueDate" type="date"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button wire:click="addDebt"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-medium text-sm">Simpan</button>
                <button wire:click="$set('showForm',false)"
                    class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl hover:bg-gray-50 text-sm">Batal</button>
            </div>
        </div>
    </div>
    @endif
</div>
