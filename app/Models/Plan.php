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

    /** Invalide le cache du plan gratuit dès qu'un plan change. */
    protected static function booted(): void
    {
        static::saved(fn () => static::forgetFreeCache());
        static::deleted(fn () => static::forgetFreeCache());
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'plan_id');
    }

    /** Plans actifs (souscriptibles), triés par position. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position');
    }

    /**
     * Plan de repli gratuit, mémorisé pour la durée de la requête.
     *
     * Perf (Phase 10) : free() est appelé plusieurs fois par summary() et une fois
     * par ligne dans la liste admin des workspaces. Sans mémorisation cela générait
     * des dizaines de requêtes identiques par page. On met en cache le résultat
     * (y compris « absent ») le temps de la requête.
     */
    protected static ?self $freeCache = null;

    protected static bool $freeResolved = false;

    public static function free(): ?self
    {
        if (! static::$freeResolved) {
            static::$freeCache = static::query()->where('is_free', true)->first();
            static::$freeResolved = true;
        }

        return static::$freeCache;
    }

    /** Réinitialise le cache du plan gratuit (tests, ou après modification d'un plan). */
    public static function forgetFreeCache(): void
    {
        static::$freeCache = null;
        static::$freeResolved = false;
    }

    /** Une limite à -1 signifie « illimité ». */
    public function isUnlimited(string $limit): bool
    {
        return (int) ($this->{$limit} ?? -1) === -1;
    }
}
