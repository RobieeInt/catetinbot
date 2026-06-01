<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catetin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @livewireStyles
</head>
<body class="h-full bg-gray-50 font-sans" x-data="{ sidebarOpen: false }">

    {{-- ============================================================ --}}
    {{-- TOP BAR (mobile header) --}}
    {{-- ============================================================ --}}
    <header class="fixed top-0 left-0 right-0 z-30 bg-white border-b border-gray-200 md:hidden">
        <div class="flex items-center justify-between h-14 px-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">💰</span>
                <span class="font-bold text-gray-800">Catetin</span>
            </div>
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </header>

    {{-- ============================================================ --}}
    {{-- SIDEBAR (mobile overlay + desktop fixed) --}}
    {{-- ============================================================ --}}

    {{-- Overlay --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/40 md:hidden" x-cloak></div>

    {{-- Sidebar panel --}}
    <aside
        x-show="sidebarOpen || window.innerWidth >= 768"
        x-transition:enter="transition-transform duration-200"
        x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform duration-200"
        x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
        class="fixed top-0 left-0 bottom-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col md:translate-x-0 md:z-20"
        x-cloak
    >
        {{-- Logo --}}
        <div class="flex items-center gap-2.5 h-16 px-5 border-b border-gray-100">
            <span class="text-2xl">💰</span>
            <div>
                <div class="font-bold text-gray-800 leading-none">Catetin</div>
                <div class="text-xs text-gray-400 mt-0.5">Keuangan Pribadi</div>
            </div>
            <button @click="sidebarOpen = false" class="ml-auto p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 md:hidden">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Nav items --}}
        <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">
            @php
            $navItems = [
                ['route' => 'dashboard',               'icon' => '📊', 'label' => 'Overview'],
                ['route' => 'dashboard.transactions',  'icon' => '📋', 'label' => 'Transaksi'],
                ['route' => 'dashboard.items',         'icon' => '🛒', 'label' => 'Breakdown Item'],
                ['route' => 'dashboard.wallets',       'icon' => '💼', 'label' => 'Dompet'],
                ['route' => 'dashboard.subscriptions', 'icon' => '📅', 'label' => 'Langganan'],
                ['route' => 'dashboard.debts',         'icon' => '💳', 'label' => 'Utang & Piutang'],
                ['route' => 'dashboard.savings',       'icon' => '🏦', 'label' => 'Tabungan'],
                ['route' => 'dashboard.reminders',     'icon' => '⏰', 'label' => 'Pengingat'],
                ['route' => 'dashboard.settings',      'icon' => '⚙️', 'label' => 'Pengaturan'],
            ];
            @endphp
            @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors {{ request()->routeIs($item['route']) ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                <span class="text-base w-5 text-center">{{ $item['icon'] }}</span>
                {{ $item['label'] }}
            </a>
            @endforeach
        </nav>

        {{-- Footer --}}
        <div class="p-3 border-t border-gray-100">
            <a href="{{ route('dashboard.logout') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-red-500 hover:bg-red-50 transition-colors">
                <span class="text-base w-5 text-center">🚪</span>
                Keluar
            </a>
        </div>
    </aside>

    {{-- ============================================================ --}}
    {{-- MAIN CONTENT --}}
    {{-- ============================================================ --}}
    <div class="md:pl-64 flex flex-col min-h-full">
        <main class="flex-1 pt-14 md:pt-0 pb-6 px-4 md:px-6 lg:px-8 max-w-7xl w-full mx-auto">
            <div class="py-4 md:py-6">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
