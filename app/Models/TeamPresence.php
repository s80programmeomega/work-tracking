<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamPresence extends Model
{
    use HasFactory;

    protected $table = 'team_presence';

    protected $fillable = [
        'team_id',
        'user_id',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
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
     * Scope for online users
     */
    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    /**
     * Scope for away users
     */
    public function scopeAway($query)
    {
        return $query->where('status', 'away');
    }

    /**
     * Scope for busy users
     */
    public function scopeBusy($query)
    {
        return $query->where('status', 'busy');
    }

    /**
     * Update user status
     */
    public function updateStatus(string $status): void
    {
        $this->update([
            'status' => $status,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Update last seen time
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Set user online
     */
    public function setOnline(): void
    {
        $this->updateStatus('online');
    }

    /**
     * Set user offline
     */
    public function setOffline(): void
    {
        $this->updateStatus('offline');
    }
}
