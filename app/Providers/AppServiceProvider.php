<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\SousTache;
use App\Models\User;
use App\Observers\SousTacheObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Opcodes\LogViewer\Facades\LogViewer;

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
        SousTache::observe(SousTacheObserver::class);

        // Forcer url() à utiliser l'URL du frontend SPA pour les e-mails et notifications.
        // APP_FRONTEND_URL = adresse réseau du frontend (ex: http://10.175.13.27:5173).
        URL::forceRootUrl(config('app.frontend_url', config('app.url')));

        // Le lien de réinitialisation doit pointer vers le frontend SPA, pas vers une route Blade
        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            return config('app.frontend_url', config('app.url'))
                .'/reset-password?token='.$token
                .'&email='.urlencode($user->email);
        });

        // Log Viewer — contrôle d'accès.
        //
        // En local/dev : aucun callback enregistré. Le middleware AuthorizeLogViewer
        // du package laisse passer les requêtes non-production sans gate ni callback
        // (comportement voulu par le package — outil développeur).
        //
        // En production : callback Bearer token. Le SPA stocke le token dans
        // localStorage ; le navigateur NE peut PAS l'envoyer automatiquement
        // lors d'une navigation directe (barre d'adresse). Deux chemins d'accès :
        //   1. Lien sidebar → /log-viewer?token=<token>  (navigation SPA)
        //   2. Header Authorization: Bearer <token>      (appel Axios)
        if (app()->isProduction()) {
            LogViewer::auth(function ($request) {
                $token = $request->bearerToken() ?? $request->query('token');
                if (! $token) {
                    return false;
                }
                $tokenRecord = PersonalAccessToken::findToken($token);
                if (! $tokenRecord) {
                    return false;
                }
                $user = $tokenRecord->tokenable;

                return $user instanceof User && $user->is_super_admin;
            });
        }

        // Enregistrer automatiquement la relation documents() sur tous les modèles
        Model::resolveRelationUsing('documents', function ($model) {
            return $model->morphMany(Document::class, 'documentable');
        });
    }
}
