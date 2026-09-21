<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\ContactController;
use App\Controllers\HealthController;
use App\Controllers\PortfolioController;
use App\Middleware\ThrottleRequests;
use App\Middleware\VerifyCsrfToken;

// Supervision : à brancher sur votre outil de monitoring.
$router->get('/up', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);

// Pages du portfolio (contenu dans config/site.php, vues dans resources/views/).
$router->get('/', [PortfolioController::class, 'home']);
$router->get('/projets', [PortfolioController::class, 'projects']);
$router->get('/projets/{slug}', [PortfolioController::class, 'project'])->where(['slug' => '[a-z0-9-]+']);
$router->get('/a-propos', [PortfolioController::class, 'about']);

// Contact : validé par App\Requests\ContactRequest, protégé contre le CSRF et le spam.
$router->get('/contact', [ContactController::class, 'index'])->name('contact');
$router->post('/contact', [ContactController::class, 'store'], [VerifyCsrfToken::class, ThrottleRequests::class]);
