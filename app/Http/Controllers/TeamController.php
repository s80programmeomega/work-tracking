<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of teams
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check permission
        if (!$user->can('team.view')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $query = Team::with(['responsable', 'membres']);

        // Filter based on user role
        if (!$user->hasRoleLevel('manager')) {
            // Non-managers can only see their own team
            $query->where('id', $user->team_id);
        }

        $teams = $query->get();

        return response()->json([
            'success' => true,
            'teams' => $teams
        ]);
    }

    /**
     * Store a newly created team
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('team.create')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'responsable_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $team = Team::create($validator->validated());
        $team->load(['responsable', 'membres']);

        return response()->json([
            'success' => true,
            'team' => $team,
            'message' => 'Équipe créée avec succès'
        ], 201);
    }

    /**
     * Display the specified team
     */
    public function show(Request $request, Team $team): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('team.view')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Check if user can view this specific team
        if (!$user->hasRoleLevel('manager') && $user->team_id !== $team->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $team->load(['responsable', 'membres']);

        return response()->json([
            'success' => true,
            'team' => $team
        ]);
    }

    /**
     * Update the specified team
     */
    public function update(Request $request, Team $team): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('team.edit')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'responsable_id' => 'sometimes|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $team->update($validator->validated());
        $team->load(['responsable', 'membres']);

        return response()->json([
            'success' => true,
            'team' => $team,
            'message' => 'Équipe mise à jour avec succès'
        ]);
    }

    /**
     * Remove the specified team
     */
    public function destroy(Request $request, Team $team): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('team.delete')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Check if team has members
        if ($team->membres()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une équipe qui contient des membres'
            ], 400);
        }

        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Équipe supprimée avec succès'
        ]);
    }

    /**
     * Add member to team
     */
    public function addMember(Request $request, Team $team): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('team.manage_members')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $member = User::find($request->user_id);

        // Check if user is already in a team
        if ($member->team_id) {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur fait déjà partie d\'une équipe'
            ], 400);
        }

        $member->update(['team_id' => $team->id]);

        return response()->json([
            'success' => true,
            'message' => 'Membre ajouté à l\'équipe avec succès'
        ]);
    }

    /**
     * Remove member from team
     */
    public function removeMember(Request $request, Team $team): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('team.manage_members')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $member = User::find($request->user_id);

        if ($member->team_id !== $team->id) {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur ne fait pas partie de cette équipe'
            ], 400);
        }

        $member->update(['team_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Membre retiré de l\'équipe avec succès'
        ]);
    }

    /**
     * Get available users for team assignment
     */
    public function availableUsers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('team.manage_members')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $users = User::whereNull('team_id')
                    ->select('id', 'nom', 'email', 'role', 'fonction')
                    ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }
}
