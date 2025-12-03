<?php

namespace App\Providers;

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
        
        // Enregistrer automatiquement la relation documents() sur tous les modèles
        \Illuminate\Database\Eloquent\Model::resolveRelationUsing('documents', function ($model) {
            return $model->morphMany(\App\Models\Document::class, 'documentable');
        });
    }
}
