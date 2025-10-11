<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CommentAttachment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'comment_id',
        'nom',
        'type',
        'taille',
        'chemin',
    ];

    protected $casts = [
        'taille' => 'integer',
        'created_at' => 'datetime',
    ];

    protected $appends = ['url', 'human_size'];

    /**
     * The comment this attachment belongs to
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Get the full URL to the attachment
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->chemin);
    }

    /**
     * Get human readable file size
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->taille;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if attachment is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->type, 'image/');
    }

    /**
     * Check if attachment is a document
     */
    public function isDocument(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ];

        return in_array($this->type, $documentTypes);
    }

    /**
     * Delete attachment file from storage
     */
    public function deleteFile(): bool
    {
        if (Storage::exists($this->chemin)) {
            return Storage::delete($this->chemin);
        }

        return false;
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Delete file from storage when attachment is deleted
        static::deleting(function ($attachment) {
            $attachment->deleteFile();
        });
    }
}
