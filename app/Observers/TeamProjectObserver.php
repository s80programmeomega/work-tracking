<?php

namespace App\Observers;

use App\Events\Teams\TeamLinkedToProject;
use App\Events\Teams\TeamUnlinkedFromProject;
use App\Models\Projet;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Teams\TeamMemberAutoAddedNotification;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class TeamProjectObserver
{
    /**
     * Synchronise les membres de l'équipe dans projet_user lors d'un lien équipe→projet.
     * Les membres existants à un rôle supérieur ne sont pas rétrogradés.
     */
    public function onLinked(TeamLinkedToProject $event): void
    {
        $team = $event->team;
        $projet = $event->projet;

        $collaborateurRole = Role::findByName('collaborateur', 'web');

        $team->members()->get()->each(function (User $user) use ($team, $projet, $collaborateurRole): void {
            // Ne pas ajouter si déjà membre à un rôle supérieur
            $existing = $projet->members()->where('user_id', $user->id)->first();

            if ($existing !== null) {
                return;
            }

            $projet->members()->attach($user->id, [
                'role_id' => $collaborateurRole->id,
                'can_edit' => false,
                'can_delete' => false,
                'can_invite' => false,
                'can_delete_member' => false,
                'can_create_activity' => false,
                'can_edit_activity' => false,
                'can_delete_activity' => false,
            ]);

            Log::info('Membre ajouté au projet via équipe liée', [
                'user_id' => $user->id,
                'team_id' => $team->id,
                'projet_id' => $projet->id,
            ]);

            // Notification async au membre ajouté automatiquement
            $user->notify(new TeamMemberAutoAddedNotification($projet, $team));
        });
    }

    /**
     * Retire du projet les membres qui n'appartiennent qu'à cette équipe
     * (pas membres directs ni responsable).
     */
    public function onUnlinked(TeamUnlinkedFromProject $event): void
    {
        $team = $event->team;
        $projet = $event->projet;

        // IDs des membres issus uniquement de cette équipe (pas membres directs)
        // On ne retire que les collaborateurs dont l'accès vient exclusivement de cette équipe.
        // La détection exacte est conservative : on ne retire que si l'utilisateur
        // n'est membre d'aucune autre équipe liée au projet et n'est pas responsable.
        $remainingTeamMemberIds = Team::query()
            ->where('project_id', $projet->id)
            ->where('id', '!=', $team->id)
            ->with('members')
            ->get()
            ->flatMap(fn (Team $t) => $t->members->pluck('id'))
            ->unique();

        $team->members()->get()->each(function (User $user) use ($team, $projet, $remainingTeamMemberIds): void {
            // Garder si c'est le responsable du projet
            if ($projet->responsable_id === $user->id) {
                return;
            }

            // Garder si encore membre d'une autre équipe liée
            if ($remainingTeamMemberIds->contains($user->id)) {
                return;
            }

            $projet->members()->detach($user->id);

            Log::info('Membre retiré du projet suite à déliaison équipe', [
                'user_id' => $user->id,
                'team_id' => $team->id,
                'projet_id' => $projet->id,
            ]);
        });
    }
}
