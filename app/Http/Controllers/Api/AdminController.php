<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\TrialExtendedNotification;
use App\Notifications\WorkspaceSuspendedNotification;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    /**
     * Platform-wide aggregate statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $totalWorkspaces = Workspace::count();
        $activeWorkspaces = Workspace::where('is_active', true)->count();

        $trialWorkspaces = Workspace::where('subscription_mode', 'trial')->count();
        $paidWorkspaces = Workspace::where('subscription_mode', 'paid')->count();

        $warningDays = config('subscription.expiry_warning_days', 7);

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

        // 10 most recently created workspaces with owner info
        $recentWorkspaces = Workspace::with(['owner:id,nom,email'])
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

        return response()->json([
            'data' => [
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
                ],
                'recent_workspaces' => $recentWorkspaces,
            ],
        ]);
    }

    /**
     * Paginated list of all workspaces with subscription info.
     */
    public function workspaces(Request $request): JsonResponse
    {
        $query = Workspace::with(['owner:id,nom,email'])
            ->withCount('members')
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
}
