<?php
namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;

class TelegramSettings
{
    public function send(string $message): void
    {
        if (!SystemSetting::readBool('telegram.enabled', false)) {
            throw new \RuntimeException('Telegram esta deshabilitado.');
        }

        $token = trim((string) SystemSetting::read('telegram.bot_token', ''));
        $chatId = trim((string) SystemSetting::read('telegram.chat_id', ''));

        if ($token === '' || $chatId === '') {
            throw new \RuntimeException('Falta Bot Token o Chat ID de Telegram.');
        }

        $response = Http::timeout(10)
            ->asForm()
            ->post('https://api.telegram.org/bot' . $token . '/sendMessage', [
                'chat_id' => $chatId,
                'text' => $message,
                'disable_web_page_preview' => true,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Telegram respondio HTTP ' . $response->status() . '.'
            );
        }
    }

    public function sendTest(): void
    {
        $this->send('Conectate TV v0.8.0 - prueba de Telegram correcta.');
    }
}
