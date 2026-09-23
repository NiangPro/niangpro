<?php

namespace App\Middleware;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Exceptions\AuthenticationException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

/** Protège /admin : un visiteur est envoyé vers /login, un compte ordinaire reçoit une 403. */
class EnsureUserIsAdmin implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (Auth::guest()) {
            if ($request->wantsJson()) {
                throw new AuthenticationException('Non authentifié.');
            }

            return Response::redirect('/login');
        }

        if (!User::isAdmin(Auth::user())) {
            abort(403, "Cet espace est réservé à l'administration du site.");
        }

        return $next($request);
    }
}
