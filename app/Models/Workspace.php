<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Workspace extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'nom',
        'description',
        'code',
        'owner_id',
        'settings',
        'is_active',
        'logo',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'logo_url',
        'member_count',
        'projet_count',
    ];

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
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workspace) {
            if (empty($workspace->code)) {
                $workspace->code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate unique workspace code
     */
    public static function generateUniqueCode(): string
    {
        do {
            $latest = static::withTrashed()->latest('id')->first();
            $nextId = $latest ? $latest->id + 1 : 1;
            $code = 'WS-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
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
     * Get all members of the workspace
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot(['role', 'permissions', 'invited_at', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Get all projects in this workspace
     */
    public function projets(): HasMany
    {
        return $this->hasMany(Projet::class);
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
            return \Storage::url($this->logo);
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

    public function hasAccess(User $user): bool
    {
        return $this->isOwner($user) || $this->isMember($user);
    }

    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->role;
    }

    public function canUserManageMembers(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        $role = $this->getMemberRole($user);
        return in_array($role, ['owner', 'admin']);
    }

    public function canUserManageProjets(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        $member = $this->members()->where('user_id', $user->id)->first();
        if (!$member) {
            return false;
        }

        $permissions = $member->pivot->permissions ?? [];
        return in_array('manage_projects', $permissions) || 
               in_array('all', $permissions) ||
               in_array($member->pivot->role, ['owner', 'admin']);
    }

    public function addMember(User $user, string $role = 'member', array $permissions = []): void
    {
        if (!$this->isMember($user)) {
            $this->members()->attach($user->id, [
                'role' => $role,
                'permissions' => $permissions,
                'invited_at' => now(),
                'invited_by' => auth()->id(),
            ]);
        }
    }

    public function removeMember(User $user): void
    {
        if (!$this->isOwner($user)) {
            $this->members()->detach($user->id);
        }
    }

    public function updateMemberRole(User $user, string $role, array $permissions = []): void
    {
        if ($this->isMember($user) && !$this->isOwner($user)) {
            $this->members()->updateExistingPivot($user->id, [
                'role' => $role,
                'permissions' => $permissions,
            ]);
        }
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
            'total_taches' => $taches->count(),
            'taches_terminees' => $taches->where('statut', 'termine')->count(),
            'taches_en_cours' => $taches->where('statut', 'en_cours')->count(),
            'taches_en_retard' => $taches->filter(fn($t) => $t->isOverdue())->count(),
            'taux_completion' => $taches->count() > 0 
                ? round(($taches->where('statut', 'termine')->count() / $taches->count()) * 100, 2)
                : 0,
            'progression_moyenne' => $projets->count() > 0
                ? round($projets->avg('progression'), 2)
                : 0,
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
     * Activate the workspace
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }
}