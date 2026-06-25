<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Activite;
use App\Models\Document;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Policies\ActivitePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\ProjetPolicy;
use App\Policies\SousTachePolicy;
use App\Policies\TachePolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Workspace::class => WorkspacePolicy::class,
        Projet::class => ProjetPolicy::class,
        Activite::class => ActivitePolicy::class,
        Tache::class => TachePolicy::class,
        SousTache::class => SousTachePolicy::class,
        Document::class => DocumentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Bind ContextualPermissionGate as a request-scoped singleton so the
        // per-request role permission cache is shared across all policy calls.
        $this->app->scoped(ContextualPermissionGate::class);

        // Platform-level Gates: superadmin-only operator actions.
        // Gate::before() has been removed — superadmin no longer bypasses workspace policy checks.
        Gate::define('platform.admin', fn (User $user) => $user->isSuperAdmin());
        Gate::define('platform.manage-workspace', fn (User $user) => $user->isSuperAdmin());
        Gate::define('platform.manage-users', fn (User $user) => $user->isSuperAdmin());

        // platform.operator: superadmin OR directeur (used for user-role endpoint).
        Gate::define('platform.operator', fn (User $user) => $user->isSuperAdmin() || $user->hasRole('directeur')
        );

        // Lock Horizon and Pulse to superadmin only.
        Gate::define('viewHorizon', fn (?User $user = null) => (bool) $user?->isSuperAdmin());
        Gate::define('viewPulse', fn (?User $user = null) => (bool) $user?->isSuperAdmin());
    }
}
