<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentDownload extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'user_id',
        'ip_address',
        'user_agent',
        'download_method',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    /**
     * The document that was downloaded
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * The user who downloaded the document
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Recent downloads
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('downloaded_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: By document
     */
    public function scopeByDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    /**
     * Scope: By user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
