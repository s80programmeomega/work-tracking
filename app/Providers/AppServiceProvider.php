<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\SousTache;
use App\Models\User;
use App\Observers\SousTacheObserver;
use Illuminate\Database\Eloquent\Model;
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

        // Log Viewer — accès réservé aux super-admins.
        //
        // Ce SPA utilise l'authentification par token Bearer (Sanctum), pas de session web.
        // Le Gate standard ne fonctionne pas car auth()->user() renvoie null sans session.
        // On utilise le callback LogViewer::auth() qui reçoit la Request complète et peut
        // lire le token Bearer directement depuis l'en-tête Authorization.
        LogViewer::auth(function ($request) {
            // Accepte le token depuis l'en-tête Authorization (Axios SPA)
            // OU depuis le paramètre de requête ?token=… (navigation directe dans le navigateur).
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

        // Enregistrer automatiquement la relation documents() sur tous les modèles
        Model::resolveRelationUsing('documents', function ($model) {
            return $model->morphMany(Document::class, 'documentable');
        });
    }
}
