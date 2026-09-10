<?php

namespace App\Middleware;

use Niang\Core\Csrf;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

class VerifyCsrfToken implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_token') ?? $request->header('X-CSRF-Token');

            if (!Csrf::verify($token)) {
                return Response::html('<h1>419</h1><p>Jeton CSRF invalide ou expiré. Rechargez la page et réessayez.</p>', 419);
            }
        }

        return $next($request);
    }
}
