<?php

namespace App\Middleware;

use Niang\Core\Auth;
use Niang\Core\Exceptions\AuthenticationException;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Gate;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

/**
 * Exige une ability (règle Gate, méthode de Policy ou permission de rôle) :
 *   $router->delete('/posts/{id}', [...], [Authorize::class . ':posts.delete']);
 * Plusieurs abilities séparées par des virgules : toutes sont exigées.
 */
class Authorize implements Middleware
{
    public function handle(Request $request, \Closure $next, string ...$abilities): Response
    {
        if (Auth::guest()) {
            if ($request->wantsJson()) {
                throw new AuthenticationException(__('http.unauthenticated'));
            }

            return Response::redirect('/login');
        }

        foreach ($abilities as $ability) {
            if (Gate::denies($ability)) {
                throw new HttpException(403);
            }
        }

        return $next($request);
    }
}
