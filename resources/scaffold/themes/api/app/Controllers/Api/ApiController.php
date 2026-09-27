<?php

namespace App\Controllers\Api;

use Niang\Core\Controller;
use Niang\Core\Http\Response;

class ApiController extends Controller
{
    /** Point d'entrée : nom, version et liens utiles. */
    public function index(): Response
    {
        return $this->json([
            'name' => config('site.name'),
            'version' => config('site.version'),
            'documentation' => url('/openapi.json'),
            'health' => url('/health'),
        ]);
    }

    /** Préflight CORS : HandleCors répond avant d'arriver ici ; cette action garde la route cachable. */
    public function preflight(): Response
    {
        return Response::html('', 204);
    }

    /** Toute URL inconnue : une erreur JSON, jamais une page HTML. */
    public function notFound(): Response
    {
        return $this->json(['message' => __('http.404')], 404);
    }
}
