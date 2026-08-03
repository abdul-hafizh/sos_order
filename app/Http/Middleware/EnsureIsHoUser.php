<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsHoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->kode_cabang !== 'HO') {
            return redirect()->route('dashboard')
                ->with('error', 'Halaman ini hanya bisa diakses oleh user Head Office (HO).');
        }

        return $next($request);
    }
}
