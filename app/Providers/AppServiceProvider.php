<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\SousTache;
use App\Observers\SousTacheObserver;
use Illuminate\Database\Eloquent\Model;
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

        // Enregistrer automatiquement la relation documents() sur tous les modèles
        Model::resolveRelationUsing('documents', function ($model) {
            return $model->morphMany(Document::class, 'documentable');
        });
    }
}
