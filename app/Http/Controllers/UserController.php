<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Activite;
use App\Models\Document;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasRole('directeur'), 403);

        $filters = $request->only([
            'search',
            'role',
            'team_id',
            'is_active',
            'sort_by',
            'sort_direction',
            'per_page',
        ]);

        $users = $this->userService->getUsers($filters);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->id() === $user->id, 403);

        $user->load(['roles', 'permissions']);
        $stats = $this->userService->getUserStats($user);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->setAttribute('stats', $stats)),
        ]);
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'permissions']);
        $stats = $this->userService->getUserStats($user);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->setAttribute('stats', $stats)),
        ]);
    }

    /**
     * Update current user profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Self-delete current user account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = $request->user();

        $user->tokens()->delete();

        $this->userService->deleteAccount($user);

        return response()->json(['success' => true, 'message' => 'Account deleted successfully.'], 204);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->userService->changePassword(
                $request->user(),
                $request->current_password,
                $request->new_password
            );

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->id() === $user->id, 403);

        $user = $this->userService->updateUser($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user): JsonResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->userService->deleteUser($user);

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Toggle user active status
     */
    public function toggleActive(User $user): JsonResponse
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->id() === $user->id, 403);

        $user = $this->userService->toggleActiveStatus($user);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Get user activity log
     */
    public function activity(User $user): JsonResponse
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->id() === $user->id, 403);

        $activities = $this->userService->getUserActivity($user);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Search users — enrichi pour le partage de document si document_id est fourni
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
        ]);

        $users = $this->userService->searchUsers($request->q, $request->limit ?? 10);

        if (! $request->document_id) {
            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
            ]);
        }

        // Enrichissement pour le partage de document
        $document = Document::with('documentable')->find($request->document_id);

        $excludeIds = collect([$document->user_id, $request->user()->id])->filter();

        // Exclure super_admin et directeur (ont déjà accès partout)
        $privilegedIds = User::role(['super_admin', 'directeur'])->pluck('id');
        $excludeIds = $excludeIds->merge($privilegedIds);

        [$memberIds, $workspaceOwnerId] = $this->resolveEntityContext(
            $document->documentable_type,
            $document->documentable
        );

        if ($workspaceOwnerId) {
            $excludeIds->push($workspaceOwnerId);
        }

        $excludeIds = $excludeIds->unique()->values()->all();
        $memberIdsArray = $memberIds->all();

        $result = $users
            ->filter(fn ($u) => ! \in_array($u->id, $excludeIds))
            ->map(fn ($u) => [
                'id' => $u->id,
                'nom' => $u->nom,
                'email' => $u->email,
                'avatar' => $u->avatar_url,
                'initials' => $u->initials,
                'is_member' => \in_array($u->id, $memberIdsArray),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Retourne [Collection(memberIds), workspaceOwnerId|null] pour une entité donnée
     */
    private function resolveEntityContext(string $type, $entity): array
    {
        if (! $entity) {
            return [collect(), null];
        }

        switch ($type) {
            case Workspace::class:
                return [
                    $entity->members()->get()->pluck('id'),
                    $entity->owner_id,
                ];

            case Projet::class:
                return [
                    $entity->members()->get()->pluck('id'),
                    $entity->workspace?->owner_id,
                ];

            case Activite::class:
                return [
                    $entity->membres()->get()->pluck('id'),
                    $entity->projet?->workspace?->owner_id,
                ];

            case Tache::class:
                return [
                    $entity->assignees()->get()->pluck('id'),
                    $entity->activite?->projet?->workspace?->owner_id,
                ];

            case TacheResultat::class:
                return [
                    $entity->tache?->assignees()->get()->pluck('id') ?? collect(),
                    $entity->tache?->activite?->projet?->workspace?->owner_id,
                ];

            default:
                return [collect(), null];
        }
    }
}
