<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\Role as RoleEnum;
use App\Events\Realtime\SessionsAllRevoked;
use App\Http\Controllers\Controller;
use App\Http\Resources\ValidationAuditLogResource;
use App\Models\Activite;
use App\Models\AdminAuditLog;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\ValidationAuditLog;
use App\Models\Workspace;
use App\Notifications\TempAdminAccessGrantedNotification;
use App\Notifications\TrialExtendedNotification;
use App\Notifications\WorkspaceSuspendedNotification;
use App\Services\AdaptiveCache;
use App\Services\AdminAuditService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected AdaptiveCache $cache,
    ) {}

    /**
     * Platform-wide aggregate statistics.
     *
     * Mises en cache (~5 min, TTL adaptatif) : statistiques plateforme identiques
     * pour tous les super-admins, coûteuses (≈20 requêtes) et tolérantes à un
     * léger décalage.
     */
    public function stats(Request $request): JsonResponse
    {
        AdminAuditService::log($request->user(), 'stats.read');

        $data = $this->cache->remember('admin:stats', 300, fn () => $this->computeStats());

        return response()->json(['data' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeStats(): array
    {
        $totalWorkspaces = Workspace::count();
        $activeWorkspaces = Workspace::where('is_active', true)->count();

        $trialWorkspaces = Workspace::where('subscription_mode', 'trial')->count();
        $paidWorkspaces = Workspace::where('subscription_mode', 'paid')->count();

        $expiredTrials = Workspace::where('subscription_mode', 'trial')
            ->whereNotNull('trial_started_at')
            ->get()
            ->filter(fn (Workspace $w) => $this->subscriptionService->isTrialExpired($w))
            ->count();

        $expiringSoon = Workspace::where('subscription_mode', 'trial')
            ->whereNotNull('trial_started_at')
            ->get()
            ->filter(fn (Workspace $w) => $this->subscriptionService->isExpiringSoon($w))
            ->count();

        $totalUsers = User::count();
        $activeUsers = User::whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subDays(30))
            ->count();
        $superAdmins = User::where('is_super_admin', true)->count();
        $newUsersLast7Days = User::where('created_at', '>=', now()->subDays(7))->count();

        // Task statistics
        $taskStatsByStatus = Tache::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $totalTasks = array_sum($taskStatsByStatus);
        $overdueTasks = Tache::overdue()->count();
        $criticalTasks = Tache::where('priorite', 'critique')
            ->whereNotIn('statut', ['termine', 'annule'])
            ->count();

        $totalProjects = Projet::count();
        $totalActivities = Activite::count();

        // 10 most recently created workspaces with owner info
        $recentWorkspaces = Workspace::with(['owner:id,nom,email'])
            ->withCount('members')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Workspace $w) => [
                'id' => $w->id,
                'nom' => $w->nom,
                'subscription_mode' => $w->subscription_mode,
                'is_active' => $w->is_active,
                'created_at' => $w->created_at?->toISOString(),
                'owner' => $w->owner ? ['id' => $w->owner->id, 'nom' => $w->owner->nom] : null,
                'subscription' => $this->subscriptionService->summary($w),
            ]);

        // Growth data: new workspaces + users per day for last 7 days.
        // Perf : 2 requêtes GROUPÉES par jour (au lieu de 14 comptes jour par jour).
        $since = now()->subDays(6)->startOfDay();
        $wsByDay = Workspace::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')->pluck('total', 'd');
        $usersByDay = User::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->groupBy('d')->pluck('total', 'd');

        $growth = collect(range(6, 0))->map(function (int $daysAgo) use ($wsByDay, $usersByDay) {
            $date = now()->subDays($daysAgo)->toDateString();

            return [
                'date' => $date,
                'new_workspaces' => (int) ($wsByDay[$date] ?? 0),
                'new_users' => (int) ($usersByDay[$date] ?? 0),
            ];
        })->values();

        return [
            'workspaces' => [
                'total' => $totalWorkspaces,
                'active' => $activeWorkspaces,
                'trial' => $trialWorkspaces,
                'paid' => $paidWorkspaces,
                'expired_trials' => $expiredTrials,
                'expiring_soon' => $expiringSoon,
            ],
            'users' => [
                'total' => $totalUsers,
                'active_last_30_days' => $activeUsers,
                'super_admins' => $superAdmins,
                'new_last_7_days' => $newUsersLast7Days,
            ],
            'tasks' => [
                'total' => $totalTasks,
                'by_status' => $taskStatsByStatus,
                'overdue' => $overdueTasks,
                'critical' => $criticalTasks,
            ],
            'projects' => [
                'total' => $totalProjects,
            ],
            'activities' => [
                'total' => $totalActivities,
            ],
            'growth' => $growth,
            'recent_workspaces' => $recentWorkspaces,
        ];
    }

    /**
     * Paginated list of all workspaces with subscription info.
     */
    public function workspaces(Request $request): JsonResponse
    {
        AdminAuditService::log($request->user(), 'workspaces.list');

        $query = Workspace::with(['owner:id,nom,email'])
            ->withCount(['members', 'projets'])
            ->orderByDesc('created_at');

        if ($request->search) {
            $query->where('nom', 'like', '%'.$request->search.'%');
        }

        if ($request->subscription_mode) {
            $query->where('subscription_mode', $request->subscription_mode);
        }

        if ($request->is_active !== null) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $workspaces = $query->paginate($request->per_page ?? 20);

        $workspaces->getCollection()->transform(fn (Workspace $w) => array_merge($w->toArray(), [
            'subscription' => $this->subscriptionService->summary($w),
        ]));

        return response()->json($workspaces);
    }

    /**
     * Paginated list of all users with workspace and last login info.
     */
    public function users(Request $request): JsonResponse
    {
        AdminAuditService::log($request->user(), 'users.list');

        $query = User::with(['currentWorkspace:id,nom'])
            ->whereNull('is_system_owner')
            ->orderByDesc('created_at');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $users = $query->paginate($request->per_page ?? 20);

        $users->getCollection()->transform(fn (User $u) => [
            'id' => $u->id,
            'nom' => $u->nom,
            'email' => $u->email,
            'is_super_admin' => $u->isSuperAdmin(),
            'current_workspace' => $u->currentWorkspace
                ? ['id' => $u->currentWorkspace->id, 'nom' => $u->currentWorkspace->nom]
                : null,
            'last_login_at' => $u->last_login_at?->toISOString(),
            'created_at' => $u->created_at?->toISOString(),
        ]);

        return response()->json($users);
    }

    /**
     * Extend the trial period for a workspace.
     */
    public function extendTrial(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'trial_duration_days' => 'required|integer|min:1|max:365',
        ]);

        $oldDuration = $workspace->trial_duration_days;
        $workspace->update(['trial_duration_days' => $validated['trial_duration_days']]);

        AdminAuditService::log($request->user(), 'workspace.extend_trial', $workspace, [
            'old_duration' => $oldDuration,
            'new_duration' => $validated['trial_duration_days'],
        ]);

        // Notify workspace owner
        if ($workspace->owner) {
            $workspace->owner->notify(new TrialExtendedNotification(
                $workspace,
                $validated['trial_duration_days']
            ));
        }

        return response()->json([
            'data' => $this->subscriptionService->summary($workspace->fresh()),
        ]);
    }

    /**
     * Suspend (deactivate) a workspace.
     */
    public function suspendWorkspace(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $workspace->update(['is_active' => false]);

        AdminAuditService::log($request->user(), 'workspace.suspend', $workspace, [
            'reason' => $validated['reason'] ?? null,
        ]);

        // Notify workspace owner
        if ($workspace->owner) {
            $workspace->owner->notify(new WorkspaceSuspendedNotification(
                $workspace,
                $validated['reason'] ?? null
            ));
        }

        return response()->json(['message' => __('admin.actions.workspace_suspended')]);
    }

    /**
     * Reactivate a suspended workspace.
     */
    public function reactivateWorkspace(Request $request, Workspace $workspace): JsonResponse
    {
        $workspace->update(['is_active' => true]);

        AdminAuditService::log($request->user(), 'workspace.reactivate', $workspace);

        return response()->json(['message' => __('admin.actions.workspace_reactivated')]);
    }

    /**
     * List all roles with their permissions, grouped by module.
     */
    public function roles(): JsonResponse
    {
        $allPermissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Group permissions by their module prefix (e.g. "taches.view" → "taches")
        $grouped = $allPermissions->groupBy(fn (Permission $p) => explode('.', $p->name)[0]);

        $roles = Role::with('permissions')
            ->where('guard_name', 'web')
            ->orderBy('priority')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'is_global' => in_array($role->name, array_column(RoleEnum::cases(), 'value')),
                'priority' => $role->priority,
                'permissions' => $role->permissions->pluck('name')->values(),
            ]);

        return response()->json([
            'data' => [
                'roles' => $roles,
                'permissions_grouped' => $grouped->map(fn ($perms, $module) => [
                    'module' => $module,
                    'permissions' => $perms->values()->map(fn (Permission $p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                    ]),
                ])->values(),
                'all_permissions' => $allPermissions->pluck('name')->values(),
            ],
        ]);
    }

    /**
     * Sync the permissions assigned to a role.
     */
    public function syncRolePermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $permissions = Permission::whereIn('name', $validated['permissions'])
            ->where('guard_name', 'web')
            ->get();

        $role->syncPermissions($permissions);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Log::info('Permissions de rôle mises à jour par super-admin', [
            'admin_id' => $request->user()->id,
            'role' => $role->name,
            'permission_count' => $permissions->count(),
        ]);

        return response()->json([
            'data' => [
                'role' => $role->name,
                'permissions' => $role->fresh('permissions')->permissions->pluck('name')->values(),
            ],
            'message' => __('admin.roles.permissions_updated'),
        ]);
    }

    /**
     * Journal d'audit de validation cross-app pour le super-admin.
     * Filtre par auteur, action, plage de dates.
     */
    public function validationAuditLog(Request $request): JsonResponse
    {
        $request->validate([
            'actor_id' => 'sometimes|integer|exists:users,id',
            'action' => 'sometimes|string|in:approuve,renvoye,timeout,bypass,n1_valide,n1_rejete,n2_valide,n2_rejete',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = ValidationAuditLog::query()
            ->with(['actor', 'resultat.tache'])
            ->latest('created_at');

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => ValidationAuditLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * List temporary superadmin accounts created by the authenticated actor.
     * Superadmin sees all; directeur sees only those they created.
     */
    public function myTempSuperadmins(Request $request): JsonResponse
    {
        $actor = $request->user();

        AdminAuditService::log($actor, 'superadmins.list');

        // Inclure les comptes actifs ET les comptes suspendus (is_super_admin=false mais admin_expires_at présent et action=suspend).
        $query = User::whereNotNull('admin_expires_at')
            ->whereNull('is_system_owner')
            ->where(function ($q) {
                $q->where('is_super_admin', true)
                    ->orWhere(fn ($q2) => $q2->where('is_super_admin', false)->where('admin_expiry_action', 'suspend'));
            })
            ->with(['currentWorkspace:id,nom']);

        if (! $actor->isSuperAdmin() || $actor->hasRole('directeur')) {
            $query->where('created_by', $actor->id);
        }

        $users = $query->orderByDesc('created_at')->get();

        return response()->json([
            'data' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'nom' => $u->nom,
                'email' => $u->email,
                'admin_expires_at' => $u->admin_expires_at?->toISOString(),
                'admin_expiry_action' => $u->admin_expiry_action,
                'is_active' => $u->is_active,
                'is_suspended' => ! $u->is_super_admin && ! $u->is_active && $u->admin_expiry_action === 'suspend',
                // Rôle workspace accordé — premier grant trouvé (tous partagent le même rôle).
                'workspace_role' => DB::table('temporary_access')
                    ->where('user_id', $u->id)
                    ->value('role') ?? 'observateur',
                'custom_permissions' => (function () use ($u): ?array {
                    $raw = DB::table('temporary_access')
                        ->where('user_id', $u->id)
                        ->value('custom_permissions');

                    return $raw ? json_decode($raw, true) : null;
                })(),
                'granted_workspaces' => DB::table('temporary_access')
                    ->where('user_id', $u->id)
                    ->join('workspaces', 'workspaces.id', '=', 'temporary_access.accessible_id')
                    ->select('workspaces.id', 'workspaces.nom')
                    ->get(),
                'created_at' => $u->created_at?->toISOString(),
            ]),
        ]);
    }

    /**
     * Early-terminate a temporary superadmin account.
     * Applies admin_expiry_action immediately.
     */
    public function terminate(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if ($user->isSystemOwner()) {
            abort(403, 'Compte système protégé — modification impossible.');
        }

        if (! $user->isTempAdmin()) {
            abort(422, 'Ce compte n\'est pas un superadmin temporaire.');
        }

        // Directeur can only terminate accounts they created.
        if ($actor->hasRole('directeur') && $user->created_by !== $actor->id) {
            abort(403, 'Vous ne pouvez terminer que les comptes que vous avez créés.');
        }

        // Invalider toutes les sessions actives immédiatement, y compris en temps réel
        // (sans ça, une session déjà ouverte reste utilisable jusqu'au prochain rechargement).
        $user->tokens()->delete();
        broadcast(new SessionsAllRevoked($user));

        // Désactiver le compte dans tous les cas avant l'action finale.
        // is_active=false bloque déjà la connexion (AuthService::login) : suffisant pour
        // verrouiller l'accès sans détruire les grants nécessaires à une réactivation.
        $user->update(['is_active' => false, 'is_super_admin' => false]);
        $user->syncRoles([]);

        if ($user->admin_expiry_action === 'delete') {
            // Suppression définitive : les grants n'ont plus de raison d'exister.
            DB::table('temporary_access')->where('user_id', $user->id)->delete();
            DB::table('workspace_members')
                ->where('user_id', $user->id)
                ->where('is_temp_access', true)
                ->delete();

            AdminAuditService::log($actor, 'superadmin.terminated', $user, ['expiry_action' => 'delete']);
            $user->forceDelete();
        } else {
            // Suspension : les grants temporary_access et workspace_members sont conservés
            // pour que reactivateTempAdmin() puisse restaurer l'accès workspace + droits.
            AdminAuditService::log($actor, 'superadmin.terminated', $user, ['expiry_action' => 'suspend']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['message' => 'Compte superadmin temporaire terminé.']);
    }

    /**
     * Réactiver un compte superadmin temporaire suspendu.
     */
    public function reactivateTempAdmin(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if ($user->isSystemOwner()) {
            abort(403, 'Compte système protégé — modification impossible.');
        }

        // Le compte doit être suspendu : is_super_admin=false, admin_expires_at présent, action=suspend.
        if ($user->is_super_admin || $user->admin_expires_at === null || $user->admin_expiry_action !== 'suspend') {
            abort(422, 'Ce compte n\'est pas un compte superadmin temporaire suspendu.');
        }

        if ($actor->hasRole('directeur') && $user->created_by !== $actor->id) {
            abort(403, 'Vous ne pouvez réactiver que les comptes que vous avez créés.');
        }

        // Réactiver le compte et restaurer le rôle Spatie.
        // Recalcule une nouvelle expiration sur la même durée que celle accordée initialement,
        // ré-ancrée à maintenant (sinon le compte serait immédiatement re-marqué comme expiré).
        $originalDuration = $user->created_at->diffInSeconds($user->admin_expires_at);
        $newExpiresAt = Carbon::now()->addSeconds(max($originalDuration, 0));

        $user->update(['is_active' => true, 'is_super_admin' => true, 'admin_expires_at' => $newExpiresAt]);
        $user->syncRoles(['super_admin']);

        // Restaurer les grants workspace conservés lors de la suspension (terminate()/
        // ExpireSuperAdminAccounts ne suppriment plus temporary_access pour l'action 'suspend').
        // On ré-ancre leur expiration sur la même durée que le compte pour qu'ils redeviennent actifs.
        DB::table('temporary_access')
            ->where('user_id', $user->id)
            ->update(['expires_at' => $newExpiresAt]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        AdminAuditService::log($actor, 'superadmin.reactivated', $user);

        return response()->json(['message' => __('admin.users.temp_admin_reactivated')]);
    }

    /**
     * Recherche un utilisateur par adresse email exacte — accessible aux directeurs et superadmins.
     */
    public function lookupUserByEmail(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)
            ->whereNull('is_system_owner')
            ->first();

        if (! $user) {
            return response()->json(['message' => __('admin.users.not_found')], 404);
        }

        return response()->json([
            'id' => $user->id,
            'nom_complet' => $user->nom_complet,
            'email' => $user->email,
            'is_super_admin' => $user->isSuperAdmin(),
        ]);
    }

    /**
     * Crée un nouveau compte administrateur temporaire sans envoyer les identifiants automatiquement.
     * Les identifiants sont envoyés manuellement via sendTempAdminCredentials().
     */
    public function createTempAdmin(Request $request): JsonResponse
    {
        $actor = $request->user();

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'nom' => 'required|string|max:255',
            'expires_in_days' => 'required|integer|min:1|max:365',
            'expiry_action' => 'required|in:suspend,delete',
            'workspace_ids' => 'required|array|min:1',
            'workspace_ids.*' => 'integer|exists:workspaces,id',
            // Rôle par défaut appliqué à tous les workspaces sélectionnés.
            'workspace_role' => 'sometimes|string|in:observateur,cadre,manager',
            // Permissions personnalisées (tableau de chaînes) — surcharge le jeu du rôle si présent.
            'custom_permissions' => 'sometimes|nullable|array',
            'custom_permissions.*' => 'string',
        ]);

        // Un directeur ne peut accorder l'accès qu'à ses propres workspaces.
        if ($actor->hasRole('directeur')) {
            $ownedIds = Workspace::where('owner_id', $actor->id)
                ->whereIn('id', $validated['workspace_ids'])
                ->pluck('id');

            if ($ownedIds->count() !== count($validated['workspace_ids'])) {
                abort(403, 'Vous ne pouvez accorder l\'accès qu\'à vos propres workspaces.');
            }
        }

        $plainPassword = Str::password(12);
        $expiresAt = Carbon::now()->addDays($validated['expires_in_days']);
        $workspaceRole = $validated['workspace_role'] ?? 'observateur';
        $customPermissions = ! empty($validated['custom_permissions']) ? $validated['custom_permissions'] : null;

        $user = User::create([
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'password' => $plainPassword,
            'is_super_admin' => true,
            'is_active' => true,
            'admin_expires_at' => $expiresAt,
            'admin_expiry_action' => $validated['expiry_action'],
            'created_by' => $actor->id,
        ]);

        $user->syncRoles(['super_admin']);

        // Enregistrer les accès workspace scopés dans temporary_access.
        // Le champ role contient le rôle contextuel workspace (observateur/cadre/manager),
        // pas readonly/readwrite — ce mappage est géré côté frontend.
        foreach ($validated['workspace_ids'] as $wsId) {
            DB::table('temporary_access')->insert([
                'user_id' => $user->id,
                'accessible_type' => Workspace::class,
                'accessible_id' => $wsId,
                'role' => $workspaceRole,
                'custom_permissions' => $customPermissions !== null ? json_encode($customPermissions) : null,
                'created_by' => $actor->id,
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]);
        }

        AdminAuditService::log($actor, 'superadmin.created', $user, [
            'workspace_ids' => $validated['workspace_ids'],
            'expires_at' => $expiresAt->toISOString(),
        ]);

        return response()->json([
            'message' => __('admin.users.temp_admin_created'),
            'data' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'email' => $user->email,
                'admin_expires_at' => $expiresAt->toISOString(),
                'admin_expiry_action' => $validated['expiry_action'],
            ],
        ], 201);
    }

    /**
     * Envoie (ou renvoie) les identifiants d'un compte admin temporaire par email.
     * Génère un nouveau mot de passe à chaque appel.
     */
    public function sendTempAdminCredentials(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if (! $user->isTempAdmin()) {
            abort(422, 'Ce compte n\'est pas un compte admin temporaire.');
        }

        if ($actor->hasRole('directeur') && $user->created_by !== $actor->id) {
            abort(403, 'Vous ne pouvez envoyer les identifiants que pour les comptes que vous avez créés.');
        }

        // Génère un nouveau mot de passe et le sauvegarde.
        // Le cast 'hashed' sur User appelle Hash::make() automatiquement —
        // passer le texte brut évite un double-hachage.
        $plainPassword = Str::password(12);
        $user->update(['password' => $plainPassword]);

        $firstGrantedWorkspace = DB::table('temporary_access')
            ->where('user_id', $user->id)
            ->where('accessible_type', Workspace::class)
            ->orderBy('id')
            ->first();

        $workspace = $firstGrantedWorkspace
            ? Workspace::find($firstGrantedWorkspace->accessible_id)
            : null;

        if ($workspace) {
            $user->notify(new TempAdminAccessGrantedNotification(
                grantedBy: $actor,
                workspace: $workspace,
                expiresAt: Carbon::parse($user->admin_expires_at),
                expiryAction: $user->admin_expiry_action ?? 'suspend',
                plainPassword: $plainPassword,
            ));
        }

        AdminAuditService::log($actor, 'superadmin.credentials_sent', $user);

        return response()->json(['message' => __('admin.users.credentials_sent')]);
    }

    /**
     * Update the global Spatie role of a user (super_admin, directeur, utilisateur).
     */
    public function updateUserRole(Request $request, User $user): JsonResponse
    {
        if ($user->isSystemOwner()) {
            abort(403, 'Compte système protégé — modification impossible.');
        }

        $actor = $request->user();
        $isDirecteur = $actor->hasRole('directeur');

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', array_column(RoleEnum::cases(), 'value'))],
            'is_super_admin' => 'sometimes|boolean',
            'admin_expires_at' => 'sometimes|nullable|date|after:now',
            'admin_expiry_action' => 'sometimes|in:suspend,delete',
            'workspace_ids' => 'sometimes|array',
            'workspace_ids.*' => 'integer|exists:workspaces,id',
        ]);

        // Directeur can only grant workspace access for workspaces they own.
        if ($isDirecteur && ! empty($validated['workspace_ids'])) {
            $ownedIds = Workspace::where('owner_id', $actor->id)
                ->whereIn('id', $validated['workspace_ids'])
                ->pluck('id');

            if ($ownedIds->count() !== count($validated['workspace_ids'])) {
                abort(403, 'Vous ne pouvez accorder l\'accès qu\'à vos propres workspaces.');
            }
        }

        $user->syncRoles([$validated['role']]);

        $updateData = [];
        if (isset($validated['is_super_admin'])) {
            $updateData['is_super_admin'] = $validated['is_super_admin'];
        }
        if (isset($validated['admin_expires_at'])) {
            $updateData['admin_expires_at'] = $validated['admin_expires_at'];
        }
        if (isset($validated['admin_expiry_action'])) {
            $updateData['admin_expiry_action'] = $validated['admin_expiry_action'];
        }
        if ($validated['is_super_admin'] ?? false) {
            $updateData['created_by'] = $actor->id;
        }
        if (! empty($updateData)) {
            $user->update($updateData);
        }

        // Create temporary workspace access grants if workspace_ids provided.
        if (! empty($validated['workspace_ids']) && ($validated['is_super_admin'] ?? false)) {
            $expiresAt = $validated['admin_expires_at'] ?? null;

            DB::table('temporary_access')->where('user_id', $user->id)
                ->whereIn('accessible_id', $validated['workspace_ids'])
                ->delete();

            foreach ($validated['workspace_ids'] as $wsId) {
                DB::table('temporary_access')->insert([
                    'user_id' => $user->id,
                    'accessible_type' => Workspace::class,
                    'accessible_id' => $wsId,
                    'role' => 'observateur',
                    'expires_at' => $expiresAt,
                    'created_by' => $actor->id,
                    'created_at' => now(),
                ]);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Notifier l'utilisateur promu par email si un accès temporaire vient d'être accordé.
        if (($validated['is_super_admin'] ?? false) && isset($validated['admin_expires_at'])) {
            $workspace = ! empty($validated['workspace_ids'])
                ? Workspace::find($validated['workspace_ids'][0])
                : null;

            if ($workspace) {
                $user->notify(new TempAdminAccessGrantedNotification(
                    grantedBy: $actor,
                    workspace: $workspace,
                    expiresAt: Carbon::parse($validated['admin_expires_at']),
                    expiryAction: $validated['admin_expiry_action'] ?? 'suspend',
                ));
            }
        }

        AdminAuditService::log($actor, 'user.role_updated', $user, [
            'new_role' => $validated['role'],
            'is_super_admin' => $validated['is_super_admin'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'email' => $user->email,
                'roles' => $user->fresh()->getRoleNames(),
                'is_super_admin' => $user->fresh()->is_super_admin,
            ],
            'message' => __('admin.users.role_updated'),
        ]);
    }

    /**
     * Journal d'audit des actions admin plateforme — super-admin permanent uniquement.
     */
    public function auditLog(Request $request): JsonResponse
    {
        $request->validate([
            'actor_id' => 'sometimes|integer|exists:users,id',
            'action' => 'sometimes|string|max:100',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = AdminAuditLog::with(['actor:id,nom,email'])
            ->latest('created_at');

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Journal d'audit scopé — directeur voit uniquement les actions de ses superadmins temporaires.
     */
    public function myAuditLog(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $actor = $request->user();

        $query = AdminAuditLog::with(['actor:id,nom,email'])
            ->where('created_by', $actor->id)
            ->latest('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
