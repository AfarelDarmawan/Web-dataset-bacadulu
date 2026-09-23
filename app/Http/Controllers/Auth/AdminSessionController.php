<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        Auth::shouldUse('admin');
        $guard = Auth::guard('admin');

        if ($guard->check() && $guard->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($guard->check()) {
            $guard->logout();
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();
        }

        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        Auth::shouldUse('admin');
        $guard = Auth::guard('admin');

        if ($guard->check() && $guard->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($guard->check()) {
            $guard->logout();
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:128'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        $remember = (bool) ($validated['remember'] ?? false);

        if (! $guard->attempt($credentials, $remember)) {
            AuditService::record('auth.admin_login_failed', null, [
                'email_hash' => hash('sha256', mb_strtolower($credentials['email'])),
            ]);

            return back()->withErrors([
                'email' => 'Email atau kata sandi admin tidak sesuai.',
            ])->onlyInput('email', 'remember');
        }

        if (! $guard->user()->isAdmin() || ! $guard->user()->isActive()) {
            AuditService::record('auth.admin_login_blocked', $guard->user(), [
                'reason' => $guard->user()->isAdmin() ? 'inactive' : 'not_admin',
            ]);

            $guard->logout();
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Akun ini tidak memiliki akses administrator.',
            ])->onlyInput('email', 'remember');
        }

        $request->session()->regenerate();
        $guard->user()->forceFill(['last_login_at' => now()])->save();
        AuditService::record('auth.admin_login', $guard->user());

        $request->session()->forget('url.intended');

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::shouldUse('admin');
        $guard = Auth::guard('admin');

        AuditService::record('auth.admin_logout', $guard->user());

        $guard->logout();
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}