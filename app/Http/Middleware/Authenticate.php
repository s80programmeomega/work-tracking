<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
       if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        return route('api/signin');
    }
    /**
     * Handle an unauthenticated user.
     */
    protected function unauthenticated($request, array $guards)
    {
        // Si c'est une requête API, retourner du JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'Non authentifié'
            ], 401));
        }

        // Sinon, comportement par défaut
        parent::unauthenticated($request, $guards);
    }
}
