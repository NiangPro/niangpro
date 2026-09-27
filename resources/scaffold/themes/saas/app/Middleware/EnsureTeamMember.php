<?php

namespace App\Middleware;

use App\Support\Team;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Middleware;

/**
 * Après IdentifyTenant : l'utilisateur doit être membre de l'organisation (404 sinon, elle n'existe
 * pas pour lui), avec au moins le rôle demandé — EnsureTeamMember::class . ':admin' (403 sinon).
 */
class EnsureTeamMember implements Middleware
{
    public function handle(Request $request, \Closure $next, string $role = 'member'): Response
    {
        if (Team::role() === null) {
            throw new NotFoundException();
        }

        if (!Team::hasRole($role)) {
            throw new HttpException(403, 'Réservé aux ' . ($role === 'owner' ? 'propriétaires' : 'administrateurs') . ' de l\'organisation.');
        }

        return $next($request);
    }
}
