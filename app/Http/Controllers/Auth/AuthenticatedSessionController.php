<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        Auth::shouldUse('web');

        $guard = Auth::guard('web');

        if ($guard->check() && $guard->user()->isResearcher()) {
            return redirect()->route('user.profile');
        }

        /*
         * Membersihkan sesi yang bukan milik peneliti.
         * Ini juga mencegah sesi admin lama mengambil alih portal publik.
         */
        if ($guard->check()) {
            $guard->logout();

            $request->session()->regenerate(true);
            $request->session()->regenerateToken();
        }

        /*
         * Hanya menerima redirect internal yang aman.
         *
         * Diterima:
         * /datasets
         * /profile
         * /requests?status=pending
         *
         * Ditolak:
         * //evil.example
         * /\evil.example
         * https://evil.example
         */
        $intended = (string) $request->query('redirect', '');

        if ($this->isSafeInternalPath($intended)) {
            $request->session()->put('url.intended', $intended);
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        Auth::shouldUse('web');

        $guard = Auth::guard('web');

        /*
         * Atur masa remember-me apabila SessionGuard mendukungnya.
         */
        if (method_exists($guard, 'setRememberDuration')) {
            $guard->setRememberDuration(
                (int) config('bacadulu.profile.remember_minutes', 43200)
            );
        }

        /*
         * Normalisasi email agar variasi huruf kapital dan spasi
         * tidak dapat digunakan untuk melewati pembatasan login.
         */
        $request->merge([
            'email' => mb_strtolower(
                trim((string) $request->input('email'))
            ),
        ]);

        if ($guard->check() && $guard->user()->isResearcher()) {
            return redirect()->route('user.profile');
        }

        if ($guard->check()) {
            $guard->logout();

            $request->session()->regenerate(true);
            $request->session()->regenerateToken();
        }

        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
                'max:190',
            ],
            'password' => [
                'required',
                'string',
                'max:128',
            ],
            'remember' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);

        /*
         * Field remember bukan bagian dari kredensial database.
         */
        unset($credentials['remember']);

        if (! $guard->attempt($credentials, $remember)) {
            AuditService::record(
                'auth.user_login_failed',
                null,
                [
                    'email_hash' => hash(
                        'sha256',
                        mb_strtolower($credentials['email'])
                    ),
                ]
            );

            return back()
                ->withErrors([
                    'email' => 'Email atau kata sandi tidak sesuai.',
                ])
                ->onlyInput('email');
        }

        /*
         * Akun administrator tidak boleh masuk melalui
         * halaman login peneliti.
         */
        if ($guard->user()->isAdmin()) {
            AuditService::record(
                'auth.user_login_blocked',
                $guard->user(),
                [
                    'reason' => 'admin_on_researcher_portal',
                ]
            );

            $guard->logout();

            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'Email ini bukan akun peneliti.',
                ])
                ->onlyInput('email');
        }

        /*
         * Pengguna yang dinonaktifkan tidak dapat melanjutkan sesi.
         */
        if (! $guard->user()->isActive()) {
            AuditService::record(
                'auth.user_login_blocked',
                $guard->user(),
                [
                    'reason' => 'inactive',
                ]
            );

            $guard->logout();

            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'Akun sedang dinonaktifkan.',
                ])
                ->onlyInput('email');
        }

        /*
         * Regenerasi session ID untuk mencegah session fixation.
         */
        $request->session()->regenerate();

        $guard->user()
            ->forceFill([
                'last_login_at' => now(),
            ])
            ->save();

        AuditService::record(
            'auth.user_login',
            $guard->user()
        );

        return redirect()->intended(
            route('user.profile')
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::shouldUse('web');

        $guard = Auth::guard('web');

        AuditService::record(
            'auth.user_logout',
            $guard->user()
        );

        $guard->logout();

        /*
         * Hapus session lama dan buat token CSRF baru.
         */
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Memastikan tujuan redirect merupakan path internal yang aman.
     */
    private function isSafeInternalPath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        /*
         * Path harus:
         * - dimulai dengan satu garis miring;
         * - tidak dimulai dengan dua garis miring;
         * - tidak mengandung backslash;
         * - tidak mengandung karakter kontrol.
         */
        return preg_match(
            '/\A\/(?!\/)[^\\\\\x00-\x1F\x7F]*\z/u',
            $path
        ) === 1;
    }
}