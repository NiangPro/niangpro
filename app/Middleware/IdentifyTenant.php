<?php

namespace App\Middleware;

use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;
use Niang\Core\Tenancy;

/**
 * Identifie le locataire de la requête (config/tenancy.php) et traite la requête pour lui :
 * modèles par locataire filtrés, cache séparé, logs et jobs marqués. 404 pour un locataire inconnu.
 */
class IdentifyTenant implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $tenant = Tenancy::resolve($request) ?? throw new NotFoundException();

        return Tenancy::run($tenant, fn () => $next($request));
    }
}
