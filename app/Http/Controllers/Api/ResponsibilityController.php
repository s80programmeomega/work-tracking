<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ResponsibilityRequest;
use App\Models\Responsibility;
use App\Models\User;
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResponsibilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->responsibilities()->get();

        return response()->json(['data' => $items]);
    }

    public function store(ResponsibilityRequest $request): JsonResponse
    {
        $item = $request->user()->responsibilities()->create($request->validated());

        return response()->json(['data' => $item], 201);
    }

    public function update(ResponsibilityRequest $request, Responsibility $responsibility): JsonResponse
    {
        abort_unless($responsibility->user_id === $request->user()->id, 403);

        $responsibility->update($request->validated());

        return response()->json(['data' => $responsibility->fresh()]);
    }

    public function destroy(Request $request, Responsibility $responsibility): JsonResponse
    {
        abort_unless($responsibility->user_id === $request->user()->id, 403);

        $responsibility->delete();

        return response()->json(null, 204);
    }

    /** Crée une responsabilité pour un autre utilisateur (directeur/manager/superadmin). */
    public function storeFor(ResponsibilityRequest $request, User $user): JsonResponse
    {
        $this->authorizeManagerAccess($request, $user);

        $item = $user->responsibilities()->create($request->validated());

        return response()->json(['data' => $item], 201);
    }

    /** Met à jour une responsabilité d'un autre utilisateur (directeur/manager/superadmin). */
    public function updateFor(ResponsibilityRequest $request, User $user, Responsibility $responsibility): JsonResponse
    {
        $this->authorizeManagerAccess($request, $user);
        abort_unless($responsibility->user_id === $user->id, 403);

        $responsibility->update($request->validated());

        return response()->json(['data' => $responsibility->fresh()]);
    }

    /** Supprime une responsabilité d'un autre utilisateur (directeur/manager/superadmin). */
    public function destroyFor(Request $request, User $user, Responsibility $responsibility): JsonResponse
    {
        $this->authorizeManagerAccess($request, $user);
        abort_unless($responsibility->user_id === $user->id, 403);

        $responsibility->delete();

        return response()->json(null, 204);
    }

    private function authorizeManagerAccess(Request $request, User $target): void
    {
        /** @var User $viewer */
        $viewer = $request->user();

        if ($viewer->isSuperAdmin()) {
            return;
        }

        $gate = app(ContextualPermissionGate::class);
        $sharedWorkspace = Workspace::whereHas('members', fn ($q) => $q->where('users.id', $viewer->id))
            ->whereHas('members', fn ($q) => $q->where('users.id', $target->id))
            ->first();

        abort_unless(
            $sharedWorkspace && $gate->userCan($viewer, Permission::WORKSPACES_VIEW_MEMBERS, $sharedWorkspace),
            403,
            'Accès non autorisé'
        );
    }
}
