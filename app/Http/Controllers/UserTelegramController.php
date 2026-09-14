<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserTelegramController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->select(['id', 'user', 'nama_user', 'email', 'telegram_chat_id'])
            ->when($request->search, function ($query, $search) {
                $query->where('user', 'like', "%{$search}%")
                    ->orWhere('nama_user', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('nama_user')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('User-Telegram/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return back()->with('success', "Telegram Chat ID {$user->nama_user} berhasil diperbarui.");
    }
}
