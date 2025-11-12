<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjetInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'projet_id',
        'email',
        'role',
        'can_edit',
        'can_delete',
        'can_invite',
        'token',
        'invited_by',
        'message',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_invite' => 'boolean',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
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

        $projet = $this->projet;

        // Vérifier si déjà membre
        if ($projet->members()->where('user_id', $user->id)->exists()) {
            throw new \Exception('Vous êtes déjà membre de ce projet');
        }

        // Ajouter au projet
        $projet->members()->attach($user->id, [
            'role' => $this->role,
            'can_edit' => $this->can_edit,
            'can_delete' => $this->can_delete,
            'can_invite' => $this->can_invite,
        ]);

        // ✅ Ajouter au workspace s'il n'est pas déjà membre
        $workspace = $projet->workspace;
        if ($workspace && !$workspace->members()->where('user_id', $user->id)->exists()) {
            $workspace->members()->attach($user->id, [
                'role' => 'viewer', // Rôle par défaut dans le workspace
                'permissions' => json_encode([]),
                'invited_at' => now(),
                'invited_by' => $this->invited_by,
            ]);
        }

        // Mettre à jour l'invitation
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Log
        activity()
            ->causedBy($user)
            ->performedOn($projet)
            ->withProperties([
                'role' => $this->role,
                'invited_by' => $this->invited_by
            ])
            ->log('User accepted projet invitation');
    }

    /**
     * Decline invitation
     */
    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }

    /**
     * Cancel invitation
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}