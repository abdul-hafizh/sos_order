<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegram
{
    public static function sendMessage(string|int $chatId, string $message): array
    {
        $token = config('services.telegram.bot_token');

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);

        $context = [
            'chat_id' => $chatId,
            'status' => $response->status(),
            'body' => $response->body(),
        ];

        // Telegram membalas ok:false (mis. "chat not found" kalau user belum
        // menekan Start di bot) - catat sebagai error supaya mudah dilacak.
        if ($response->json('ok') === true) {
            Log::info('TELEGRAM_SEND_OUT', $context);
        } else {
            Log::error('TELEGRAM_SEND_FAILED', $context);
        }

        return $response->json() ?? [];
    }
}