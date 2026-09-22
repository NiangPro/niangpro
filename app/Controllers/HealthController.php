<?php

namespace App\Controllers;

use Niang\Core\Controller;
use Niang\Core\HealthCheck;
use Niang\Core\Http\Response;

/** GET /up et /health — à brancher sur votre supervision (Uptime Kuma, un check de load balancer, etc.). */
class HealthController extends Controller
{
    public function index(): Response
    {
        $result = HealthCheck::run();

        return $this->json($result, $result['status'] === 'ok' ? 200 : 503);
    }
}
