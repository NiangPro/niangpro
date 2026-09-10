<?php

namespace Niang\Core;

use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

interface Middleware
{
    public function handle(Request $request, \Closure $next): Response;
}
