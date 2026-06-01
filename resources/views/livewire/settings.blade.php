<div>
    <h1 class="text-xl font-bold text-gray-800 mb-4">Pengaturan</h1>

    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 mb-4">
        @if($saved)
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 text-sm">✅ Budget berhasil disimpan!</div>
        @endif

        <p class="text-sm font-semibold text-gray-700 mb-3">Budget Pengeluaran</p>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Budget Harian (Rp)</label>
                <input wire:model="daily" type="number" min="0" inputmode="numeric"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                @error('daily')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                @if((int)$daily > 0)<p class="text-xs text-gray-400 mt-1">{{ rp((int)$daily) }}/hari</p>@endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Budget Mingguan (Rp)</label>
                <input wire:model="weekly" type="number" min="0" inputmode="numeric"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                @error('weekly')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                @if((int)$weekly > 0)<p class="text-xs text-gray-400 mt-1">{{ rp((int)$weekly) }}/minggu</p>@endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Budget Bulanan (Rp)</label>
                <input wire:model="monthly" type="number" min="0" inputmode="numeric"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                @error('monthly')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                @if((int)$monthly > 0)<p class="text-xs text-gray-400 mt-1">{{ rp((int)$monthly) }}/bulan</p>@endif
            </div>
        </div>

        <button wire:click="save"
            class="mt-5 w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-xl transition-colors text-sm">
            Simpan Pengaturan
        </button>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
        <h3 class="text-sm font-semibold text-yellow-800 mb-2">⚙️ Info Akun</h3>
        <div class="space-y-1">
            <p class="text-sm text-yellow-700">OWNER_CHAT_ID: <code class="bg-yellow-100 px-1 rounded text-xs">{{ env('OWNER_CHAT_ID', 'Belum diset') }}</code></p>
            <p class="text-sm text-yellow-700">Timezone: <code class="bg-yellow-100 px-1 rounded text-xs">{{ config('app.timezone') }}</code></p>
        </div>
    </div>
</div>
