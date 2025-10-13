<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'permissions',
        'joined_at',
        'last_active_at',
        'notifications_enabled',
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
        'last_active_at' => 'datetime',
        'notifications_enabled' => 'boolean',
    ];

    /**
     * Get the team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if member has permission
     */
    public function hasPermission(string $permission): bool
    {
        if (in_array($this->role, ['owner', 'admin'])) {
            return true;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Check if member is admin or owner
     */
    public function isAdminOrOwner(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    /**
     * Update last activity
     */
    public function updateActivity(): void
    {
        $this->update(['last_active_at' => now()]);
    }
}
