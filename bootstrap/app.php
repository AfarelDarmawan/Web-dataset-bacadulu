<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureResearcher;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts(at: function (): array {
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);
            $configuredHost = is_string($host) && $host !== ''
                ? '^'.preg_quote($host, '/').'$'
                : '(?!)';

            if (app()->environment('production')) {
                return [$configuredHost];
            }

            return array_values(array_unique([
                $configuredHost,
                '^localhost$',
                '^127\.0\.0\.1$',
                '^\[?::1\]?$',
            ]));
        }, subdomains: false);

        $middleware->append(SecurityHeaders::class);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request): string {
            $adminPath = trim((string) config('bacadulu.admin.path', 'panel-adminbaca'), '/');

            return $request->is($adminPath, $adminPath.'/*')
                ? route('admin.login')
                : route('login');
        });

        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
            'admin' => EnsureAdmin::class,
            'researcher' => EnsureResearcher::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('bacadulu:sync-connectors')
            ->hourly()
            ->withoutOverlapping(30);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
