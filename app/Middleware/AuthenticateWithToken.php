<?php

namespace App\Middleware;

use Niang\Core\ApiToken;
use Niang\Core\Auth;
use Niang\Core\Exceptions\AuthenticationException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

/**
 * Authentifie une route par jeton API (Authorization: Bearer <jeton>), sans toucher à la
 * session — à appliquer aux routes qui en ont besoin, pas au groupe /api entier (une route
 * publique comme GET /api/posts n'en a pas besoin, POST /api/tokens ne peut pas en avoir).
 */
class AuthenticateWithToken implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            throw new AuthenticationException('Jeton API manquant.');
        }

        $user = ApiToken::resolve(substr($header, 7));

        if ($user === null) {
            throw new AuthenticationException(__('http.invalid_token'));
        }

        Auth::resolveViaToken($user);

        try {
            return $next($request);
        } finally {
            // Scope strictement limité à cette requête : jamais hérité par la suivante, y
            // compris dans un process long (CLI, tests) qui gérerait plusieurs requêtes.
            Auth::resolveViaToken(null);
        }
    }
}
