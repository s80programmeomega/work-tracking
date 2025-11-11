<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
            ->withPivot(['role', 'can_edit', 'can_delete', 'can_invite'])
            ->withTimestamps();
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
    public function scopeAccessibleBy($query, $userId)
    {
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
                            $memberQuery->where('user_id', $userId)
                                ->whereIn('role', ['owner', 'super_admin', 'admin']);
                        });
                    });
                });
        });
    }

    /**
     * ✅ NOUVEAU : Vérifier si user peut voir TOUS les projets du workspace
     */
    public function userCanSeeAllWorkspaceProjects(User $user): bool
    {
        if (!$this->workspace) {
            return false;
        }

        // Owner du workspace
        if ($this->workspace->owner_id === $user->id) {
            return true;
        }

        // Admin du workspace
        $member = $this->workspace->members()->where('user_id', $user->id)->first();
        if ($member && in_array($member->pivot->role, ['super_admin', 'admin'])) {
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
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function hasAccess(User $user): bool
    {
        // Owner has access
        if ($this->isResponsable($user)) {
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

        return false;
    }

    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->role;
    }

    public function canUserEdit(User $user): bool
    {
        if ($this->isResponsable($user)) {
            return true;
        }

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
}