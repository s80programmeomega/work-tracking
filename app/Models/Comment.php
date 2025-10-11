<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Comment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'content',
        'content_html',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $with = ['user', 'reactions', 'mentions'];

    /**
     * Polymorphic relationship to commentable entity (Task, Project, Activity)
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Author of the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent comment for threaded replies
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Child comments (replies)
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Mentions in this comment
     */
    public function mentions(): HasMany
    {
        return $this->hasMany(CommentMention::class);
    }

    /**
     * Reactions on this comment
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    /**
     * Attachments on this comment
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(CommentAttachment::class);
    }

    /**
     * Get reactions grouped by emoji
     */
    public function getReactionsGroupedAttribute(): array
    {
        return $this->reactions()
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->get()
            ->mapWithKeys(fn($reaction) => [$reaction->emoji => $reaction->count])
            ->toArray();
    }

    /**
     * Check if comment has been edited
     */
    public function isEdited(): bool
    {
        return $this->is_edited;
    }

    /**
     * Check if comment is a reply
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Mark comment as edited
     */
    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Scope to get only root comments (not replies)
     */
    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get comments by commentable
     */
    public function scopeForCommentable($query, string $type, int $id)
    {
        return $query->where('commentable_type', $type)
                    ->where('commentable_id', $id);
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['content', 'is_edited', 'edited_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
