<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-gray-800">Langganan</h1>
        <button wire:click="$set('showForm',true)"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium">+ Tambah</button>
    </div>

    {{-- Mobile: card list --}}
    <div class="sm:hidden space-y-2">
        @forelse($subscriptions as $s)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 {{ !$s->active ? 'opacity-50' : '' }}">
            <div class="flex justify-between items-start mb-1">
                <div class="min-w-0">
                    <span class="font-semibold text-gray-800 text-sm block truncate">{{ $s->name }}</span>
                    <span class="text-xs text-gray-500">{{ $s->category }} · Tgl {{ $s->day_of_month }}</span>
                </div>
                <span class="text-sm font-bold text-red-600 ml-2 shrink-0">{{ rp((int)$s->amount) }}</span>
            </div>
            <div class="flex items-center justify-between mt-2">
                <span class="px-2 py-0.5 rounded-full text-xs {{ $s->active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $s->active ? 'Aktif' : 'Nonaktif' }}
                </span>
                <div class="flex gap-3">
                    <button wire:click="toggleActive({{ $s->id }},{{ $s->active ? 'true' : 'false' }})"
                        class="text-xs {{ $s->active ? 'text-yellow-600' : 'text-green-600' }} font-medium">
                        {{ $s->active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                    <button wire:click="deleteSubscription({{ $s->id }})" wire:confirm="Hapus langganan {{ $s->name }}?"
                        class="text-xs text-red-500 font-medium">Hapus</button>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center text-gray-400 text-sm">
            Belum ada langganan.
        </div>
        @endforelse
    </div>

    {{-- Desktop: table --}}
    <div class="hidden sm:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-gray-600 font-medium">Nama</th>
                    <th class="text-left px-4 py-3 text-gray-600 font-medium">Kategori</th>
                    <th class="text-right px-4 py-3 text-gray-600 font-medium">Nominal</th>
                    <th class="text-center px-4 py-3 text-gray-600 font-medium">Tgl Tagih</th>
                    <th class="text-center px-4 py-3 text-gray-600 font-medium">Status</th>
                    <th class="text-center px-4 py-3 text-gray-600 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subscriptions as $s)
                <tr class="hover:bg-gray-50 {{ !$s->active ? 'opacity-50' : '' }}">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $s->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $s->category }}</td>
                    <td class="px-4 py-3 text-right font-medium text-red-600">{{ rp((int)$s->amount) }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">Tgl {{ $s->day_of_month }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $s->active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $s->active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 justify-center">
                            <button wire:click="toggleActive({{ $s->id }},{{ $s->active ? 'true' : 'false' }})"
                                class="text-xs {{ $s->active ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $s->active ? 'Nonaktif' : 'Aktif' }}kan
                            </button>
                            <button wire:click="deleteSubscription({{ $s->id }})" wire:confirm="Hapus langganan {{ $s->name }}?"
                                class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Belum ada langganan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Bottom-sheet modal --}}
    @if($showForm)
    <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center" wire:click.self="$set('showForm',false)">
        <div class="bg-white w-full sm:max-w-sm sm:mx-4 rounded-t-3xl sm:rounded-2xl shadow-xl p-5 pb-8 sm:pb-5">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>
            <h3 class="text-base font-bold text-gray-800 mb-4">Tambah Langganan</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Nama</label>
                    <input wire:model="newName" placeholder="Contoh: Netflix, Spotify"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('newName')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Nominal (Rp)</label>
                    <input wire:model="newAmount" type="number" min="1" inputmode="numeric"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('newAmount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Kategori</label>
                        <select wire:model="newCategory"
                            class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            @foreach($this->categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Tgl Tagih (1-31)</label>
                        <input wire:model="newDay" type="number" min="1" max="31" inputmode="numeric"
                            class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        @error('newDay')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Dompet (opsional)</label>
                    <select wire:model="newWallet"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">-- Pilih dompet --</option>
                        @foreach($wallets as $w)
                        <option value="{{ $w->name }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button wire:click="addSubscription"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-medium text-sm">Simpan</button>
                <button wire:click="$set('showForm',false)"
                    class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl hover:bg-gray-50 text-sm">Batal</button>
            </div>
        </div>
    </div>
    @endif
</div>
