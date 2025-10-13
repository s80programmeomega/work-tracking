<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TeamMemberController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->middleware('auth:sanctum');
        $this->teamService = $teamService;
    }

    /**
     * Add member to team
     */
    public function store(Request $request, string $teamUuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|in:owner,admin,moderator,member',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $team = $this->teamService->getTeamByUuid($teamUuid);
            $user = User::findOrFail($request->input('user_id'));
            $role = $request->input('role', 'member');

            $member = $this->teamService->addMember($team, $user, $role, $request->user());

            return response()->json([
                'success' => true,
                'member' => $member->load('user'),
                'message' => 'Membre ajouté avec succès',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update member role
     */
    public function updateRole(Request $request, string $teamUuid, int $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,moderator,member',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $team = $this->teamService->getTeamByUuid($teamUuid);
            $user = User::findOrFail($userId);

            $this->teamService->updateMemberRole($team, $user, $request->input('role'), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Rôle mis à jour avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update member permissions
     */
    public function updatePermissions(Request $request, string $teamUuid, int $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $team = $this->teamService->getTeamByUuid($teamUuid);
            $user = User::findOrFail($userId);

            $this->teamService->updateMemberPermissions($team, $user, $request->input('permissions'));

            return response()->json([
                'success' => true,
                'message' => 'Permissions mises à jour avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove member from team
     */
    public function destroy(string $teamUuid, int $userId): JsonResponse
    {
        try {
            $team = $this->teamService->getTeamByUuid($teamUuid);
            $user = User::findOrFail($userId);

            $this->teamService->removeMember($team, $user, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Membre retiré avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Transfer team ownership
     */
    public function transferOwnership(Request $request, string $teamUuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_owner_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $team = $this->teamService->getTeamByUuid($teamUuid);
            $newOwner = User::findOrFail($request->input('new_owner_id'));

            $this->teamService->transferOwnership($team, $newOwner, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Propriété transférée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
