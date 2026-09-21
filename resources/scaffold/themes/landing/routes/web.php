<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\HealthController;
use App\Controllers\LandingController;

// Supervision : à brancher sur votre outil de monitoring.
$router->get('/up', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);

// Une page à sections ancrées (#fonctionnalites, #fonctionnement, #temoignages, #tarifs, #faq)
// et une page de mentions légales.
$router->get('/', [LandingController::class, 'home']);
$router->get('/mentions-legales', [LandingController::class, 'legal']);
