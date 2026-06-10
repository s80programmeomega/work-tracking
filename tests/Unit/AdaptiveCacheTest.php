<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AdaptiveCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * TTL adaptatif : le multiplicateur croît avec le débit de requêtes (charge),
 * borné à [1.0, 3.0]. remember() met bien en cache.
 */
class AdaptiveCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_multiplier_is_one_when_idle(): void
    {
        $cache = new AdaptiveCache;
        $this->assertSame(1.0, $cache->loadMultiplier());
        $this->assertSame(60, $cache->ttlFor(60)); // 60 × 1.0
    }

    public function test_multiplier_grows_with_request_rate(): void
    {
        $cache = new AdaptiveCache;

        // Simule un débit modéré (300 req/min ≈ moitié du seuil de 600).
        Cache::put('adaptive_cache:req_rate', 300, 60);
        $mult = $cache->loadMultiplier();

        $this->assertGreaterThan(1.0, $mult);
        $this->assertLessThanOrEqual(3.0, $mult);
        // 60s de base → TTL allongé proportionnellement.
        $this->assertGreaterThan(60, $cache->ttlFor(60));
    }

    public function test_multiplier_is_capped_when_busy(): void
    {
        $cache = new AdaptiveCache;

        Cache::put('adaptive_cache:req_rate', 100000, 60); // très chargé
        $this->assertSame(3.0, $cache->loadMultiplier());   // plafonné
        $this->assertSame(180, $cache->ttlFor(60));         // 60 × 3.0
    }

    public function test_remember_caches_the_callback_result(): void
    {
        $cache = new AdaptiveCache;
        $calls = 0;

        $first = $cache->remember('k', 60, function () use (&$calls) {
            $calls++;

            return 'valeur';
        });
        $second = $cache->remember('k', 60, function () use (&$calls) {
            $calls++;

            return 'autre';
        });

        $this->assertSame('valeur', $first);
        $this->assertSame('valeur', $second);   // servi depuis le cache
        $this->assertSame(1, $calls);           // callback exécuté une seule fois
    }

    public function test_record_hit_increments_the_rate(): void
    {
        $cache = new AdaptiveCache;

        $cache->recordHit();
        $cache->recordHit();
        $cache->recordHit();

        $this->assertSame(3, (int) Cache::get('adaptive_cache:req_rate'));
    }
}
