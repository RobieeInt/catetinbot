<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-gray-800">Pengingat</h1>
        <button wire:click="$set('showForm',true)"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium">+ Tambah</button>
    </div>

    {{-- Active reminders --}}
    <p class="text-sm font-semibold text-gray-700 mb-2">Aktif ({{ count($activeList) }})</p>

    {{-- Mobile: card list --}}
    <div class="sm:hidden space-y-2 mb-5">
        @forelse($activeList as $r)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex justify-between items-start">
                <div class="min-w-0">
                    <span class="font-semibold text-gray-800 text-sm block truncate">{{ $r->task }}</span>
                    <span class="text-xs text-gray-500 block mt-0.5">
                        {{ \Carbon\Carbon::parse($r->remind_at)->setTimezone('Asia/Makassar')->format('d M Y, H:i') }}
                    </span>
                </div>
                @if($r->repeat !== 'none')
                <span class="ml-2 px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full text-xs shrink-0">{{ $r->repeat }}</span>
                @endif
            </div>
            <div class="flex gap-3 mt-3 pt-3 border-t border-gray-100">
                <button wire:click="deactivate({{ $r->id }})" class="text-xs text-yellow-600 font-medium">Nonaktifkan</button>
                <button wire:click="deleteReminder({{ $r->id }})" wire:confirm="Hapus pengingat ini?"
                    class="text-xs text-red-500 font-medium ml-auto">Hapus</button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 text-center text-gray-400 text-sm">
            Tidak ada pengingat aktif.
        </div>
        @endforelse
    </div>

    {{-- Desktop: table active --}}
    <div class="hidden sm:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-gray-600 font-medium">Tugas</th>
                    <th class="text-left px-4 py-3 text-gray-600 font-medium">Waktu</th>
                    <th class="text-center px-4 py-3 text-gray-600 font-medium">Ulang</th>
                    <th class="text-center px-4 py-3 text-gray-600 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($activeList as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $r->task }}</td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ \Carbon\Carbon::parse($r->remind_at)->setTimezone('Asia/Makassar')->format('d M Y, H:i') }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($r->repeat !== 'none')
                        <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full text-xs">{{ $r->repeat }}</span>
                        @else
                        <span class="text-gray-400 text-xs">sekali</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 justify-center">
                            <button wire:click="deactivate({{ $r->id }})" class="text-xs text-yellow-600 hover:text-yellow-800">Nonaktif</button>
                            <button wire:click="deleteReminder({{ $r->id }})" wire:confirm="Hapus pengingat ini?"
                                class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Tidak ada pengingat aktif.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Past reminders --}}
    @if(!empty($pastList))
    <p class="text-sm font-semibold text-gray-500 mb-2">Sudah lewat / nonaktif</p>

    {{-- Mobile: past cards --}}
    <div class="sm:hidden space-y-2 opacity-60">
        @foreach($pastList as $r)
        <div class="bg-white rounded-xl border border-gray-100 p-3 flex justify-between items-center">
            <div class="min-w-0">
                <span class="text-sm text-gray-600 block truncate">{{ $r->task }}</span>
                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($r->remind_at)->setTimezone('Asia/Makassar')->format('d M Y, H:i') }}</span>
            </div>
            <button wire:click="deleteReminder({{ $r->id }})" class="text-xs text-red-400 ml-3 shrink-0">Hapus</button>
        </div>
        @endforeach
    </div>

    {{-- Desktop: past table --}}
    <div class="hidden sm:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden opacity-60">
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach($pastList as $r)
                <tr>
                    <td class="px-4 py-2.5 text-gray-500">{{ $r->task }}</td>
                    <td class="px-4 py-2.5 text-gray-400">{{ \Carbon\Carbon::parse($r->remind_at)->setTimezone('Asia/Makassar')->format('d M Y, H:i') }}</td>
                    <td class="px-4 py-2.5 text-center">
                        <button wire:click="deleteReminder({{ $r->id }})" class="text-xs text-red-400 hover:text-red-600">Hapus</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Bottom-sheet modal --}}
    @if($showForm)
    <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center" wire:click.self="$set('showForm',false)">
        <div class="bg-white w-full sm:max-w-sm sm:mx-4 rounded-t-3xl sm:rounded-2xl shadow-xl p-5 pb-8 sm:pb-5">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>
            <h3 class="text-base font-bold text-gray-800 mb-4">Tambah Pengingat</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Tugas / Catatan</label>
                    <input wire:model="newTask" placeholder="Contoh: Minum vitamin"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('newTask')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Tanggal</label>
                        <input wire:model="newDate" type="date"
                            class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        @error('newDate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Jam</label>
                        <input wire:model="newTime" type="time"
                            class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Pengulangan</label>
                    <select wire:model="newRepeat"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="none">Sekali</option>
                        <option value="daily">Setiap hari</option>
                        <option value="weekly">Setiap minggu</option>
                        <option value="monthly">Setiap bulan</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button wire:click="addReminder"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-medium text-sm">Simpan</button>
                <button wire:click="$set('showForm',false)"
                    class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl hover:bg-gray-50 text-sm">Batal</button>
            </div>
        </div>
    </div>
    @endif
</div>
