<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\ContactController;
use App\Controllers\HealthController;
use App\Controllers\VitrineController;
use App\Middleware\ThrottleRequests;
use App\Middleware\VerifyCsrfToken;

// Supervision : à brancher sur votre outil de monitoring.
$router->get('/up', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);

// Pages du site (contenu dans config/site.php, vues dans resources/views/pages/).
$router->get('/', [VitrineController::class, 'home']);
$router->get('/a-propos', [VitrineController::class, 'about']);
$router->get('/services', [VitrineController::class, 'services']);
$router->get('/realisations', [VitrineController::class, 'works']);
$router->get('/faq', [VitrineController::class, 'faq']);
$router->get('/mentions-legales', [VitrineController::class, 'legal']);
$router->get('/politique-de-confidentialite', [VitrineController::class, 'privacy']);

// Formulaire de contact : validé par App\Requests\ContactRequest, protégé contre le CSRF et le spam.
$router->get('/contact', [ContactController::class, 'index'])->name('contact');
$router->post('/contact', [ContactController::class, 'store'], [VerifyCsrfToken::class, ThrottleRequests::class]);
