<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        Auth::shouldUse('web');
        $guard = Auth::guard('web');

        if ($guard->check() && $guard->user()->isResearcher()) {
            return redirect()->route('user.profile');
        }

        if ($guard->check()) {
            $guard->logout();
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        Auth::shouldUse('web');
        $guard = Auth::guard('web');

        if ($guard->check() && $guard->user()->isResearcher()) {
            return redirect()->route('user.profile');
        }

        if ($guard->check()) {
            $guard->logout();
            $request->session()->regenerate(true);
            $request->session()->regenerateToken();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'institution' => ['nullable', 'string', 'max:190'],
            'password' => ['required', 'confirmed', 'max:128', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'institution' => $validated['institution'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'researcher',
            'status' => 'active',
        ]);

        event(new Registered($user));
        $guard->login($user);
        $request->session()->regenerate();
        AuditService::record('auth.user_registered', $user);

        return redirect()->route('user.profile')
            ->with('success', 'Akun berhasil dibuat. Selamat datang di BacaDulu Dataset.');
    }
}
