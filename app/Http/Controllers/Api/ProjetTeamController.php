<?php

namespace App\Http\Controllers\Api;

use App\Events\Teams\TeamLinkedToProject;
use App\Events\Teams\TeamUnlinkedFromProject;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Projet;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class ProjetTeamController extends Controller
{
    /**
     * Liste les équipes liées au projet.
     */
    public function index(Projet $projet): AnonymousResourceCollection
    {
        $this->authorize('view', $projet);

        $teams = Team::query()
            ->where('project_id', $projet->id)
            ->with('members')
            ->get();

        return TeamResource::collection($teams);
    }

    /**
     * Lie une équipe du workspace au projet.
     */
    public function link(Request $request, Projet $projet): JsonResponse
    {
        $this->authorize('manageTeams', $projet);

        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $team = Team::query()
            ->where('id', $validated['team_id'])
            ->where('workspace_id', $projet->workspace_id)
            ->firstOrFail();

        if ($team->project_id === $projet->id) {
            return response()->json(['message' => 'Équipe déjà liée à ce projet.'], 422);
        }

        if ($team->project_id !== null) {
            return response()->json(['message' => 'Cette équipe est déjà liée à un autre projet.'], 422);
        }

        $team->update(['project_id' => $projet->id]);

        Log::info('Équipe liée au projet', [
            'team_id' => $team->id,
            'projet_id' => $projet->id,
            'user_id' => $request->user()->id,
        ]);

        event(new TeamLinkedToProject($team, $projet, $request->user()));

        return response()->json([
            'message' => 'Équipe liée au projet avec succès.',
            'team' => new TeamResource($team->loadMissing('members')),
        ]);
    }

    /**
     * Délie une équipe du projet.
     */
    public function unlink(Request $request, Projet $projet, Team $team): JsonResponse
    {
        $this->authorize('manageTeams', $projet);

        if ($team->project_id !== $projet->id) {
            return response()->json(['message' => 'Cette équipe n\'est pas liée à ce projet.'], 422);
        }

        $team->update(['project_id' => null]);

        Log::info('Équipe déliée du projet', [
            'team_id' => $team->id,
            'projet_id' => $projet->id,
            'user_id' => $request->user()->id,
        ]);

        event(new TeamUnlinkedFromProject($team, $projet, $request->user()));

        return response()->json(['message' => 'Équipe déliée du projet avec succès.']);
    }

    /**
     * Active ou désactive le mode équipes sur le projet.
     */
    public function toggleUseTeams(Request $request, Projet $projet): JsonResponse
    {
        $this->authorize('manageTeams', $projet);

        $validated = $request->validate([
            'use_teams' => ['required', 'boolean'],
        ]);

        $projet->update(['use_teams' => $validated['use_teams']]);

        Log::info('Mode équipes mis à jour sur le projet', [
            'projet_id' => $projet->id,
            'use_teams' => $validated['use_teams'],
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => $validated['use_teams']
                ? 'Intégration équipes activée.'
                : 'Intégration équipes désactivée.',
            'use_teams' => $projet->use_teams,
        ]);
    }

    /**
     * Retourne les candidats assignables pour une tâche/activité du projet.
     *
     * Si use_teams = true : union des membres des équipes liées + membres du projet.
     * Si use_teams = false : membres du workspace.
     */
    public function candidates(Request $request, Projet $projet): AnonymousResourceCollection
    {
        $this->authorize('view', $projet);

        $projet->loadMissing(['workspace.members']);

        if ($projet->use_teams) {
            // Membres des équipes liées au projet + membres directs du projet
            $teamMemberIds = Team::query()
                ->where('project_id', $projet->id)
                ->with('members')
                ->get()
                ->flatMap(fn (Team $t) => $t->members->pluck('id'))
                ->unique();

            $projectMemberIds = $projet->members()->pluck('users.id');

            $candidateIds = $teamMemberIds->merge($projectMemberIds)->unique()->values();

            $candidates = User::query()
                ->whereIn('id', $candidateIds)
                ->get();
        } else {
            // Tous les membres du workspace
            $candidates = $projet->workspace->members()
                ->whereNull('workspace_members.banned_at')
                ->get();
        }

        return UserResource::collection($candidates);
    }
}
