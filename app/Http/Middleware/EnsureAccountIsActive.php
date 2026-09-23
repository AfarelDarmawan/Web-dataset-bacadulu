<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $guardName = $request->routeIs('admin.*') ? 'admin' : 'web';
        $guard = Auth::guard($guardName);
        $user = $guard->user();

        if ($user && ! $user->isActive()) {
            $guard->logout();
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            return redirect()->route($guardName === 'admin' ? 'admin.login' : 'login')->withErrors([
                'email' => 'Akun dinonaktifkan. Hubungi administrator untuk bantuan.',
            ]);
        }

        return $next($request);
    }
}
