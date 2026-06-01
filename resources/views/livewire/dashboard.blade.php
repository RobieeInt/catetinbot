<div>
    <h1 class="text-xl font-bold text-gray-800 mb-4">Overview</h1>

    {{-- Budget Cards --}}
    <div class="grid grid-cols-1 gap-3 mb-5 sm:grid-cols-3">
        @foreach (['daily' => 'Hari Ini', 'weekly' => 'Minggu Ini', 'monthly' => 'Bulan Ini'] as $key => $label)
        @php $d = $overview[$key]; @endphp
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $label }}</span>
                <span class="text-lg leading-none">
                    @if($d['status']==='danger')🚨@elseif($d['status']==='warning')⚠️@else✅@endif
                </span>
            </div>
            <div class="text-2xl font-bold text-gray-900 mb-0.5">{{ rp($d['expense']) }}</div>
            <div class="flex gap-3 text-xs text-gray-500 mb-3">
                <span>↑ {{ rp($d['income']) }}</span>
                <span class="{{ $d['net']>=0?'text-green-600':'text-red-500' }} font-medium">
                    Net {{ rp($d['net']) }}
                </span>
            </div>
            @if($d['budget']>0)
            <div class="space-y-1">
                <div class="flex justify-between text-xs text-gray-400">
                    <span>{{ rp($d['budget']) }}</span>
                    <span class="font-medium {{ $d['status']==='danger'?'text-red-500':($d['status']==='warning'?'text-yellow-500':'text-green-600') }}">{{ $d['pct'] }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all {{ $d['status']==='danger'?'bg-red-500':($d['status']==='warning'?'bg-yellow-400':'bg-green-500') }}"
                         style="width:{{ min(100,$d['pct']) }}%"></div>
                </div>
                @if($d['status']==='danger')
                @php $over = $d['expense'] - $d['budget']; @endphp
                <div class="text-xs font-semibold text-red-500 mt-1">🚨 Overbudget {{ rp($over) }}!</div>
                @elseif($d['status']==='warning')
                <div class="text-xs text-yellow-600 mt-1">⚠️ Sisa {{ rp($d['budget'] - $d['expense']) }}</div>
                @endif
            </div>
            @else
            <div class="text-xs text-gray-300 italic">Budget belum diset</div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-4 mb-5 lg:grid-cols-2">
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">📈 30 Hari Terakhir</h2>
            <div class="relative h-44">
                <canvas id="chart30"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">📅 6 Bulan Terakhir</h2>
            <div class="relative h-44">
                <canvas id="chartMonthly"></canvas>
            </div>
        </div>
    </div>

    {{-- Bottom grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

        {{-- Kategori --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">🏷 Kategori Minggu Ini</h2>
            @forelse($categoryBreakdown as $cat)
            <div class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
                <span class="text-sm text-gray-600 truncate mr-2">{{ $cat['category'] }}</span>
                <span class="text-sm font-semibold text-gray-800 shrink-0">{{ rp((int)$cat['sum']) }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 py-4 text-center">Belum ada data</p>
            @endforelse
        </div>

        {{-- Saldo --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-700">💼 Saldo Dompet</h2>
                <a href="{{ route('dashboard.wallets') }}" class="text-xs text-blue-500">Lihat →</a>
            </div>
            @forelse($wallets as $w)
            @php $bal = $walletBalances[$w->id] ?? 0; @endphp
            <div class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
                <span class="text-sm text-gray-600">{{ $w->name }}</span>
                <span class="text-sm font-semibold {{ $bal<0?'text-red-500':'text-gray-800' }}">{{ rp($bal) }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 py-4 text-center">Belum ada dompet</p>
            @endforelse
        </div>

        {{-- Pengingat + Jatuh tempo --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-700">⏰ Pengingat Aktif</h2>
                <a href="{{ route('dashboard.reminders') }}" class="text-xs text-blue-500">Lihat →</a>
            </div>
            @forelse(array_slice($reminders,0,4) as $r)
            <div class="py-2 border-b border-gray-50 last:border-0">
                <div class="text-sm font-medium text-gray-800 truncate">{{ $r->task }}</div>
                <div class="text-xs text-gray-400 mt-0.5">
                    {{ \Carbon\Carbon::parse($r->remind_at)->setTimezone(config('app.timezone'))->format('d M, H:i') }}
                    @if($r->repeat!=='none')<span class="text-blue-400 ml-1">· {{ $r->repeat }}</span>@endif
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 py-4 text-center">Tidak ada pengingat</p>
            @endforelse

            @php
                $upcoming = array_values(array_filter((array)$openDebts, fn($d)=>$d->due_date && \Carbon\Carbon::parse($d->due_date)->isFuture()));
                usort($upcoming, fn($a,$b)=>strcmp($a->due_date,$b->due_date));
            @endphp
            @if(!empty($upcoming))
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-500 mb-2">Jatuh Tempo</p>
                @foreach(array_slice($upcoming,0,3) as $d)
                <div class="py-2 border-b border-gray-50 last:border-0">
                    <div class="text-sm font-medium text-gray-800">{{ ucfirst($d->type) }} — {{ $d->person }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ rp((int)$d->amount) }} · {{ \Carbon\Carbon::parse($d->due_date)->format('d M Y') }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const opts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
        scales: { x: { ticks: { font: { size: 10 }, maxRotation: 45 } }, y: { ticks: { font: { size: 10 } } } }
    };

    const data30 = @json($chart30);
    const dataM  = @json($chartMonthly);

    new Chart(document.getElementById('chart30'), {
        type: 'bar',
        data: {
            labels: data30.map(d => (d.date||'').substring(5)),
            datasets: [
                { label:'Keluar', data: data30.map(d=>parseInt(d.expense)||0), backgroundColor:'rgba(239,68,68,0.7)', borderRadius:3 },
                { label:'Masuk',  data: data30.map(d=>parseInt(d.income)||0),  backgroundColor:'rgba(34,197,94,0.7)',  borderRadius:3 },
            ]
        },
        options: opts
    });

    new Chart(document.getElementById('chartMonthly'), {
        type: 'bar',
        data: {
            labels: dataM.map(d => d.month||''),
            datasets: [
                { label:'Keluar', data: dataM.map(d=>parseInt(d.expense)||0), backgroundColor:'rgba(239,68,68,0.7)', borderRadius:3 },
                { label:'Masuk',  data: dataM.map(d=>parseInt(d.income)||0),  backgroundColor:'rgba(34,197,94,0.7)',  borderRadius:3 },
            ]
        },
        options: opts
    });
});
</script>
