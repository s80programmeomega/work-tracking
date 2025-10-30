<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'email',
        'role',
        'token',
        'invited_by',
        'message',
        'permissions',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<=', now());
    }

    /**
     * Check if invitation is valid
     */
    public function isValid(): bool
    {
        return $this->status === 'pending' && 
               $this->expires_at > now();
    }

    /**
     * Accept invitation
     */
    public function accept(User $user): void
    {
        if (!$this->isValid()) {
            throw new \Exception('Cette invitation n\'est plus valide');
        }

        // Add user to workspace
        $this->workspace->members()->attach($user->id, [
            'role' => $this->role,
            'can_create_projects' => $this->permissions['can_create_projects'] ?? false,
            'can_invite_members' => $this->permissions['can_invite_members'] ?? false,
            'can_manage_settings' => $this->permissions['can_manage_settings'] ?? false,
            'invited_at' => now(),
        ]);

        // Update invitation status
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }
}