<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('auth-login', function (Request $request): array {
            $identity = hash('sha256', mb_strtolower((string) $request->input('email')).'|'.$request->ip());

            return [
                Limit::perMinute(5)->by('login-identity-'.$identity),
                Limit::perMinute(20)->by('login-ip-'.$request->ip()),
            ];
        });

        RateLimiter::for('registration', fn (Request $request): array => [
            Limit::perHour(5)->by('register-ip-'.$request->ip()),
        ]);

        RateLimiter::for('public-catalog', fn (Request $request): array => [
            Limit::perMinute(120)->by('catalog-ip-'.$request->ip()),
        ]);

        RateLimiter::for('data-request', fn (Request $request): array => [
            Limit::perHour(20)->by('request-user-'.($request->user()?->id ?? $request->ip())),
            Limit::perMinute(30)->by('request-ip-'.$request->ip()),
        ]);

        RateLimiter::for('data-download', fn (Request $request): array => [
            Limit::perMinute(8)->by('download-user-'.($request->user()?->id ?? $request->ip())),
            Limit::perHour(60)->by('download-hour-'.($request->user()?->id ?? $request->ip())),
        ]);

        RateLimiter::for('profile-update', fn (Request $request): array => [
            Limit::perMinute(10)->by('profile-update-user-'.($request->user()?->id ?? $request->ip())),
        ]);

        RateLimiter::for('dataset-import', fn (Request $request): array => [
            Limit::perMinute(3)->by('import-user-'.($request->user()?->id ?? $request->ip())),
            Limit::perHour(20)->by('import-hour-'.($request->user()?->id ?? $request->ip())),
        ]);

        RateLimiter::for('source-upload', fn (Request $request): array => [
            Limit::perMinute(4)->by('source-upload-user-'.($request->user()?->id ?? $request->ip())),
            Limit::perHour(30)->by('source-upload-hour-'.($request->user()?->id ?? $request->ip())),
        ]);

        RateLimiter::for('ai-extraction', fn (Request $request): array => [
            Limit::perMinute(2)->by('ai-extract-user-'.($request->user()?->id ?? $request->ip())),
            Limit::perHour(20)->by('ai-extract-hour-'.($request->user()?->id ?? $request->ip())),
        ]);

        RateLimiter::for('data-sync', fn (Request $request): array => [
            Limit::perMinute(3)->by('data-sync-user-'.($request->user()?->id ?? $request->ip())),
            Limit::perHour(30)->by('data-sync-hour-'.($request->user()?->id ?? $request->ip())),
        ]);

        RateLimiter::for('admin-write', fn (Request $request): array => [
            Limit::perMinute(60)->by('admin-write-'.($request->user()?->id ?? $request->ip())),
        ]);
    }
}
