<div>
    <h1 class="text-xl font-bold text-gray-800 mb-4">Breakdown Item</h1>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 mb-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Periode</label>
                <select wire:model.live="period" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="harian">Hari Ini</option>
                    <option value="mingguan">Minggu Ini</option>
                    <option value="bulanan">Bulan Ini</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Urutkan</label>
                <select wire:model.live="sortBy" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="total">Total Terbesar</option>
                    <option value="qty">Qty Terbanyak</option>
                </select>
            </div>
        </div>

        @if($period === 'custom')
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Dari</label>
                <input wire:model.live="dateFrom" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sampai</label>
                <input wire:model.live="dateTo" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
        </div>
        @endif

        <div>
            <label class="block text-xs text-gray-500 mb-1">Cari</label>
            <input wire:model.live="search" placeholder="Nama item..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
    </div>

    {{-- Mobile: card list --}}
    <div class="sm:hidden space-y-2">
        @forelse($items as $i => $item)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-xs text-gray-400 w-5 shrink-0">{{ $i + 1 }}</span>
                    <span class="font-medium text-gray-800 text-sm truncate">{{ $item->name }}</span>
                </div>
                <span class="text-sm font-semibold text-red-600 ml-2 shrink-0">{{ rp((int)$item->total) }}</span>
            </div>
            <div class="flex gap-4 mt-2 ml-7 text-xs text-gray-500">
                <span>Qty: <strong class="text-gray-700">{{ $item->qty }}</strong></span>
                <span>Frekuensi: <strong class="text-gray-700">{{ $item->times }}x</strong></span>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center text-gray-400 text-sm">
            Belum ada data item.
        </div>
        @endforelse
    </div>

    {{-- Desktop: table --}}
    <div class="hidden sm:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">#</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Nama Item</th>
                        <th class="text-right px-4 py-3 text-gray-600 font-medium">Total</th>
                        <th class="text-right px-4 py-3 text-gray-600 font-medium">Qty</th>
                        <th class="text-right px-4 py-3 text-gray-600 font-medium">Frekuensi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $i => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-right font-medium text-red-600">{{ rp((int)$item->total) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $item->qty }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $item->times }}x</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Belum ada data item.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(!empty($items))
        <div class="px-4 py-3 border-t border-gray-100 text-xs text-gray-500">
            Periode: {{ $start }} s/d {{ $end }} &nbsp;·&nbsp; {{ count($items) }} item unik
        </div>
        @endif
    </div>

    @if(!empty($items))
    <p class="sm:hidden text-xs text-gray-400 text-center mt-3">Periode: {{ $start }} s/d {{ $end }} · {{ count($items) }} item</p>
    @endif
</div>
