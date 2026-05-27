<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->middleware('auth:sanctum');
        $this->teamService = $teamService;
    }

    /**
     * Display a listing of teams
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'visibility' => $request->input('visibility'),
                'is_active' => $request->input('is_active', true),
                'project_id' => $request->input('project_id'),
                'search' => $request->input('search'),
                'per_page' => $request->input('per_page', 15),
            ];

            $teams = $this->teamService->getTeams($filters);

            return response()->json([
                'success' => true,
                'teams' => $teams,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des équipes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get teams for authenticated user
     */
    public function myTeams(Request $request): JsonResponse
    {
        try {
            $teams = $this->teamService->getUserTeams($request->user());

            return response()->json([
                'success' => true,
                'teams' => $teams,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de vos équipes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifie si l'utilisateur peut créer des équipes
     */
    private function canCreateTeam(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasRole('directeur') || $user->hasRole('manager') || $user->hasRole('cadre');
    }

    /**
     * Vérifie si l'utilisateur peut gérer cette équipe (propriétaire ou super admin)
     */
    private function canManageTeam(User $user, Team $team): bool
    {
        return $user->isSuperAdmin() || $team->owner_id === $user->id;
    }

    /**
     * Store a newly created team
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->canCreateTeam($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les managers et cadres peuvent créer des équipes.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projets,id',
            'visibility' => 'nullable|in:public,private,secret',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $team = $this->teamService->createTeam($request->user(), $validator->validated());

            return response()->json([
                'success' => true,
                'team' => $team,
                'message' => 'Équipe créée avec succès',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'équipe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified team by UUID
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $team = $this->teamService->getTeamByUuid($uuid);

            return response()->json([
                'success' => true,
                'team' => $team,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Équipe non trouvée',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified team
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'sometimes|in:public,private,secret',
            'settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $team = $this->teamService->getTeamByUuid($uuid);

            if (! $this->canManageTeam($request->user(), $team)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Seul le propriétaire peut modifier cette équipe.',
                ], 403);
            }

            $updatedTeam = $this->teamService->updateTeam($team, $validator->validated());

            return response()->json([
                'success' => true,
                'team' => $updatedTeam,
                'message' => 'Équipe mise à jour avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'équipe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archive the specified team
     */
    public function archive(string $uuid): JsonResponse
    {
        try {
            $team = $this->teamService->getTeamByUuid($uuid);

            if (! $this->canManageTeam(request()->user(), $team)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Seul le propriétaire peut archiver cette équipe.',
                ], 403);
            }

            $this->teamService->archiveTeam($team);

            return response()->json([
                'success' => true,
                'message' => 'Équipe archivée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage de l\'équipe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore archived team
     */
    public function restore(string $uuid): JsonResponse
    {
        try {
            $team = $this->teamService->getTeamByUuid($uuid);

            if (! $this->canManageTeam(request()->user(), $team)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Seul le propriétaire peut restaurer cette équipe.',
                ], 403);
            }

            $this->teamService->restoreTeam($team);

            return response()->json([
                'success' => true,
                'message' => 'Équipe restaurée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration de l\'équipe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified team
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $team = $this->teamService->getTeamByUuid($uuid);

            if (! $this->canManageTeam(request()->user(), $team)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Seul le propriétaire peut supprimer cette équipe.',
                ], 403);
            }

            $this->teamService->deleteTeam($team);

            return response()->json([
                'success' => true,
                'message' => 'Équipe supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'équipe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload team avatar
     */
    public function uploadAvatar(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $team = $this->teamService->getTeamByUuid($uuid);

            if (! $this->canManageTeam($request->user(), $team)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Seul le propriétaire peut modifier l\'avatar de cette équipe.',
                ], 403);
            }

            $path = $this->teamService->uploadAvatar($team, $request->file('avatar'));

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => asset('storage/'.$path),
                'message' => 'Avatar téléchargé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement de l\'avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get team statistics
     */
    public function stats(string $uuid): JsonResponse
    {
        try {
            $team = $this->teamService->getTeamByUuid($uuid);
            $stats = $this->teamService->getTeamStats($team);

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get team activity feed
     */
    public function activities(Request $request, string $uuid): JsonResponse
    {
        try {
            $team = $this->teamService->getTeamByUuid($uuid);
            $limit = $request->input('limit', 50);
            $activities = $this->teamService->getActivityFeed($team, $limit);

            return response()->json([
                'success' => true,
                'activities' => $activities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des activités',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get online members
     */
    public function onlineMembers(string $uuid): JsonResponse
    {
        try {
            $team = $this->teamService->getTeamByUuid($uuid);
            $onlineMembers = $this->teamService->getOnlineMembers($team);

            return response()->json([
                'success' => true,
                'online_members' => $onlineMembers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des membres en ligne',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user presence in team
     */
    public function updatePresence(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:online,away,busy,offline',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $team = $this->teamService->getTeamByUuid($uuid);
            $this->teamService->updateMemberPresence($team, $request->user(), $request->input('status'));

            return response()->json([
                'success' => true,
                'message' => 'Présence mise à jour avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la présence',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
