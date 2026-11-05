<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Límites por IP (y por email en login) para frenar fuerza bruta
     * sobre contraseñas y tokens de firma (OWASP A07).
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login:'.mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by('login-ip:'.$request->ip()),
        ]);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('signing', fn (Request $request) => Limit::perMinute(30)->by('signing:'.$request->ip())
        );

        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(60)->by('webhook:'.$request->ip())
        );
    }
}
