<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache applicatif à TTL adaptatif piloté par la charge (débit de requêtes).
 *
 * Principe : plus le serveur traite de requêtes par minute, plus le TTL est long
 * (on réutilise davantage le cache au moment où c'est utile pour soulager la
 * base) ; en période calme, le TTL reste court (données plus fraîches, le coût
 * d'un recalcul est négligeable).
 *
 * La « charge » est approximée par un compteur Redis req/min (INCR bon marché),
 * et non par la charge système (coûteuse à lire et en retard de plusieurs
 * minutes). Voir recordHit().
 */
class AdaptiveCache
{
    /** Clé du compteur de débit (fenêtre d'une minute). */
    private const RATE_KEY = 'adaptive_cache:req_rate';

    /** Multiplicateur de TTL maximal (cap) quand le serveur est très chargé. */
    private const MAX_MULTIPLIER = 3.0;

    /** Seuil de requêtes/min à partir duquel le multiplicateur atteint son max. */
    private const BUSY_THRESHOLD = 600; // ~10 req/s

    /**
     * Mémorise le résultat de $callback sous $key, avec un TTL = base × charge.
     *
     * @template T
     *
     * @param  Closure():T  $callback
     * @return T
     */
    public function remember(string $key, int $baseSeconds, Closure $callback): mixed
    {
        $ttl = $this->ttlFor($baseSeconds);

        return Cache::remember($key, $ttl, $callback);
    }

    /** Invalide une entrée (utile pour les tests ou une purge ciblée). */
    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * TTL effectif (secondes) pour un TTL de base donné, selon la charge courante.
     */
    public function ttlFor(int $baseSeconds): int
    {
        return (int) max(1, round($baseSeconds * $this->loadMultiplier()));
    }

    /**
     * Multiplicateur de charge dans [1.0, MAX_MULTIPLIER] selon le débit req/min.
     * 0 req/min → 1.0 ; ≥ BUSY_THRESHOLD → MAX_MULTIPLIER ; linéaire entre les deux.
     */
    public function loadMultiplier(): float
    {
        $rate = (int) (Cache::get(self::RATE_KEY) ?? 0);
        if ($rate <= 0) {
            return 1.0;
        }

        $ratio = min(1.0, $rate / self::BUSY_THRESHOLD);

        return 1.0 + $ratio * (self::MAX_MULTIPLIER - 1.0);
    }

    /**
     * Incrémente le compteur de débit (à appeler une fois par requête HTTP, via
     * un middleware léger). Fenêtre glissante d'une minute via TTL.
     */
    public function recordHit(): void
    {
        // add() pose la valeur+TTL seulement si absente ; sinon on incrémente.
        if (! Cache::add(self::RATE_KEY, 1, 60)) {
            Cache::increment(self::RATE_KEY);
        }
    }
}
