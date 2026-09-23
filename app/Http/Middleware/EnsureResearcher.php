<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureResearcher
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Auth::guard('web')->user()?->isResearcher(), 403, 'Halaman ini khusus akun peneliti.');

        return $next($request);
    }
}
