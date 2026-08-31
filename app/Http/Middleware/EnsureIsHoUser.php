<?php

namespace App\Http\Middleware;

use App\Models\AdminSos;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsHoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !AdminSos::where('id', $user->id)->exists()) {
            return redirect()->route('dashboard')
                ->with('error', 'Halaman ini hanya bisa diakses oleh Admin.');
        }

        return $next($request);
    }
}
