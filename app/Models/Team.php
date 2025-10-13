<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'owner_id',
        'project_id',
        'visibility',
        'avatar',
        'settings',
        'is_active',
        'archived_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($team) {
            if (empty($team->uuid)) {
                $team->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the team owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the associated project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Projet::class, 'project_id');
    }

    /**
     * Get all team members with their roles
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot(['role', 'permissions', 'joined_at', 'last_active_at', 'notifications_enabled'])
            ->withTimestamps();
    }

    /**
     * Get team member records
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get team messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TeamMessage::class);
    }

    /**
     * Get team announcements
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(TeamAnnouncement::class);
    }

    /**
     * Get team resources
     */
    public function resources(): HasMany
    {
        return $this->hasMany(TeamResource::class);
    }

    /**
     * Get team activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TeamActivity::class);
    }

    /**
     * Get team presence records
     */
    public function presences(): HasMany
    {
        return $this->hasMany(TeamPresence::class);
    }

    /**
     * Get online members
     */
    public function onlineMembers()
    {
        return $this->members()
            ->whereHas('teamPresence', function ($query) {
                $query->where('team_id', $this->id)
                    ->where('status', 'online');
            });
    }

    /**
     * Scope for active teams
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('archived_at');
    }

    /**
     * Scope for public teams
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Check if user is a member
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user is owner
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(User $user, string $role): bool
    {
        return $this->members()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', $role)
            ->exists();
    }
}
