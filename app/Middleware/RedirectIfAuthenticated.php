<?php

namespace App\Middleware;

use Niang\Core\Auth;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

class RedirectIfAuthenticated implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (Auth::check()) {
            return Response::redirect('/');
        }

        return $next($request);
    }
}
