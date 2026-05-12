<?php

namespace App\Providers;

use App\Models\Activite;
use App\Models\Document;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\Workspace;
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
            return new DocumentAccessResolver;
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
            $workspace = Workspace::find($workspaceId);

            if (! $workspace) {
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
        // Liste des modèles qui peuvent avoir des documents
        $documentableModels = [
            Workspace::class,
            Projet::class,
            Activite::class,
            Tache::class,
            TacheResultat::class,
        ];

        foreach ($documentableModels as $modelClass) {
            if (class_exists($modelClass)) {
                // Utilisez la classe spécifique plutôt que Model
                $modelClass::macro('getDocuments', function () {
                    return Document::where('documentable_type', get_class($this))
                        ->where('documentable_id', $this->getKey())
                        ->when(method_exists(Document::class, 'accessibleBy'), function ($query) {
                            return $query->accessibleBy(auth()->user());
                        })
                        ->get();
                });

                $modelClass::macro('uploadDocument', function ($file, $user = null, $options = []) {
                    $user = $user ?? auth()->user();

                    if (! $user) {
                        throw new \Exception('Aucun utilisateur authentifié');
                    }

                    $service = app(DocumentService::class);

                    return $service->upload(
                        $file,
                        get_class($this),
                        $this->getKey(),
                        $user,
                        $options
                    );
                });
            }
        }
    }
}
