<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'permissionable_type',
        'permissionable_id',
        'can_view',
        'can_download',
        'can_edit',
        'can_delete',
        'can_share',
        'expires_at',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_download' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_share' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The document this permission belongs to
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * The entity that has permission (User or Role)
     */
    public function permissionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if permission is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if permission is active
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Scope: Active permissions only
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Expired permissions
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope: For user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('permissionable_type', User::class)
            ->where('permissionable_id', $userId);
    }

    /**
     * Scope: For document
     */
    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }
}
