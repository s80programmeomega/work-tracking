<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Enregistre les assets Scribe publiés et isole la configuration
 * de documentation du reste des providers applicatifs.
 * Les routes sont déclarées dans routes/docs.php (chargé par RouteServiceProvider).
 */
class ScribeServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void {}
}
