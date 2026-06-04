<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $support_ticket_id
 * @property int $uploaded_by
 * @property string $file_path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SupportTicketAttachment extends Model
{
    protected $fillable = ['support_ticket_id', 'uploaded_by', 'file_path', 'original_name', 'mime_type', 'size_bytes'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('support.attachments.download', $this->id);
    }
}
