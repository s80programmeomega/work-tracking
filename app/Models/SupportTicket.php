<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $workspace_id
 * @property string $category
 * @property string $subject
 * @property string $message
 * @property string $status
 * @property string|null $attachment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'category',
        'subject',
        'message',
        'status',
        'attachment',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
