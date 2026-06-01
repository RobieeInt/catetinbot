<?php

use App\Http\Controllers\Api\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware('throttle:webhook')
    ->name('telegram.webhook');
