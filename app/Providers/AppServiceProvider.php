<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\SousTache;
use App\Models\User;
use App\Observers\SousTacheObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
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
        SousTache::observe(SousTacheObserver::class);

        // Log Viewer — accès réservé aux super-admins uniquement
        Gate::define('viewLogViewer', fn (User $user) => $user->is_super_admin);

        // Enregistrer automatiquement la relation documents() sur tous les modèles
        Model::resolveRelationUsing('documents', function ($model) {
            return $model->morphMany(Document::class, 'documentable');
        });
    }
}
