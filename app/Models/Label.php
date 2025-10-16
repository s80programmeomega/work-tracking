<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'projet_id',
        'nom',
        'couleur',
        'description',
        'ordre',
        'is_global',
        'usage_count',
        'created_by',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'is_global' => 'boolean',
        'usage_count' => 'integer',
    ];

    protected $appends = ['text_color'];

    /**
     * Get taches associated with this label
     */
    public function taches(): BelongsToMany
    {
        return $this->belongsToMany(Tache::class, 'label_tache')
            ->withTimestamps();
    }

    /**
     * Get the project this label belongs to (if project-specific)
     */
    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    /**
     * Get the user who created this label
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to order labels by ordre
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('ordre');
    }

    /**
     * Scope to get only global labels
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('is_global', true)->whereNull('projet_id');
    }

    /**
     * Scope to get labels for a specific project (includes global + project-specific)
     */
    public function scopeForProject(Builder $query, int $projetId): Builder
    {
        return $query->where(function ($q) use ($projetId) {
            $q->where('is_global', true)
              ->orWhere('projet_id', $projetId);
        });
    }

    /**
     * Scope to get only project-specific labels
     */
    public function scopeProjectSpecific(Builder $query, int $projetId): Builder
    {
        return $query->where('projet_id', $projetId)->where('is_global', false);
    }

    /**
     * Calculate text color based on background color for readability
     */
    public function getTextColorAttribute(): string
    {
        $hex = ltrim($this->couleur, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calculate luminance using relative luminance formula
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        // Return white for dark colors, black for light colors
        return $luminance > 0.5 ? '#000000' : '#FFFFFF';
    }

    /**
     * Increment usage count when label is assigned to a task
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Decrement usage count when label is removed from a task
     */
    public function decrementUsage(): void
    {
        if ($this->usage_count > 0) {
            $this->decrement('usage_count');
        }
    }
}
