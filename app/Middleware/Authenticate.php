<?php

namespace App\Middleware;

use Niang\Core\Auth;
use Niang\Core\Exceptions\AuthenticationException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

class Authenticate implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (Auth::guest()) {
            if ($request->wantsJson()) {
                throw new AuthenticationException(__('http.unauthenticated'));
            }

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
