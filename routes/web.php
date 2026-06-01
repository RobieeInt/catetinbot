<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\ExpensesTable;
use App\Livewire\ItemBreakdown;
use App\Livewire\Wallets;
use App\Livewire\Settings;
use App\Livewire\Subscriptions;
use App\Livewire\Debts;
use App\Livewire\Savings;
use App\Livewire\Reminders;

// Dashboard login
Route::get('/dashboard/login', function () {
    return view('dashboard.login');
})->name('dashboard.login');

Route::post('/dashboard/login', function (\Illuminate\Http\Request $request) {
    $password = config('app.dashboard_password');

    if (!$password || $request->input('password') === $password) {
        $request->session()->put('dashboard_auth', true);
        return redirect()->route('dashboard');
    }

    return back()->withErrors(['password' => 'Password salah.']);
})->name('dashboard.login.post');

Route::get('/dashboard/logout', function () {
    session()->forget('dashboard_auth');
    return redirect()->route('dashboard.login');
})->name('dashboard.logout');

// Dashboard routes (protected by DashboardPassword middleware)
Route::middleware(['web', 'dashboard.password'])->prefix('dashboard')->group(function () {
    Route::get('/',              Dashboard::class)->name('dashboard');
    Route::get('/transactions',  ExpensesTable::class)->name('dashboard.transactions');
    Route::get('/items',         ItemBreakdown::class)->name('dashboard.items');
    Route::get('/wallets',       Wallets::class)->name('dashboard.wallets');
    Route::get('/settings',      Settings::class)->name('dashboard.settings');
    Route::get('/subscriptions', Subscriptions::class)->name('dashboard.subscriptions');
    Route::get('/debts',         Debts::class)->name('dashboard.debts');
    Route::get('/savings',       Savings::class)->name('dashboard.savings');
    Route::get('/reminders',     Reminders::class)->name('dashboard.reminders');
});

Route::get('/', function () {
    return view('welcome');
})->name('home');
