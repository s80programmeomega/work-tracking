<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $slug
 * @property string $nom_fr
 * @property string $nom_en
 * @property string|null $description_fr
 * @property string|null $description_en
 * @property string $icon
 * @property int $position
 * @property Carbon|null $published_at
 */
class HelpCategory extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'slug',
        'nom_fr',
        'nom_en',
        'description_fr',
        'description_en',
        'icon',
        'position',
        'published_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'published_at' => 'datetime',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(HelpArticle::class, 'category_id');
    }

    /** Catégories publiées uniquement (published_at <= maintenant). */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** Collection Scout dédiée. */
    public function searchableAs(): string
    {
        return 'help_categories';
    }

    /** N'indexe que les catégories publiées. */
    public function shouldBeSearchable(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    /**
     * Données indexées : noms + descriptions bilingues.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'nom_fr' => $this->nom_fr,
            'nom_en' => $this->nom_en,
            'description_fr' => $this->description_fr ?? '',
            'description_en' => $this->description_en ?? '',
            'created_at' => $this->created_at?->timestamp ?? 0,
        ];
    }
}
