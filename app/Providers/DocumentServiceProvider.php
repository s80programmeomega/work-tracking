<?php

namespace App\Providers;

use App\Models\Document;
use App\Policies\DocumentPolicy;
use App\Services\DocumentAccessResolver;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Enregistrer le DocumentAccessResolver comme singleton
        $this->app->singleton(DocumentAccessResolver::class, function ($app) {
            return new DocumentAccessResolver();
        });
        
        // Enregistrer le DocumentService comme singleton
        $this->app->singleton(DocumentService::class, function ($app) {
            return new DocumentService($app->make(DocumentAccessResolver::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Enregistrer la Policy pour Document
        Gate::policy(Document::class, DocumentPolicy::class);
        
        // Enregistrer des gates personnalisés si nécessaire
        $this->registerGates();
        
        // Enregistrer des macros si nécessaire
        $this->registerMacros();
    }
    
    /**
     * Enregistrer les gates personnalisés
     */
    protected function registerGates(): void
    {
        // Gate pour vérifier si un user peut uploader sur une entité
        Gate::define('upload-to-entity', function ($user, $entityType, $entityId) {
            $resolver = app(DocumentAccessResolver::class);
            return $resolver->canUpload($user, $entityType, $entityId);
        });
        
        // Gate pour vérifier l'accès à un workspace
        Gate::define('access-workspace-documents', function ($user, $workspaceId) {
            $workspace = \App\Models\Workspace::find($workspaceId);
            
            if (!$workspace) {
                return false;
            }
            
            if ($user->isSuperAdmin()) {
                return true;
            }
            
            if ($workspace->owner_id === $user->id) {
                return true;
            }
            
            return $workspace->members()->where('user_id', $user->id)->exists();
        });
    }
    
    /**
     * Enregistrer des macros pour les modèles
     */
    protected function registerMacros(): void
    {
        // Macro pour récupérer facilement les documents d'une entité
        // Exemple : $projet->documents()
        
        // Pour éviter les conflits, on utilise un nom explicite
        \Illuminate\Database\Eloquent\Model::macro('getDocuments', function () {
            return \App\Models\Document::where('documentable_type', get_class($this))
                ->where('documentable_id', $this->id)
                ->accessibleBy(auth()->user())
                ->get();
        });
        
        // Macro pour uploader facilement un document sur une entité
        // Exemple : $projet->uploadDocument($file, $user)
        \Illuminate\Database\Eloquent\Model::macro('uploadDocument', function ($file, $user = null, $options = []) {
            $user = $user ?? auth()->user();
            
            $service = app(DocumentService::class);
            
            return $service->upload(
                $file,
                get_class($this),
                $this->id,
                $user,
                $options
            );
        });
    }
}
