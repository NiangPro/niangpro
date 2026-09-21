<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\BlogController;
use App\Controllers\ContactController;
use App\Controllers\HealthController;
use App\Controllers\PostController;
use App\Controllers\TagController;
use App\Middleware\ThrottleRequests;
use App\Middleware\VerifyCsrfToken;

// Supervision : à brancher sur votre outil de monitoring.
$router->get('/up', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);

$router->get('/', [BlogController::class, 'home']);

// Liste paginée : PostController::page (nombre par page : config/site.php, 'posts_per_page').
$router->get('/blog', [PostController::class, 'page']);
$router->get('/blog/{slug}', [BlogController::class, 'show'])->where(['slug' => '[a-z0-9-]+']);

// Catégories et tags.
$router->get('/tags', [BlogController::class, 'tags'])->name('tags');
$router->get('/tags/{name}', [BlogController::class, 'tag'])->where(['name' => '[a-z0-9-]+']);
$router->get('/categories/{slug}', [BlogController::class, 'category'])->where(['slug' => '[a-z0-9-]+']);

// TagController, en lecture seule : ses routes d'écriture (POST/PUT/DELETE) n'ont ni authentification
// ni protection CSRF dans le squelette de démonstration — ne les exposez pas telles quelles.
$router->get('/api/tags', [TagController::class, 'index']);

$router->get('/a-propos', [BlogController::class, 'about']);

// Contact : validé par App\Requests\ContactRequest, protégé contre le CSRF et le spam.
$router->get('/contact', [ContactController::class, 'index'])->name('contact');
$router->post('/contact', [ContactController::class, 'store'], [VerifyCsrfToken::class, ThrottleRequests::class]);
