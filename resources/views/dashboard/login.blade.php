<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Catetin</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-gray-50 flex flex-col items-center justify-center px-4">
    <div class="w-full max-w-sm">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">💰</div>
            <h1 class="text-2xl font-bold text-gray-800">Catetin</h1>
            <p class="text-sm text-gray-500 mt-1">Dashboard Keuangan Pribadi</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-3 mb-4 text-sm">
                {{ $errors->first('password') }}
            </div>
            @endif

            <form method="POST" action="{{ route('dashboard.login.post') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input
                        type="password"
                        name="password"
                        autofocus
                        autocomplete="current-password"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Masukkan password"
                    >
                </div>
                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-xl transition-colors text-sm"
                >
                    Masuk
                </button>
            </form>
        </div>
    </div>
</body>
</html>
