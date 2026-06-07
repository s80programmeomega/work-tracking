<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use HasFactory, SoftDeletes;

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
}
