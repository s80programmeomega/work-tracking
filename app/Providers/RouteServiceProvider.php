<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // Tests PHPUnit + Dusk : pas de rate limit pour ne pas étrangler les balayages multi-pages.
            if (app()->environment('testing') || env('DISABLE_RATE_LIMITING')) {
                return Limit::none();
            }

            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Limiteur dédié au login : protège contre le brute-force sans piéger les
        // utilisateurs légitimes. Clé = email + IP, donc les échecs d'un utilisateur
        // ne bloquent pas les autres derrière la même IP (NAT/bureau). 10/min.
        RateLimiter::for('login', function (Request $request) {
            if (app()->environment('testing') || env('DISABLE_RATE_LIMITING')) {
                return Limit::none();
            }

            $email = (string) $request->input('email');

            return Limit::perMinute(30)->by(mb_strtolower($email).'|'.$request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // "web" + "api" : sessions Laravel + tokens Sanctum — les deux permettent d'accéder aux docs
            Route::middleware(['web', 'api'])
                ->prefix('api')
                ->group(base_path('routes/docs.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
