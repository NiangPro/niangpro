<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\PostController;
use App\Controllers\TagController;
use App\Middleware\Authenticate;
use App\Middleware\LogRequest;
use App\Middleware\RedirectIfAuthenticated;
use App\Middleware\ThrottleRequests;
use App\Middleware\VerifyCsrfToken;
use Niang\Core\Gate;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

// Règles d'autorisation (v0.4.0). Pour une app plus grosse, sortez ceci dans son propre fichier.
Gate::define('delete-post', function (?array $user, array $post): bool {
    // Ici : simple "il faut être connecté". Dans une vraie appli, comparez $user['id'] à un auteur.
    return $user !== null;
});

$router->get('/', [HomeController::class, 'index']);

$router->get('/up', [HealthController::class, 'index']); // v0.9.0 : supervision

$router->get('/hello/{name}', [HomeController::class, 'hello']);

$router->post('/echo', [HomeController::class, 'echoBody']);

// Démo v0.2.0 : session, CSRF, validation, messages flash
$router->get('/contact', [ContactController::class, 'index'])->name('contact');
$router->post('/contact', [ContactController::class, 'store'], [VerifyCsrfToken::class]);

// Démo v0.3.0 : migrations, Query Builder, relations, transactions
$router->get('/posts', [PostController::class, 'index']);
$router->get('/blog', [PostController::class, 'page']);
$router->post('/posts', [PostController::class, 'store'], [Authenticate::class]);
$router->delete('/posts/{id}', [PostController::class, 'destroy'], [Authenticate::class])
    ->where(['id' => '[0-9]+']); // v0.6.0 : contrainte regex — /posts/abc ne matche plus cette route

// Démo v0.4.0 : auth, middlewares auth/guest, rate limiting
$router->get('/register', [AuthController::class, 'showRegister'], [RedirectIfAuthenticated::class])->name('register');
$router->post('/register', [AuthController::class, 'register'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/login', [AuthController::class, 'showLogin'], [RedirectIfAuthenticated::class])->name('login');
$router->post('/login', [AuthController::class, 'login'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->post('/logout', [AuthController::class, 'logout'], [VerifyCsrfToken::class]);

// Démo v0.6.0 : routes ressources REST (7 routes générées : tags.index, tags.show, ...)
$router->resource('tags', TagController::class);

// Démo v0.6.0 : sous-domaines — curl -H "Host: acme.niangpro.test" .../tenant
$router->domain('{tenant}.niangpro.test', function ($router) {
    $router->get('/tenant', function (Request $request): Response {
        return Response::json(['tenant' => $request->param('tenant')]);
    });
});

// Exemple de route avec closure + middleware
$router->get('/ping', function (Request $request): Response {
    return Response::json(['pong' => true, 'time' => time()]);
}, [LogRequest::class]);

// Exemple de groupe de routes
$router->group(['prefix' => '/api'], function ($router) {
    $router->get('/status', function (): Response {
        return Response::json(['status' => 'ok', 'framework' => 'NiangPro']);
    });
});
