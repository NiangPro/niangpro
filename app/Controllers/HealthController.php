<?php

namespace App\Controllers;

use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Response;

/** GET /up — à brancher sur votre supervision (Uptime Kuma, un check de load balancer, etc.). */
class HealthController extends Controller
{
    public function index(): Response
    {
        try {
            DB::connection()->query('SELECT 1');
            $database = true;
        } catch (\Throwable) {
            $database = false;
        }

        return $this->json(
            ['status' => $database ? 'ok' : 'degraded', 'database' => $database],
            $database ? 200 : 503
        );
    }
}
