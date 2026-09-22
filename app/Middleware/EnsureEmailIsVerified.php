<?php

namespace App\Middleware;

use Niang\Core\Auth;
use Niang\Core\Exceptions\AuthenticationException;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

/**
 * Optionnelle : non branchée par défaut sur les routes existantes ni sur les thèmes de site
 * livrés. Une application décide elle-même où l'exiger (ex: avant un checkout), en l'ajoutant
 * au tableau de middlewares de la route concernée — après Authenticate::class, puisqu'elle
 * suppose un utilisateur déjà connecté. abort(403) plutôt qu'une redirection : l'application
 * qui l'utilise n'a pas forcément de page "vérifiez votre email" — à elle de gérer l'exception
 * si elle en veut une (voir Niang\Core\Exceptions\Handler).
 */
class EnsureEmailIsVerified implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null) {
            throw new AuthenticationException('Non authentifié.');
        }

        if ($user['email_verified_at'] === null) {
            throw new HttpException(403, 'Adresse email non vérifiée.');
        }

        return $next($request);
    }
}
