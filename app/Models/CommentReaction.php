<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $count SQL alias from selectRaw aggregate queries
 */
class CommentReaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'comment_id',
        'user_id',
        'emoji',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * The comment this reaction belongs to
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * The user who reacted
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get reactions by emoji
     */
    public function scopeByEmoji($query, string $emoji)
    {
        return $query->where('emoji', $emoji);
    }

    /**
     * Scope to get reactions by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get reactions by comment
     */
    public function scopeByComment($query, int $commentId)
    {
        return $query->where('comment_id', $commentId);
    }

    /**
     * Toggle reaction (add if doesn't exist, remove if exists)
     */
    public static function toggle(int $commentId, int $userId, string $emoji): bool
    {
        $reaction = self::where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->first();

        if ($reaction) {
            $reaction->delete();

            return false; // Removed
        }

        self::create([
            'comment_id' => $commentId,
            'user_id' => $userId,
            'emoji' => $emoji,
        ]);

        return true; // Added
    }
}
