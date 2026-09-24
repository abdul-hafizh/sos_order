<?php

namespace App\Http\Controllers;

use App\Models\AdminSos;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminHoController extends Controller
{
    public function index()
    {
        return Inertia::render('AdminHo/Index', [
            // Semua user di vw_admin_sos otomatis admin, tidak ada lagi
            // pemilihan admin tunggal - halaman ini cuma buat lihat/atur
            // telegram_chat_id supaya tahu siapa yang bakal menerima notifikasi.
            'users' => AdminSos::orderBy('nama_user')
                ->get(['id', 'user', 'nama_user', 'email', 'telegram_chat_id']),
        ]);
    }

    public function updateTelegram(Request $request, User $user)
    {
        abort_unless(AdminSos::where('id', $user->id)->exists(), 403, 'User bukan bagian dari GSOS.');

        $validated = $request->validate([
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);

        $user->update(['telegram_chat_id' => $validated['telegram_chat_id']]);

        return redirect()->back()->with('success', "Telegram Chat ID {$user->nama_user} berhasil diperbarui.");
    }
}
