<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Projet;
use App\Models\Activite;
use App\Models\Tache;
use App\Models\TacheResultat;
use Illuminate\Support\Facades\Log;

/**
 * Service de résolution des permissions sur les documents
 * 
 * Gère la logique complexe d'accès aux documents selon :
 * - La hiérarchie (Workspace → Projet → Activité → Tâche → Résultat)
 * - Les rôles et permissions des utilisateurs
 * - Les permissions explicites sur les documents
 */
class DocumentAccessResolver
{
    /**
     * Vérifie si un utilisateur peut voir un document
     */
    public function canView(User $user, Document $document): bool
    {
        // Super admin a tous les droits
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Owner du document
        if ($document->user_id === $user->id) {
            return true;
        }

        // Document public
        if ($document->visibility === 'public') {
            return true;
        }

        // Permission explicite sur le document
        if ($this->hasDirectPermission($user, $document, 'can_view')) {
            return true;
        }

        // Permissions héritées selon le type de parent
        return $this->hasContextualAccess($user, $document, 'view');
    }

    /**
     * Vérifie si un utilisateur peut télécharger un document
     */
    public function canDownload(User $user, Document $document): bool
    {
        // Super admin a tous les droits
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Owner du document
        if ($document->user_id === $user->id) {
            return true;
        }

        // Permission explicite
        if ($this->hasDirectPermission($user, $document, 'can_download')) {
            return true;
        }

        // Permissions contextuelles
        return $this->hasContextualAccess($user, $document, 'download');
    }

    /**
     * Vérifie si un utilisateur peut modifier un document
     */
    public function canEdit(User $user, Document $document): bool
    {
        // Super admin a tous les droits
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Owner du document
        if ($document->user_id === $user->id) {
            return true;
        }

        // Permission explicite
        if ($this->hasDirectPermission($user, $document, 'can_edit')) {
            return true;
        }

        // Permissions contextuelles (plus restrictives)
        return $this->hasContextualAccess($user, $document, 'edit');
    }

    /**
     * Vérifie si un utilisateur peut supprimer un document
     */
    public function canDelete(User $user, Document $document): bool
    {
        // Super admin a tous les droits
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Owner du document
        if ($document->user_id === $user->id) {
            return true;
        }

        // Permission explicite
        if ($this->hasDirectPermission($user, $document, 'can_delete')) {
            return true;
        }

        // Permissions contextuelles (très restrictives)
        return $this->hasContextualAccess($user, $document, 'delete');
    }

    /**
     * Vérifie si un utilisateur peut partager un document
     */
    public function canShare(User $user, Document $document): bool
    {
        // Super admin a tous les droits
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Owner du document
        if ($document->user_id === $user->id) {
            return true;
        }

        // Permission explicite
        if ($this->hasDirectPermission($user, $document, 'can_share')) {
            return true;
        }

        // Permissions contextuelles
        return $this->hasContextualAccess($user, $document, 'share');
    }

    /**
     * Vérifie si un utilisateur peut uploader un document sur une entité
     */
    public function canUpload(User $user, string $entityType, int $entityId): bool
    {
        // Super admin a tous les droits
        if ($user->isSuperAdmin()) {
            return true;
        }

        switch ($entityType) {
            case Workspace::class:
                return $this->canUploadToWorkspace($user, $entityId);

            case Projet::class:
                return $this->canUploadToProjet($user, $entityId);

            case Activite::class:
                return $this->canUploadToActivite($user, $entityId);

            case Tache::class:
                return $this->canUploadToTache($user, $entityId);

            case TacheResultat::class:
                return $this->canUploadToResultat($user, $entityId);

            default:
                return false;
        }
    }

    /**
     * ===================================================================
     * PERMISSIONS DIRECTES (DocumentPermission)
     * ===================================================================
     */

