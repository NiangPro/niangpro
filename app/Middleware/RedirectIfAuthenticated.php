<?php

namespace App\Middleware;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

class RedirectIfAuthenticated implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (Auth::check()) {
            return Response::redirect(User::homePath(Auth::user()));
        }

        return $next($request);
    }
}
