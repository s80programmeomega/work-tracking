<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TeamResource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'team_id',
        'user_id',
        'type',
        'name',
        'description',
        'content',
        'file_path',
        'url',
        'tags',
        'usage_count',
    ];

    protected $casts = [
        'content' => 'array',
        'tags' => 'array',
        'usage_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($resource) {
            if (empty($resource->uuid)) {
                $resource->uuid = (string) Str::uuid();
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
     * Get the user who created the resource
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for templates
     */
    public function scopeTemplates($query)
    {
        return $query->where('type', 'template');
    }

    /**
     * Scope for documents
     */
    public function scopeDocuments($query)
    {
        return $query->where('type', 'document');
    }

    /**
     * Scope for checklists
     */
    public function scopeChecklists($query)
    {
        return $query->where('type', 'checklist');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Search by tags
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }
}
