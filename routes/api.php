<?php

use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware('throttle:webhook')
    ->name('telegram.webhook');

Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify'])
    ->name('whatsapp.webhook.verify');

Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle'])
    ->middleware('throttle:webhook')
    ->name('whatsapp.webhook');
