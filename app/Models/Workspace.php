<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role;

class Workspace extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nom',
        'description',
        'code',
        'owner_id',
        'settings',
        'is_active',
        'logo',
        'subscription_mode',
        'trial_started_at',
        'trial_duration_days',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_started_at' => 'datetime',
        'trial_duration_days' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'logo_url',
        'member_count',
        'projet_count',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Boot method pour générer le code et initialiser les settings
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workspace) {
            if (empty($workspace->code)) {
                $workspace->code = static::generateUniqueCode();
            }

            if (empty($workspace->settings)) {
                $workspace->settings = static::getDefaultSettings();
            }

            // Initialise le mode d'essai à la création
            if (empty($workspace->subscription_mode)) {
                $workspace->subscription_mode = 'trial';
            }
            if (empty($workspace->trial_started_at)) {
                $workspace->trial_started_at = now();
            }
            if (empty($workspace->trial_duration_days)) {
                $workspace->trial_duration_days = config('subscription.trial_duration_days', 30);
            }
        });
    }

    /**
     * Paramètres par défaut du workspace
     */
    public static function getDefaultSettings(): array
    {
        return [
            'language' => 'fr',
            'timezone' => 'Africa/Douala',
            'visibility' => 'private',
            'weekly_digest' => false,
            'members_can_invite' => true,
            'members_can_create_projects' => true,
            'members_can_delete_projects' => false,
            'notify_on_new_member' => true,
            'notify_on_new_project' => true,
            'notify_on_task_assigned' => true,
            'notify_on_deadline_approaching' => true,
            'require_task_validation' => true,
            'require_approval_for_time_off' => true,
            'default_project_visibility' => 'team',
            'validation_timeout_hours' => 48, // R8: 24–168h, default 48h
        ];
    }

    /**
     * Activity logging configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nom', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Generate unique workspace code
     */
    public static function generateUniqueCode(): string
    {
        do {
            $latest = static::withTrashed()->latest('id')->first();
            $nextId = $latest ? $latest->id + 1 : 1;
            $code = 'WS-'.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the owner of the workspace
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Accessor pour décoder automatiquement permissions
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot(['role_id', 'invited_at', 'invited_by'])
            ->withTimestamps();
    }

    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot(['role_id', 'invited_at', 'invited_by'])
            ->withTimestamps()
            ->using(new class extends Pivot
            {
                protected $casts = [
                    'invited_at' => 'datetime',
                ];
            });
    }

    /**
     * Get all projects in this workspace
     */
    public function projets(): HasMany
    {
        return $this->hasMany(Projet::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    /**
     * Accesseur pour obtenir une valeur de setting
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Mutateur pour définir une valeur de setting
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Mettre à jour plusieurs settings à la fois
     */
    public function updateSettings(array $newSettings): void
    {
        $currentSettings = $this->settings ?? [];
        $this->settings = array_merge($currentSettings, $newSettings);
        $this->save();
    }

    /**
     * Réinitialiser les settings aux valeurs par défaut
     */
    public function resetSettings(): void
    {
        $this->settings = static::getDefaultSettings();
        $this->save();
    }

    /**
     * Get active projects
     */
    public function activeProjets()
    {
        return $this->projets()->where('status', 'active');
    }

    /**
     * Get completed projects
     */
    public function completedProjets()
    {
        return $this->projets()->where('status', 'completed');
    }

    /**
     * Get archived projects
     */
    public function archivedProjets()
    {
        return $this->projets()->where('status', 'archived');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('owner_id', $userId)
                ->orWhereHas('members', function ($memberQuery) use ($userId) {
                    $memberQuery->where('user_id', $userId);
                });
        });
    }

    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('owner_id', $userId);
    }

    public function scopeAccessibleBy($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('owner_id', $userId)
                ->orWhereHas('members', function ($memberQuery) use ($userId) {
                    $memberQuery->where('user_id', $userId);
                });
        });
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('nom', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        });
    }

    /**
     * Accessors
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return $this->logo ? asset('uploads/'.$this->logo) : null;

        }

        return null;
    }

    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    public function getProjetCountAttribute(): int
    {
        return $this->projets()->count();
    }

    /**
     * Helper Methods
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function hasAccess(User $user): bool
    {
        return $user->isSuperAdmin()
            || $this->owner_id === $user->id
            || $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Get member role.
     */
    public function getMemberRole(User $user): ?string
    {
        if ($this->owner_id === $user->id) {
            return 'owner';
        }

        $member = $this->members()->where('user_id', $user->id)->first();

        return $member ? (Role::find($member->pivot->role_id)?->name) : null;
    }

    /**
     * Vérifie si un utilisateur est Owner ou Manager du workspace
     */
    public function isOwnerOrAdmin(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->owner_id === $user->id) {
            return true;
        }

        $roleName = $this->getMemberRole($user);

        return in_array($roleName, ['owner', 'manager']);
    }

    /**
     * Add a member to workspace
     */
    public function addMember(User $user, string $roleName = 'collaborateur'): void
    {
        if (! $this->isMember($user)) {
            $role = Role::findByName($roleName, 'web');
            $this->members()->attach($user->id, [
                'role_id' => $role->id,
                'invited_at' => now(),
                'invited_by' => auth()->id(),
            ]);
        }
    }

    public function removeMember(User $targetUser): void
    {
        if ($this->isOwner($targetUser)) {
            throw new \Exception('Impossible de supprimer le proprietaire du workspace.');
        }

        $this->members()->detach($targetUser->id);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(User $targetUser, string $roleName): void
    {
        if ($this->isOwner($targetUser)) {
            throw new \Exception('Impossible de modifier le proprietaire du workspace.');
        }

        $role = Role::findByName($roleName, 'web');
        $this->members()->updateExistingPivot($targetUser->id, ['role_id' => $role->id]);
    }

    /**
     * Get all activities across all projects in workspace
     */
    public function allActivites()
    {
        return Activite::whereIn('projet_id', $this->projets()->pluck('id'));
    }

    /**
     * Get all tasks across all projects in workspace
     */
    public function allTaches()
    {
        $activiteIds = $this->allActivites()->pluck('id');

        return Tache::whereIn('activite_id', $activiteIds);
    }

    /**
     * Calculate workspace statistics
     */
    public function getStatistics(): array
    {
        $projets = $this->projets;
        $activites = $this->allActivites()->get();
        $taches = $this->allTaches()->get();

        return [
            'total_projets' => $projets->count(),
            'projets_actifs' => $projets->where('status', 'active')->count(),
            'projets_termines' => $projets->where('status', 'completed')->count(),
            'total_activites' => $activites->count(),
            'total_members' => $this->members()->count() + 1, // +1 pour l'owner
            'total_taches' => $taches->count(),
            'taches_terminees' => $taches->where('statut', 'termine')->count(),
            'taches_en_cours' => $taches->where('statut', 'en_cours')->count(),
            'taches_en_retard' => $taches->filter(fn ($t) => $t->isOverdue())->count(),
            'taux_completion' => $taches->count() > 0
                ? round(($taches->where('statut', 'termine')->count() / $taches->count()) * 100, 2) : 0,
            'progression_moyenne' => $projets->count() > 0
                ? round($projets->avg('progression'), 2) : 0,
        ];
    }

    /**
     * Archive the workspace
     */
    public function archive(): void
    {
        $this->update(['is_active' => false]);

        // Optionally archive all projects
        $this->projets()->update(['status' => 'archived']);
    }

    /**
     * Vérifie si un user peut voir TOUS les projets du workspace
     */
    public function canSeeAllProjects(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Récupère les projets accessibles pour un user
     */
    public function getAccessibleProjects(User $user)
    {
        // Si peut voir tout, retourner tous les projets
        if ($this->canSeeAllProjects($user)) {
            return $this->projets();
        }

        // Sinon, uniquement les projets où il est impliqué
        return $this->projets()->where(function ($q) use ($user) {
            $q->where('responsable_id', $user->id)
                ->orWhereHas('members', function ($mq) use ($user) {
                    $mq->where('user_id', $user->id);
                });
        });
    }

    /**
     * Activate the workspace
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Mutator pour garantir que settings est toujours un array
     */
    public function setSettingsAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $this->attributes['settings'] = json_encode(
                is_array($decoded) ? $decoded : []
            );
        } elseif (is_array($value)) {
            $this->attributes['settings'] = json_encode($value);
        } elseif (is_null($value)) {
            $this->attributes['settings'] = json_encode([]);
        } else {
            // Si c'est un autre type, forcer un array vide
            $this->attributes['settings'] = json_encode([]);
        }
    }

    /**
     * Accessor pour garantir que settings est toujours un array
     */
    public function getSettingsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        // Si le décodage échoue ou ne retourne pas un array, retourner un array vide
        return is_array($decoded) ? $decoded : [];
    }
}
