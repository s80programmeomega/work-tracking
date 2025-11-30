<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class Projet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'nom',
        'description',
        'code',
        'date_debut',
        'date_fin',
        'responsable_id',
        'status',
        'visibility',
        'couleur',
        'budget',
        'progression',
        'is_template',
        'is_favorite',
        'objectifs',
        'metadata',
        'archived_at',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'budget' => 'decimal:2',
        'progression' => 'integer',
        'is_template' => 'boolean',
        'is_favorite' => 'boolean',
        'metadata' => 'array',
        'archived_at' => 'datetime',
    ];

    protected $appends = [
        'is_overdue',
        'days_remaining',
        'member_count',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($projet) {
            if (empty($projet->code)) {
                $projet->code = static::generateUniqueCode();
            }

            // Met à jour la progression quand une activité est modifiée

            // Si des activités ont été modifiées, recalculer la progression
            if ($projet->isDirty('progression') === false) {
                $projet->updateAutoProgression();
            }
        });
    }


    public static function generateUniqueCode(): string
    {
        do {
            $latestProjet = static::withTrashed()->latest('id')->first();
            $nextId = $latestProjet ? $latestProjet->id + 1 : 1;
            $code = 'PROJ-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function documents()
{
    return $this->morphMany(Document::class, 'documentable');
}

    /**
     * Relationships
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projet_user')
            ->withPivot(['role', 'can_edit', 'can_delete', 'can_invite', 'can_delete_member'])
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canUserValidateN2(User $user): bool
    {
        // Responsable du projet = Manager N2
        return $this->responsable_id === $user->id;
    }
    public function isAccessibleBy(User $user): bool
    {
        // Super admin
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Workspace owner/admin can see all projects
        if ($this->workspace && $user->canSeeAllWorkspaceProjects($this->workspace)) {
            return true;
        }

        // Responsable du projet
        if ($this->responsable_id === $user->id) {
            return true;
        }

        // Membre du projet
        if ($this->isMember($user)) {
            return true;
        }

        // Accès temporaire
        return $user->hasTemporaryAccess($this);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProjetTag::class, 'projet_projet_tag')
            ->withTimestamps();
    }

    public function activites(): HasMany
    {
        return $this->hasMany(Activite::class);
    }

    public function taches()
    {
        return $this->hasManyThrough(Tache::class, Activite::class);
    }

    /**
     * Calculate project progression based on tasks
     */
    public function calculateProgression(): int
    {
        $totalTaches = $this->taches()->count();
        if ($totalTaches === 0) {
            return 0;
        }

        $completedTaches = $this->taches()
            ->where('statut', 'termine')
            ->count();

        return (int) round(($completedTaches / $totalTaches) * 100);
    }

    public function updateProgression(): void
    {
        $this->update(['progression' => $this->calculateProgression()]);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    public function scopeTeam($query)
    {
        return $query->where('visibility', 'team');
    }

    public function scopeTemplate($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeFavorite($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('date_fin', '<', now())
            ->whereNotIn('status', ['completed', 'archived']);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('responsable_id', $userId)
                ->orWhereHas('members', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        });
    }

    public function scopeInWorkspace($query, $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('nom', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        });
    }

    /**
     * Scope pour filtrer les projets accessibles par un utilisateur
     */
    public function scopeAccessibleBy(Builder $query, $userId): Builder
    {
        $user = User::find($userId);

        // Super admin voit tout
        if ($user && $user->isSuperAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($userId) {
            // 1. Projets où l'user est responsable
            $q->where('responsable_id', $userId)

                // 2. OU projets où l'user est membre direct
                ->orWhereHas('members', function ($memberQuery) use ($userId) {
                    $memberQuery->where('user_id', $userId);
                })

                // 3. OU l'user est Owner/Admin du workspace
                ->orWhereHas('workspace', function ($workspaceQuery) use ($userId) {
                    $workspaceQuery->where(function ($wq) use ($userId) {
                        // Owner du workspace
                        $wq->where('owner_id', $userId)
                            // OU Admin/Super Admin du workspace
                            ->orWhereHas('members', function ($memberQuery) use ($userId) {
                            $memberQuery->where('workspace_members.user_id', $userId)
                                ->whereIn('workspace_members.role', ['owner', 'admin']);
                        });
                    });
                });
        });
    }

    public function getEligibleMembers()
    {
        $members = $this->members()
            ->where(function ($query) {
                $query->where('projet_members.can_edit', true)
                    ->orWhere('projet_members.can_assign_tasks', true);
            })
            ->get(['users.id', 'users.nom', 'users.email', 'users.avatar']);

        // Ajouter le responsable du projet s'il n'est pas déjà dans la liste
        if ($this->responsable && !$members->contains('id', $this->responsable->id)) {
            $members->prepend($this->responsable);
        }

        return $members->unique('id');
    }

    /**
     * ✅ NOUVEAU : Vérifier si user peut voir TOUS les projets du workspace
     */
    public function userCanSeeAllWorkspaceProjects(User $user): bool
    {
        if (!$this->workspace) {
            return false;
        }

        if ($user->isSuperAdmin())
            return true;
        if ($this->workspace->owner_id === $user->id)
            return true;

        // Admin du workspace
        $member = $this->workspace->members()->where('user_id', $user->id)->first();
        if ($member && in_array($member->pivot->role, ['owner', 'admin'])) {
            return true;
        }

        return false;
    }


    /**
     * Accessors
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->date_fin || in_array($this->status, ['completed', 'archived'])) {
            return false;
        }

        return $this->date_fin->isPast();
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->date_fin || in_array($this->status, ['completed', 'archived'])) {
            return null;
        }

        return now()->diffInDays($this->date_fin, false);
    }

    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Helper Methods
     */
    public function isResponsable(User $user): bool
    {
        return $this->responsable_id === $user->id;
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists()
            || $this->responsable_id === $user->id;
    }


    public function hasAccess(User $user): bool
    {
        // Super admin a toujours accès
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Owner has access
        if ($this->responsable_id === $user->id) {
            return true;
        }

        // Project member has access
        if ($this->isMember($user)) {
            return true;
        }

        // Workspace member has access
        if ($this->workspace && $this->workspace->hasAccess($user)) {
            return true;
        }

        // Si utilisateur est membre du projet
        return $this->members()->where('user_id', $user->id)->exists();


    }

    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->role;
    }

    public function canUserEdit(User $user): bool
    {
        if ($user->isSuperAdmin() || $this->isResponsable($user)) {
            return true;
        }

        // if ($this->isResponsable($user)) {
        //     return true;
        // }

        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->can_edit ?? false;
    }

    public function canUserDelete(User $user): bool
    {
        if ($this->isResponsable($user)) {
            return true;
        }

        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->can_delete ?? false;
    }

    public function canUserInvite(User $user): bool
    {
        if ($this->isResponsable($user)) {
            return true;
        }

        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->can_invite ?? false;
    }

    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'status' => 'active',
            'archived_at' => null,
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'progression' => 100,
        ]);
    }

    /**
     * Revoke user access to project and all related tasks/documents
     */
    public function revokeAccess(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Remove from project members
            $this->members()->detach($user->id);

            // Remove from all tasks in this project
            foreach ($this->activites as $activite) {
                foreach ($activite->taches as $tache) {
                    $tache->assignees()->detach($user->id);
                }
            }

            // Revoke document permissions
            DocumentPermission::where('permissionable_type', User::class)
                ->where('permissionable_id', $user->id)
                ->whereHas('document', function ($q) {
                    $q->where('documentable_type', Projet::class)
                        ->where('documentable_id', $this->id);
                })
                ->delete();

            // Log the action
            activity()
                ->causedBy(auth()->user())
                ->performedOn($this)
                ->withProperties(['revoked_user' => $user->id])
                ->log('access_revoked');
        });
    }

    /**
     * Get accessible tasks for a user
     */
    public function accessibleTachesFor(User $user)
    {
        return $this->taches()->where(function ($query) use ($user) {
            $query->whereHas('assignees', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->orWhere('visibility', 'public');
        });
    }


    /**
     * ✅ CALCUL DE LA PROGRESSION BASÉE SUR LA MOYENNE DES ACTIVITÉS AVEC POIDS
     */
    public function calculateAutoProgression(): int
    {
        // Si le projet est terminé ou archivé, on retourne la progression actuelle
        if (in_array($this->status, ['completed', 'archived'])) {
            return $this->progression ?? 100; // Projet terminé = 100%
        }

        $activites = $this->activites()->where('status', 'active')->get();

        // Si pas d'activités actives, progression à 0
        if ($activites->isEmpty()) {
            return 0;
        }

        $totalProgressionPonderee = 0;
        $totalPoids = 0;

        foreach ($activites as $activite) {
            // Calcul du poids de l'activité
            $poids = $this->calculateActivityWeight($activite);

            // Progression de l'activité (entre 0 et 100)
            $progressionActivite = $activite->progression ?? 0;

            // Contribution pondérée de cette activité
            $totalProgressionPonderee += $progressionActivite * $poids;
            $totalPoids += $poids;
        }

        // Éviter la division par zéro
        if ($totalPoids === 0) {
            return 0;
        }

        // Calcul de la moyenne pondérée
        $progressionMoyenne = (int) round($totalProgressionPonderee / $totalPoids);

        // Limiter entre 0 et 100
        return max(0, min(100, $progressionMoyenne));
    }

    /**
     * ✅ CALCULE LE POIDS D'UNE ACTIVITÉ POUR LA MOYENNE
     */
    private function calculateActivityWeight($activite): float
    {
        $poids = 1.0; // Poids de base

        // FACTEUR 1: Nombre de tâches (plus il y a de tâches, plus l'activité est importante)
        $tacheCount = $activite->tache_count ?? $activite->taches()->count();
        if ($tacheCount > 20) {
            $poids += 1.0; // Très importante
        } elseif ($tacheCount > 10) {
            $poids += 0.7; // Importante
        } elseif ($tacheCount > 5) {
            $poids += 0.4; // Moyennement importante
        } elseif ($tacheCount > 0) {
            $poids += 0.2; // Légèrement importante
        }

        // FACTEUR 2: Durée de l'activité (activités plus longues = plus de poids)
        if ($activite->date_debut && $activite->date_fin) {
            $dureeJours = $activite->date_debut->diffInDays($activite->date_fin);
            if ($dureeJours > 90) { // +3 mois
                $poids += 0.8;
            } elseif ($dureeJours > 30) { // +1 mois
                $poids += 0.4;
            }
        }

        // FACTEUR 3: Retard (activités en retard ont plus d'impact sur la progression globale)
        if ($activite->is_overdue) {
            $poids += 0.5;
        }

        // FACTEUR 4: Nombre de membres (activités avec plus de collaborateurs = plus importantes)
        $membresCount = $activite->membres_count ?? $activite->membres()->count();
        if ($membresCount > 5) {
            $poids += 0.6;
        } elseif ($membresCount > 2) {
            $poids += 0.3;
        }

        return $poids;
    }

    /**
     * ✅ Mise à jour automatique de la progression
     */
    public function updateAutoProgression(): void
    {
        $nouvelleProgression = $this->calculateAutoProgression();

        // Ne mettre à jour que si la progression a changé de manière significative
        if (abs(($this->progression ?? 0) - $nouvelleProgression) >= 1) {
            $this->update(['progression' => $nouvelleProgression]);
        }
    }



}