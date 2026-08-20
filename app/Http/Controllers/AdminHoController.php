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
            'users' => AdminSos::orderBy('nama_user')
                ->get(['id', 'user', 'nama_user', 'email', 'is_admin', 'telegram_chat_id']),
        ]);
    }

    public function toggle(Request $request, User $user)
    {
        abort_unless(AdminSos::where('id', $user->id)->exists(), 403, 'User bukan bagian dari GSOS.');

        if ($user->is_admin) {
            $user->update(['is_admin' => 0]);

            return redirect()->back()->with('success', "Admin GSOS {$user->nama_user} dinonaktifkan.");
        }

        User::whereIn('id', AdminSos::pluck('id'))->update(['is_admin' => 0]);
        $user->update(['is_admin' => 1]);

        return redirect()->back()->with('success', "{$user->nama_user} sekarang menjadi Admin GSOS.");
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
