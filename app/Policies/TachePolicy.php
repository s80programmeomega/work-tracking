<?php

namespace App\Policies;

use App\Models\Tache;
use App\Models\User;
use App\Models\Activite;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * 🔐 TachePolicy - Gestion des permissions sur les tâches
 * 
 * Hiérarchie de permissions:
 * 1. Super Admin → Accès total
 * 2. Workspace Owner/Admin → Visibilité + Lecture
 * 3. Responsable Projet → Validation N2 + Gestion
 * 4. Responsable Activité → Validation N1 + Gestion
 * 5. Membre Activité → Selon permissions activite_user
 * 6. Assigné Tâche → Selon permissions tache_user
 */
class TachePolicy
{
    use HandlesAuthorization;

    /**
     * ✅ Autoriser TOUTES les actions pour super admin
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * ✅ Voir la liste des tâches (avec filtres)
     */
    public function viewAny(User $user): bool
    {
        // Tout utilisateur authentifié peut voir ses propres tâches
        return true;
    }

    /**
     * ✅ Voir UNE tâche spécifique
     */
    public function view(User $user, Tache $tache): bool
    {
        $activite = $tache->activite;
        if (!$activite) {
            return false;
        }

        $projet = $activite->projet;
        if (!$projet) {
            return false;
        }

        // 1️⃣ Workspace Owner/Admin → Voit tout
        $workspace = $projet->workspace;
        if ($workspace && $workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        // 2️⃣ Responsable de projet
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // 3️⃣ Responsable d'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // 4️⃣ Membre du projet avec visibilité
        if ($projet->isMember($user)) {
            // Vérifier la visibilité de la tâche
            if ($tache->visibility === 'public') {
                return true;
            }

            if ($tache->visibility === 'members_only') {
                // Membre d'activité OU assigné
                return $activite->isMember($user) || $tache->isAssignedTo($user);
            }

            // visibility === 'private' → Seulement assignés
            return $tache->isAssignedTo($user);
        }

        // 5️⃣ Membre de l'activité
        if ($activite->isMember($user)) {
            if ($tache->visibility === 'public' || $tache->visibility === 'members_only') {
                return true;
            }
            return $tache->isAssignedTo($user);
        }

        // 6️⃣ Assigné à la tâche
        return $tache->isAssignedTo($user);
    }

    /**
     * ✅ Créer une tâche
     */
    public function create(User $user, Activite $activite): bool
    {
        $projet = $activite->projet;
        if (!$projet) {
            return false;
        }

        // 1️⃣ Workspace Owner/Admin
        $workspace = $projet->workspace;
        if ($workspace && $workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        // 2️⃣ Responsable de projet
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // 3️⃣ Responsable d'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // 4️⃣ Membre projet avec permission can_create (projet_user)
        $projetMember = $projet->membres()->where('user_id', $user->id)->first();
        if ($projetMember && $projetMember->pivot->can_edit) {
            return true;
        }

        // 5️⃣ Membre activité avec permission can_create_tasks
        $activiteMember = $activite->membres()->where('user_id', $user->id)->first();
        if ($activiteMember && $activiteMember->pivot->can_create_tasks) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Modifier une tâche
     */
    public function update(User $user, Tache $tache): bool
    {
        $activite = $tache->activite;
        if (!$activite) {
            return false;
        }

        $projet = $activite->projet;
        if (!$projet) {
            return false;
        }

        // 1️⃣ Workspace Owner/Admin
        $workspace = $projet->workspace;
        if ($workspace && $workspace->isOwnerOrAdmin($user)) {
            return true;
        }

        // 2️⃣ Responsable de projet
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // 3️⃣ Responsable d'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // 4️⃣ Membre activité avec permission can_edit_tasks
        $activiteMember = $activite->membres()->where('user_id', $user->id)->first();
        if ($activiteMember && $activiteMember->pivot->can_edit_tasks) {
            return true;
        }

        // 5️⃣ Assigné avec permission can_edit (tache_user)
        $assignment = $tache->assignees()->where('user_id', $user->id)->first();
        if ($assignment && ($assignment->pivot->can_edit ?? false)) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Supprimer une tâche
     */
    public function delete(User $user, Tache $tache): bool
    {
        $activite = $tache->activite;
        if (!$activite) {
            return false;
        }

        $projet = $activite->projet;
        if (!$projet) {
            return false;
        }

        // 1️⃣ Workspace Owner
        $workspace = $projet->workspace;
        if ($workspace && $workspace->owner_id === $user->id) {
            return true;
        }

        // 2️⃣ Responsable de projet
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // 3️⃣ Responsable d'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // 4️⃣ Membre activité avec permission can_delete_tasks
        $activiteMember = $activite->membres()->where('user_id', $user->id)->first();
        if ($activiteMember && $activiteMember->pivot->can_delete_tasks) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Marquer comme terminé
     */
    public function complete(User $user, Tache $tache): bool
    {
        // 1️⃣ Assigné avec permission can_complete
        $assignment = $tache->assignees()->where('user_id', $user->id)->first();
        if ($assignment && ($assignment->pivot->can_complete ?? true)) {
            return true;
        }

        // 2️⃣ Responsable d'activité
        $activite = $tache->activite;
        if ($activite && $activite->responsable_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Valider N1 (Niveau Activité)
     */
    public function validateN1(User $user, Tache $tache): bool
    {
        if (!$tache->validation_n1_required) {
            return false;
        }

        if ($tache->validated_n1_at) {
            return false; // Déjà validé
        }

        $activite = $tache->activite;
        if (!$activite) {
            return false;
        }

        // 1️⃣ Responsable d'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // 2️⃣ Membre activité avec permission can_validate_results
        $activiteMember = $activite->membres()->where('user_id', $user->id)->first();
        if ($activiteMember && $activiteMember->pivot->can_validate_results) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Valider N2 (Niveau Projet - Manager)
     */
    public function validateN2(User $user, Tache $tache): bool
    {
        if (!$tache->validation_n2_required) {
            return false;
        }

        // N1 doit être validé d'abord
        if (!$tache->validated_n1_at) {
            return false;
        }

        if ($tache->validated_n2_at) {
            return false; // Déjà validé
        }

        $activite = $tache->activite;
        if (!$activite) {
            return false;
        }

        $projet = $activite->projet;
        if (!$projet) {
            return false;
        }

        // 1️⃣ Responsable du projet
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // 2️⃣ Workspace Owner (validation finale)
        $workspace = $projet->workspace;
        if ($workspace && $workspace->owner_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Assigner des utilisateurs
     */
    public function assignUsers(User $user, Tache $tache): bool
    {
        $activite = $tache->activite;
        if (!$activite) {
            return false;
        }

        // 1️⃣ Responsable d'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // 2️⃣ Membre activité avec permission can_assign_users
        $activiteMember = $activite->membres()->where('user_id', $user->id)->first();
        if ($activiteMember && $activiteMember->pivot->can_assign_users) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Archiver/Désarchiver
     */
    public function archive(User $user, Tache $tache): bool
    {
        return $this->update($user, $tache);
    }

    /**
     * ✅ Dupliquer
     */
    public function duplicate(User $user, Tache $tache): bool
    {
        // Doit pouvoir voir ET créer
        return $this->view($user, $tache) && $this->create($user, $tache->activite);
    }
}