<?php

namespace App\Controllers;

use Niang\Core\Controller;
use Niang\Core\HealthCheck;
use Niang\Core\Http\Response;

/**
 * GET /up, /health et /health/ready : l'application et ses dépendances (base, cache, stockage, file) —
 * 503 si l'une manque. GET /health/live : le process répond, sans rien vérifier d'autre.
 * À brancher sur votre supervision (Uptime Kuma, un répartiteur de charge, les sondes Kubernetes...).
 */
class HealthController extends Controller
{
    /**
     * Vivacité : aucune dépendance vérifiée, pour qu'une base momentanément indisponible ne fasse pas
     * redémarrer le conteneur (c'est le rôle de /health/ready de le retirer du trafic).
     */
    public function live(): Response
    {
        return $this->json(['status' => 'ok']);
    }

    public function index(): Response
    {
        $result = HealthCheck::run();

        return $this->json($result, $result['status'] === 'ok' ? 200 : 503);
    }
}
