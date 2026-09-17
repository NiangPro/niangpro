<?php

namespace App\Middleware;

use Niang\Core\Cors;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

class HandleCors implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        // Préflight : le navigateur attend une réponse vide avec juste les en-têtes CORS, sans
        // exécuter la route (et donc sans middleware d'auth qui la rejetterait).
        $response = Cors::isPreflight($request) ? Response::html('', 204) : $next($request);

        foreach (Cors::headersFor($request) as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
