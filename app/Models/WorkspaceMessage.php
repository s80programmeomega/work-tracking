<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class WorkspaceMessage extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    /** @var array<int,string> */
    protected $fillable = [
        'uuid',
        'workspace_id',
        'workspace_channel_id',
        'user_id',
        'content',
        'mentions',
        'attachments',
        'reply_to_id',
        'is_pinned',
        'is_edited',
        'edited_at',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'mentions' => 'array',
        'attachments' => 'array',
        'is_pinned' => 'boolean',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $message): void {
            if (empty($message->uuid)) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['channel.workspace', 'user', 'replyTo']);

        return [
            'id' => (string) $this->id,
            'uuid' => $this->uuid,
            'content' => mb_substr($this->content ?? '', 0, 1_000),
            'user_nom' => $this->user?->nom ?? '',
            'channel_type' => $this->channel?->type ?? '',
            'workspace_id' => (int) ($this->workspace_id ?? 0),
            'workspace_name' => $this->channel?->workspace?->nom ?? '',
            'reply_to_snippet' => $this->replyTo
                ? mb_substr($this->replyTo->content ?? '', 0, 120)
                : '',
            'created_at' => $this->created_at?->timestamp ?? 0,
        ];
    }

    public function searchableAs(): string
    {
        return 'workspace_messages';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->workspace_id !== null;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(WorkspaceChannel::class, 'workspace_channel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(WorkspaceMessageReaction::class, 'workspace_message_id');
    }

    public function getMentionedUsers(): Collection
    {
        if (empty($this->mentions)) {
            return collect([]);
        }

        return User::whereIn('id', $this->mentions)->get();
    }

    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }
}
