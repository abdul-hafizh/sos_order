<?php

namespace App\Http\Controllers;

use App\Libraries\TelegramChatbot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook untuk bot Telegram "Sync_sos_account". Alurnya:
 * 1. User buka chat bot lalu klik /start -> bot minta kontak (nomor HP) lewat
 *    tombol "request_contact" (nomor didapat langsung dari Telegram, bukan
 *    diketik manual, supaya tidak bisa dipalsukan dari sisi user).
 * 2. User share kontak -> nomor HP-nya dicocokkan ke t_user.no_hp.
 * 3. Kalau cocok, chat_id Telegram disimpan ke t_user.telegram_chat_id.
 *    Kalau tidak ketemu, telegram_chat_id TIDAK diubah dan user diberi tahu.
 */
class TelegramSyncController extends Controller
{
    public function webhook(Request $request)
    {
        if (!$this->isValidSecret($request)) {
            Log::warning('TELEGRAM_SYNC_WEBHOOK: secret token tidak valid');
            abort(403);
        }

        $message = $request->input('message');

        if (!is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'] ?? null;

        if (!$chatId) {
            return response()->json(['ok' => true]);
        }

        $contact = $message['contact'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));

        if (is_array($contact)) {
            $this->handleContact($chatId, $message, $contact);
        } elseif (str_starts_with($text, '/start')) {
            $this->sendStartPrompt($chatId);
        } else {
            $this->sendRequestContactReminder($chatId);
        }

        return response()->json(['ok' => true]);
    }

    private function isValidSecret(Request $request): bool
    {
        $expected = (string) config('services.telegram.chatbot_webhook_secret');

        if ($expected === '') {
            // Belum dikonfigurasi - jangan blokir supaya setup awal tetap bisa
            // dites, tapi ini tidak aman untuk production.
            Log::warning('TELEGRAM_CHATBOT_WEBHOOK_SECRET belum diset, webhook sync Telegram belum terlindungi.');
            return true;
        }

        $received = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        return hash_equals($expected, $received);
    }

    private function sendStartPrompt(string|int $chatId): void
    {
        $message = "Halo! Selamat datang di Sync SOS Account.\n\n"
            . "Untuk menghubungkan akun Telegram ini dengan akun SOS Anda, silakan bagikan nomor HP Anda dengan menekan tombol \"Bagikan Nomor HP\" di bawah.";

        TelegramChatbot::sendMessage($chatId, $message, [
            'keyboard' => [[
                ['text' => 'Bagikan Nomor HP', 'request_contact' => true],
            ]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ]);
    }

    private function sendRequestContactReminder(string|int $chatId): void
    {
        TelegramChatbot::sendMessage(
            $chatId,
            "Ketik /start lalu tekan tombol \"Bagikan Nomor HP\" untuk menyinkronkan akun Telegram Anda."
        );
    }

    private function handleContact(string|int $chatId, array $message, array $contact): void
    {
        // Pastikan kontak yang dibagikan adalah milik pengirim sendiri, bukan
        // kontak orang lain yang di-forward ke chat.
        $fromId = $message['from']['id'] ?? null;
        $contactUserId = $contact['user_id'] ?? null;

        if ($fromId && $contactUserId && (string) $fromId !== (string) $contactUserId) {
            TelegramChatbot::sendMessage($chatId, 'Mohon bagikan nomor HP milik Anda sendiri, bukan kontak orang lain.');
            return;
        }

        $phoneNumber = (string) ($contact['phone_number'] ?? '');
        $phoneKey = $this->normalizePhone($phoneNumber);

        if ($phoneKey === '') {
            TelegramChatbot::sendMessage($chatId, 'Nomor HP tidak terbaca, silakan coba lagi.');
            return;
        }

        $user = User::query()
            ->whereNotNull('no_hp')
            ->get(['id', 'nama_user', 'no_hp'])
            ->first(function (User $candidate) use ($phoneKey) {
                return $this->normalizePhone((string) $candidate->no_hp) === $phoneKey;
            });

        if (!$user) {
            TelegramChatbot::sendMessage(
                $chatId,
                "Nomor HP {$phoneNumber} tidak ditemukan di data akun SOS. Silakan hubungi admin untuk memastikan nomor HP Anda sudah terdaftar dengan benar."
            );
            Log::warning('TELEGRAM_SYNC_WEBHOOK: no_hp tidak ditemukan', [
                'phone' => $phoneNumber,
                'chat_id' => $chatId,
            ]);
            return;
        }

        $user->update(['telegram_chat_id' => (string) $chatId]);

        TelegramChatbot::sendMessage(
            $chatId,
            "Berhasil! Akun Telegram ini sudah tersambung dengan akun SOS a.n. {$user->nama_user}. Anda akan menerima notifikasi pesanan melalui chat ini."
        );
    }

    /**
     * Samakan format nomor HP (0812xxxx, 62812xxxx, +62812xxxx) supaya bisa
     * dicocokkan meskipun formatnya beda-beda: ambil semua digit lalu pakai
     * 10 digit terakhir sebagai kunci pembanding.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return substr($digits, -10);
    }
}
