<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\Api\ApiController;
use App\Controllers\Api\V1\AuthController;
use App\Controllers\Api\V1\NoteController;
use App\Controllers\HealthController;
use App\Middleware\AuthenticateWithToken;
use App\Middleware\HandleCors;
use App\Middleware\ThrottleRequests;

// Supervision : à brancher sur votre outil de monitoring.
$router->get('/up', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);
$router->get('/health/ready', [HealthController::class, 'index']);
$router->get('/health/live', [HealthController::class, 'live']);

$router->get('/', [ApiController::class, 'index']);

// Version 1 de l'API. CORS : origines autorisées dans config/cors.php ; chaque chemin a sa route
// OPTIONS pour le préflight des navigateurs. Description OpenAPI : ./bin/niang openapi.
$router->group(['prefix' => '/api/v1', 'middleware' => [HandleCors::class]], function ($router) {
    $id = ['id' => '[0-9]+'];

    $router->post('/register', [AuthController::class, 'register'], [ThrottleRequests::class]);
    $router->post('/tokens', [AuthController::class, 'login'], [ThrottleRequests::class]);

    $router->group(['middleware' => [AuthenticateWithToken::class]], function ($router) use ($id) {
        $router->delete('/tokens/current', [AuthController::class, 'logout']);
        $router->get('/me', [AuthController::class, 'me']);

        $router->get('/notes', [NoteController::class, 'index']);
        $router->post('/notes', [NoteController::class, 'store']);
        $router->get('/notes/{id}', [NoteController::class, 'show'])->where($id);
        $router->put('/notes/{id}', [NoteController::class, 'update'])->where($id);
        $router->delete('/notes/{id}', [NoteController::class, 'destroy'])->where($id);
    });

    foreach (['/register', '/tokens', '/tokens/current', '/me', '/notes', '/notes/{id}'] as $path) {
        $router->options($path, [ApiController::class, 'preflight']);
    }
});

$router->fallback([ApiController::class, 'notFound']);
