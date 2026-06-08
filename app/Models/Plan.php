<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $nom_fr
 * @property string $nom_en
 * @property string|null $description_fr
 * @property string|null $description_en
 * @property bool $is_free
 * @property int $price
 * @property string $currency
 * @property string $billing_period
 * @property int $max_members
 * @property int $max_storage_mb
 * @property int $max_file_size_mb
 * @property array<int, string>|null $features
 * @property bool $is_active
 * @property int $position
 */
class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'nom_fr',
        'nom_en',
        'description_fr',
        'description_en',
        'is_free',
        'price',
        'currency',
        'billing_period',
        'max_members',
        'max_storage_mb',
        'max_file_size_mb',
        'features',
        'is_active',
        'position',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'integer',
        'max_members' => 'integer',
        'max_storage_mb' => 'integer',
        'max_file_size_mb' => 'integer',
        'position' => 'integer',
        'features' => 'array',
    ];

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'plan_id');
    }

    /** Plans actifs (souscriptibles), triés par position. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position');
    }

    /** Le plan de repli gratuit (utilisé après expiration d'un essai/abonnement). */
    public static function free(): ?self
    {
        return static::query()->where('is_free', true)->first();
    }

    /** Une limite à -1 signifie « illimité ». */
    public function isUnlimited(string $limit): bool
    {
        return (int) ($this->{$limit} ?? -1) === -1;
    }
}
