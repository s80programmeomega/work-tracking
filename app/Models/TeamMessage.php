<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class TeamMessage extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    public function toSearchableArray(): array
    {
        $this->loadMissing(['team.workspace', 'user', 'replyTo']);

        return [
            'id' => (string) $this->id,
            'uuid' => $this->uuid,
            // Contenu tronqué à 1 000 chars pour éviter de surcharger l'index
            'content' => mb_substr($this->content ?? '', 0, 1_000),
            'user_nom' => $this->user?->nom ?? '',
            'team_name' => $this->team?->name ?? '',
            'team_uuid' => $this->team?->uuid ?? '',
            'workspace_id' => (int) ($this->team?->workspace_id ?? 0),
            'workspace_name' => $this->team?->workspace?->nom ?? '',
            // Extrait du message parent si ce message est une réponse (120 chars)
            'reply_to_snippet' => $this->replyTo
                ? mb_substr($this->replyTo->content ?? '', 0, 120)
                : '',
            'created_at' => $this->created_at?->timestamp ?? 0,
        ];
    }

    public function searchableAs(): string
    {
        return 'team_messages';
    }

    /**
     * N'indexer que les messages appartenant à un workspace connu.
     * Les messages d'équipes sans workspace sont visibles uniquement par le super-admin
     * et ne sont donc pas inclus dans l'index standard.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->team?->workspace_id !== null;
    }

    protected $fillable = [
        'uuid',
        'team_id',
        'user_id',
        'content',
        'mentions',
        'attachments',
        'reply_to_id',
        'is_pinned',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'mentions' => 'array',
        'attachments' => 'array',
        'is_pinned' => 'boolean',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($message) {
            if (empty($message->uuid)) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who sent the message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent message if this is a reply
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(TeamMessage::class, 'reply_to_id');
    }

    /**
     * Get replies to this message
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TeamMessage::class, 'reply_to_id');
    }

    /**
     * Get message reactions
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(TeamMessageReaction::class, 'message_id');
    }

    /**
     * Scope for pinned messages
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope for recent messages
     */
    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Mark as edited
     */
    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Toggle pin status
     */
    public function togglePin(): void
    {
        $this->update(['is_pinned' => ! $this->is_pinned]);
    }

    /**
     * Get mentioned users
     */
    public function getMentionedUsers()
    {
        if (empty($this->mentions)) {
            return collect([]);
        }

        return User::whereIn('id', $this->mentions)->get();
    }
}
