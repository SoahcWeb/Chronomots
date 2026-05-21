<?php

namespace App\Providers;

use App\Services\GameplaySecurityService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('letters-submit', function (Request $request) {
            return Limit::perMinute(8)
                ->by('letters:'.($request->user()?->id ?? $request->ip()))
                ->response(function (Request $request, array $headers) {
                    app(GameplaySecurityService::class)->logRateLimitHit('letters', $request);

                    return response(
                        'Trop de tentatives sur le mode lettres. Attends un instant avant de rejouer.',
                        429,
                        $headers,
                    );
                });
        });

        RateLimiter::for('numbers-submit', function (Request $request) {
            return Limit::perMinute(8)
                ->by('numbers:'.($request->user()?->id ?? $request->ip()))
                ->response(function (Request $request, array $headers) {
                    app(GameplaySecurityService::class)->logRateLimitHit('numbers', $request);

                    return response(
                        'Trop de tentatives sur le mode chiffres. Attends un instant avant de rejouer.',
                        429,
                        $headers,
                    );
                });
        });

        RateLimiter::for('daily-challenge-submit', function (Request $request) {
            return Limit::perMinute(6)
                ->by('daily-challenge:'.($request->user()?->id ?? $request->ip()))
                ->response(function (Request $request, array $headers) {
                    app(GameplaySecurityService::class)->logRateLimitHit('daily-challenge', $request);

                    return response(
                        'Trop de tentatives sur les défis quotidiens. Attends un instant avant de réessayer.',
                        429,
                        $headers,
                    );
                });
        });
    }
}
