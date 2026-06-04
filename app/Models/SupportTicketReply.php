<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $support_ticket_id
 * @property int $user_id
 * @property string $body
 * @property bool $is_admin_reply
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SupportTicketReply extends Model
{
    protected $fillable = ['support_ticket_id', 'user_id', 'body', 'is_admin_reply'];

    protected $casts = ['is_admin_reply' => 'boolean'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