    protected function hasDirectPermission(User $user, Document $document, string $permission): bool
    {
        $perm = $document->permissions()
            ->where('permissionable_type', User::class)
            ->where('permissionable_id', $user->id)
            ->where($permission, true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $perm !== null;
    }

    /**
     * ===================================================================
     * PERMISSIONS CONTEXTUELLES (Héritées de la hiérarchie)
     * ===================================================================
     */

    protected function hasContextualAccess(User $user, Document $document, string $action): bool
    {
        if (!$document->documentable) {
            return false;
        }

        $entityType = $document->documentable_type;
        $entity = $document->documentable;

        switch ($entityType) {
            case Workspace::class:
                return $this->checkWorkspaceAccess($user, $entity, $action);

            case Projet::class:
                return $this->checkProjetAccess($user, $entity, $action);

            case Activite::class:
                return $this->checkActiviteAccess($user, $entity, $action);

            case Tache::class:
                return $this->checkTacheAccess($user, $entity, $action);

            case TacheResultat::class:
                return $this->checkResultatAccess($user, $entity, $action);

            default:
                return false;
        }
    }

    /**
     * ===================================================================
     * WORKSPACE : Permissions sur les documents du workspace
     * ===================================================================
     */

   protected function checkWorkspaceAccess(User $user, Workspace $workspace, string $action): bool
    {
        if ($workspace->owner_id === $user->id) {
            return true;
        }

        $member = $workspace->members()->where('user_id', $user->id)->first();
        if (!$member) {
            return false;
        }

        $role = $member->pivot->role ?? null;
        $permissions = $member->pivot->permissions ?? [];
        
        // FIX: Décoder si JSON string
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        // Admins ont tous les droits par défaut
        if ($role === 'admin' || $role === 'owner') {
            return true;
        }

        // Vérifier la permission spécifique
        return $this->checkPermissionInArray($permissions, "documents_{$action}");
    }

    /**
     * ===================================================================
     * PROJET : Permissions sur les documents du projet
     * ===================================================================
     */

    protected function checkProjetAccess(User $user, Projet $projet, string $action): bool
    {
        // Responsable du projet (toutes les permissions)
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // Admin du workspace parent
        if ($projet->workspace && $this->checkWorkspaceAccess($user, $projet->workspace, $action)) {
            return true;
        }

        // Membre du projet avec permissions
        $member = $projet->members()->where('user_id', $user->id)->first();
        if (!$member) {
            return false;
        }

        // Mapping des actions vers les permissions pivot
        $permissionMap = [
            'view' => 'can_edit', // Si peut éditer le projet, peut voir ses documents
            'download' => 'can_edit',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
            'share' => 'can_invite', // Si peut inviter, peut partager
        ];

        $pivotPermission = $permissionMap[$action] ?? null;

        if ($pivotPermission && isset($member->pivot->{$pivotPermission})) {
            return $member->pivot->{$pivotPermission} === true;
        }

        return false;
    }

    /**
     * ===================================================================
     * ACTIVITÉ : Permissions sur les documents de l'activité
     * ===================================================================
     */

    protected function checkActiviteAccess(User $user, Activite $activite, string $action): bool
    {
        // Responsable de l'activité (toutes les permissions)
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet parent
        if ($activite->projet && $activite->projet->responsable_id === $user->id) {
            return true;
        }

        // Admin du workspace
        if ($activite->projet && $activite->projet->workspace) {
            if ($this->checkWorkspaceAccess($user, $activite->projet->workspace, $action)) {
                return true;
            }
        }

        // Membre de l'activité avec permissions
        $member = $activite->membres()->where('user_id', $user->id)->first();
        if (!$member) {
            return false;
        }

        // Mapping des actions vers les permissions pivot
        $permissionMap = [
            'view' => 'can_edit_activity',
            'download' => 'can_edit_activity',
            'edit' => 'can_edit_activity',
            'delete' => 'can_delete_activity',
            'share' => 'can_assign_users',
        ];

        $pivotPermission = $permissionMap[$action] ?? null;

        if ($pivotPermission && isset($member->pivot->{$pivotPermission})) {
            return $member->pivot->{$pivotPermission} === true;
        }

        return false;
    }

    /**
     * ===================================================================
     * TÂCHE : Permissions sur les documents de la tâche
     * ===================================================================
     */

    protected function checkTacheAccess(User $user, Tache $tache, string $action): bool
    {
        // Responsable de l'activité parente
        if ($tache->activite && $tache->activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet parent
        if ($tache->activite && $tache->activite->projet && $tache->activite->projet->responsable_id === $user->id) {
            return true;
        }

        // Admin du workspace
        if ($tache->activite && $tache->activite->projet && $tache->activite->projet->workspace) {
            if ($this->checkWorkspaceAccess($user, $tache->activite->projet->workspace, $action)) {
                return true;
            }
        }

        // Assigné à la tâche
        $assignee = $tache->assignees()->where('user_id', $user->id)->first();
        if (!$assignee) {
            return false;
        }

        // Mapping des actions vers les permissions pivot
        $permissionMap = [
            'view' => 'can_edit',
            'download' => 'can_edit',
            'edit' => 'can_edit',
            'delete' => 'can_complete', // Seuls ceux qui peuvent compléter peuvent supprimer
            'share' => 'can_edit',
        ];

        $pivotPermission = $permissionMap[$action] ?? null;

        if ($pivotPermission && isset($assignee->pivot->{$pivotPermission})) {
            return $assignee->pivot->{$pivotPermission} === true;
        }

        // Par défaut, un assigné peut voir et télécharger
        if (in_array($action, ['view', 'download'])) {
            return true;
        }

        return false;
    }

    /**
     * ===================================================================
     * RÉSULTAT DE TÂCHE : Permissions sur les documents du résultat
     * ===================================================================
     */

    protected function checkResultatAccess(User $user, TacheResultat $resultat, string $action): bool
    {
        // L'assigné qui a soumis le résultat (toutes les permissions)
        if ($resultat->user_id === $user->id) {
            return true;
        }

        // Validateurs N1 et N2 peuvent voir et télécharger
        if ($resultat->tache) {
            $tache = $resultat->tache;

            // Responsable de l'activité (Validateur N1)
            if ($tache->activite && $tache->activite->responsable_id === $user->id) {
                return in_array($action, ['view', 'download', 'share']);
            }

            // Responsable du projet (Validateur N2)
            if ($tache->activite && $tache->activite->projet && $tache->activite->projet->responsable_id === $user->id) {
                return in_array($action, ['view', 'download', 'share']);
            }

            // Membre de l'activité avec permission de validation
            $membre = $tache->activite->membres()->where('user_id', $user->id)->first();
            if ($membre && $membre->pivot->can_validate_results) {
                return in_array($action, ['view', 'download']);
            }
        }

        // Admin du workspace
        if ($resultat->tache && $resultat->tache->activite && $resultat->tache->activite->projet && $resultat->tache->activite->projet->workspace) {
            if ($this->checkWorkspaceAccess($user, $resultat->tache->activite->projet->workspace, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ===================================================================
     * UPLOAD : Qui peut uploader des documents ?
     * ===================================================================
     */

    protected function canUploadToWorkspace(User $user, int $workspaceId): bool
    {
        $workspace = Workspace::find($workspaceId);
        if (!$workspace) {
            return false;
        }

        // Owner du workspace
        if ($workspace->owner_id === $user->id) {
            return true;
        }

        // Admin du workspace
        $member = $workspace->members()->where('user_id', $user->id)->first();
        if ($member && in_array($member->pivot->role, ['owner', 'admin'])) {
            return true;
        }

        return false;
    }

    protected function canUploadToProjet(User $user, int $projetId): bool
    {
        $projet = Projet::find($projetId);
        if (!$projet) {
            return false;
        }

        // Responsable du projet
        if ($projet->responsable_id === $user->id) {
            return true;
        }

        // Admin du workspace parent
        if ($projet->workspace && $this->canUploadToWorkspace($user, $projet->workspace->id)) {
            return true;
        }

        // Membre du projet avec permission can_edit
        $member = $projet->members()->where('user_id', $user->id)->first();
        return $member && ($member->pivot->can_edit ?? false);
    }

    protected function canUploadToActivite(User $user, int $activiteId): bool
    {
        $activite = Activite::find($activiteId);
        if (!$activite) {
            return false;
        }

        // Responsable de l'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet parent
        if ($activite->projet && $activite->projet->responsable_id === $user->id) {
            return true;
        }

        // Admin du workspace
        if ($activite->projet && $activite->projet->workspace && $this->canUploadToWorkspace($user, $activite->projet->workspace->id)) {
            return true;
        }

        // Membre de l'activité avec permission can_edit_activity
        $member = $activite->membres()->where('user_id', $user->id)->first();
        return $member && ($member->pivot->can_edit_activity ?? false);
    }

    protected function canUploadToTache(User $user, int $tacheId): bool
    {
        $tache = Tache::find($tacheId);
        if (!$tache) {
            return false;
        }

        // Responsable de l'activité parente
        if ($tache->activite && $tache->activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet parent
        if ($tache->activite && $tache->activite->projet && $tache->activite->projet->responsable_id === $user->id) {
            return true;
        }

        // Admin du workspace
        if ($tache->activite && $tache->activite->projet && $tache->activite->projet->workspace && $this->canUploadToWorkspace($user, $tache->activite->projet->workspace->id)) {
            return true;
        }

        // Assigné à la tâche
        return $tache->assignees()->where('user_id', $user->id)->exists();
    }

    protected function canUploadToResultat(User $user, int $resultatId): bool
    {
        $resultat = TacheResultat::find($resultatId);
        if (!$resultat) {
            return false;
        }

        // Seul l'assigné peut uploader sur son propre résultat
        return $resultat->user_id === $user->id;
    }

    /**
     * ===================================================================
     * HELPERS
     * ===================================================================
     */

    protected function checkPermissionInArray($permissions, string $permissionKey): bool
    {
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        if (!is_array($permissions)) {
            return false;
        }

        return in_array($permissionKey, $permissions) || in_array('all', $permissions);
    }

    /**
     * Log des vérifications de permissions (pour debug)
     */
    protected function logPermissionCheck(User $user, Document $document, string $action, bool $granted): void
    {
        Log::debug('Document Permission Check', [
            'user_id' => $user->id,
            'document_id' => $document->id,
            'documentable_type' => $document->documentable_type,
            'action' => $action,
            'granted' => $granted,
        ]);
    }
}