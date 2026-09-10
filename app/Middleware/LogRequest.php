<?php

namespace App\Middleware;

use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Log;
use Niang\Core\Middleware;

class LogRequest implements Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        Log::info('{method} {uri}', ['method' => $request->method, 'uri' => $request->uri]);

        return $next($request);
    }
}
