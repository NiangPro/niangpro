<?php

namespace App\Middleware;

use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;
use Niang\Core\UrlSignature;

/** Protège une route signée (voir signedRoute()) : reset de mot de passe, vérification d'email... */
class ValidateSignature implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $url = $request->query ? $request->uri . '?' . http_build_query($request->query) : $request->uri;

        if (!UrlSignature::validate($url)) {
            abort(403, 'Lien invalide, expiré ou modifié.');
        }

        return $next($request);
    }
}
