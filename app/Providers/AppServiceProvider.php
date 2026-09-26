<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

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
        // Limites par utilisateur (ou par IP sans session) : protègent contre les abus sans gêner l'usage normal
        $by = fn (Request $r) => (string) ($r->user() ? $r->user()->id : $r->ip());
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(240)->by($by($r)));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(60)->by($by($r)));
        RateLimiter::for('finalize', fn (Request $r) => Limit::perMinute(10)->by($by($r)));
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(30)->by($r->ip()));

        $this->app['events']->listen(
            SocialiteWasCalled::class,
            MicrosoftExtendSocialite::class . '@handle'
        );
    }
}
