<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

class ThrottleRequestsUnlessTest extends ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = ''): mixed
    {
        if (app()->environment('testing') || env('DISABLE_RATE_LIMITING')) {
            return $next($request);
        }

        // parent::handle() uses func_num_args() === 3 to detect named limiters, but since
        // we always forward 5 args that check always fails — handle named limiters ourselves.
        if (is_string($maxAttempts) && ! is_null($limiter = $this->limiter->limiter($maxAttempts))) {
            return $this->handleRequestUsingNamedLimiter($request, $next, $maxAttempts, $limiter);
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
}
