<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bot Telegram "Sync_sos_account" (token TELEGRAM_CHATBOT_TOKEN) - dipakai
 * khusus untuk alur sinkronisasi telegram_chat_id lewat nomor HP, terpisah
 * dari bot notifikasi SPK (App\Libraries\SendTelegram / TELEGRAM_BOT_TOKEN).
 */
class TelegramChatbot
{
    public static function sendMessage(string|int $chatId, string $message, ?array $replyMarkup = null): array
    {
        $token = config('services.telegram.chatbot_token');

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

        Log::info('TELEGRAM_CHATBOT_SEND_OUT', [
            'chat_id' => $chatId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $response->json() ?? [];
    }
}
