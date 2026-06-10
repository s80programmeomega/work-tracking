<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ValidationAuditLogResource;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\ValidationAuditLog;
use App\Models\Workspace;
use App\Notifications\TrialExtendedNotification;
use App\Notifications\WorkspaceSuspendedNotification;
use App\Services\AdaptiveCache;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $query = User::with(['currentWorkspace:id,nom'])
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

        Log::info('Essai prolongé par super-admin', [
            'admin_id' => $request->user()->id,
            'action' => 'extend_trial',
            'target_workspace_id' => $workspace->id,
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

        Log::warning('Workspace suspendu par super-admin', [
            'admin_id' => $request->user()->id,
            'action' => 'suspend_workspace',
            'target_workspace_id' => $workspace->id,
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

        Log::info('Workspace réactivé par super-admin', [
            'admin_id' => $request->user()->id,
            'action' => 'reactivate_workspace',
            'target_workspace_id' => $workspace->id,
        ]);

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
     * Update the global Spatie role of a user (super_admin, directeur, utilisateur).
     */
    public function updateUserRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', array_column(RoleEnum::cases(), 'value'))],
            'is_super_admin' => 'sometimes|boolean',
        ]);

        $user->syncRoles([$validated['role']]);

        if (isset($validated['is_super_admin'])) {
            $user->update(['is_super_admin' => $validated['is_super_admin']]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Log::info('Rôle utilisateur modifié par super-admin', [
            'admin_id' => $request->user()->id,
            'target_user_id' => $user->id,
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
}
