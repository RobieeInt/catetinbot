<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            \App\Services\TelegramService::class,
            \App\Services\NotificationService::class
        );
    }

    public function boot(): void
    {
        // Rate limiter webhook Telegram: 30 req/menit per IP
        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(fn () => response()->json(['ok' => false, 'error' => 'Too many requests'], 429));
        });
    }
}
