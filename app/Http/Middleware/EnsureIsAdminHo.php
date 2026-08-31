<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdminHo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_admin) {
            return redirect()->route('dashboard')
                ->with('error', 'Halaman ini hanya bisa diakses oleh Admin.');
        }

        return $next($request);
    }
}
