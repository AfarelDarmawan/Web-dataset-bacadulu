<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Auth::guard('admin')->user()?->isAdmin(), 403, 'Halaman ini khusus administrator.');

        return $next($request);
    }
}
