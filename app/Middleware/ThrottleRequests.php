<?php

namespace App\Middleware;

use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;
use Niang\Core\RateLimiter;

/**
 * Limite par défaut : 10 requêtes/minute, par IP et par route.
 * Besoin d'une autre limite ailleurs ? Dupliquez cette classe avec vos valeurs plutôt que
 * de la rendre configurable — plus simple à lire qu'un système de paramètres génériques.
 */
class ThrottleRequests implements Middleware
{
    private int $maxAttempts = 10;
    private int $decaySeconds = 60;

    public function handle(Request $request, \Closure $next): Response
    {
        $key = ($request->server['REMOTE_ADDR'] ?? 'cli') . '|' . $request->uri;

        if (!RateLimiter::attempt($key, $this->maxAttempts, $this->decaySeconds)) {
            return Response::json(['message' => 'Trop de requêtes, réessayez plus tard.'], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($key));
        }

        return $next($request);
    }
}
