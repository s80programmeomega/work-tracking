<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Activite;
use App\Models\Document;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\Workspace;
use App\Policies\ActivitePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\ProjetPolicy;
use App\Policies\TachePolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Workspace::class => WorkspacePolicy::class,
        Projet::class    => ProjetPolicy::class,
        Activite::class  => ActivitePolicy::class,
        Tache::class     => TachePolicy::class,
        Document::class  => DocumentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super admin bypasses all policy checks
        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });
    }
}
