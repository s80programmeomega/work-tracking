<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // L'application utilise des tokens Sanctum (Bearer) et non la session web.
        // On expose l'endpoint d'autorisation sous /api/broadcasting/auth
        // avec auth:sanctum, ce qui aligne l'auth temps réel sur le reste de l'API.
        Broadcast::routes([
            'prefix' => 'api',
            'middleware' => ['api', 'auth:sanctum'],
        ]);

        require base_path('routes/channels.php');
    }
}
