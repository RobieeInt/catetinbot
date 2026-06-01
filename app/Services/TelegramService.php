<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;
    private string $baseUrl;

    public function __construct()
    {
        $this->token   = config('services.telegram.token');
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
    }

    public function sendMessage(string $chatId, string $text, array $extra = []): void
    {
        $payload = array_merge([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ], $extra);

        try {
            Http::timeout(15)->post("{$this->baseUrl}/sendMessage", $payload);
        } catch (\Throwable $e) {
            Log::error('TelegramService::sendMessage failed', ['error' => $e->getMessage(), 'chat_id' => $chatId]);
        }
    }

    public function sendPhoto(string $chatId, string $photoPath, string $caption = ''): void
    {
        try {
            Http::timeout(30)
                ->attach('photo', file_get_contents($photoPath), basename($photoPath))
                ->post("{$this->baseUrl}/sendPhoto", [
                    'chat_id'    => $chatId,
                    'caption'    => $caption,
                    'parse_mode' => 'HTML',
                ]);
        } catch (\Throwable $e) {
            Log::error('TelegramService::sendPhoto failed', ['error' => $e->getMessage()]);
        }
    }

    public function getFile(string $fileId): ?object
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/getFile", ['file_id' => $fileId]);
            $data     = $response->json();
            if ($data['ok'] ?? false) {
                return (object) $data['result'];
            }
        } catch (\Throwable $e) {
            Log::error('TelegramService::getFile failed', ['error' => $e->getMessage()]);
        }
        return null;
    }

    public function downloadFile(string $filePath): ?string
    {
        try {
            $url      = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
            $response = Http::timeout(30)->get($url);
            if ($response->successful()) {
                return base64_encode($response->body());
            }
        } catch (\Throwable $e) {
            Log::error('TelegramService::downloadFile failed', ['error' => $e->getMessage()]);
        }
        return null;
    }

    public function downloadFileRaw(string $filePath): ?string
    {
        try {
            $url      = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
            $response = Http::timeout(30)->get($url);
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            Log::error('TelegramService::downloadFileRaw failed', ['error' => $e->getMessage()]);
        }
        return null;
    }

    public function setWebhook(string $url, string $secret): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/setWebhook", [
                'url'            => $url,
                'secret_token'   => $secret,
                'allowed_updates'=> ['message'],
            ]);
            return $response->json();
        } catch (\Throwable $e) {
            Log::error('TelegramService::setWebhook failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public function deleteWebhook(): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/deleteWebhook");
            return $response->json();
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public function setCommands(array $commands): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/setMyCommands", [
                'commands' => $commands,
            ]);
            return $response->json();
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public function getUpdates(int $offset = 0, int $limit = 100): array
    {
        try {
            $response = Http::timeout(35)->get("{$this->baseUrl}/getUpdates", [
                'offset'          => $offset,
                'limit'           => $limit,
                'timeout'         => 30,
                'allowed_updates' => ['message'],
            ]);
            $data = $response->json();
            if ($data['ok'] ?? false) {
                return $data['result'] ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning('TelegramService::getUpdates failed', ['error' => $e->getMessage()]);
        }
        return [];
    }
}
