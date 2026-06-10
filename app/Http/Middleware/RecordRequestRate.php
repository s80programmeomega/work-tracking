<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AdaptiveCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Incrémente le compteur de débit (req/min) utilisé par AdaptiveCache pour
 * ajuster les TTL selon la charge. Opération Redis bon marché, après réponse.
 */
class RecordRequestRate
{
    public function __construct(private AdaptiveCache $adaptiveCache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Après coup : ne pas pénaliser la latence de la requête.
        try {
            $this->adaptiveCache->recordHit();
        } catch (\Throwable) {
            // Le comptage de charge ne doit jamais casser une requête.
        }

        return $response;
    }
}
