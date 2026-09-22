<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\Api\TokenController;
use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\PostController;
use App\Controllers\TagController;
use App\Middleware\Authenticate;
use App\Middleware\AuthenticateWithToken;
use App\Middleware\HandleCors;
use App\Middleware\LogRequest;
use App\Middleware\RedirectIfAuthenticated;
use App\Middleware\ThrottleRequests;
use App\Middleware\ValidateSignature;
use App\Middleware\VerifyCsrfToken;
use App\Policies\PostPolicy;
use Niang\Core\Auth;
use Niang\Core\Gate;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

// Règles d'autorisation. 'post.delete'/'post.update' résolvent vers PostPolicy::delete()/update()
// (voir app/Policies/PostPolicy.php) — pour une seule règle isolée, Gate::define() reste plus simple.
Gate::policy('post', PostPolicy::class);

$router->get('/', [HomeController::class, 'index']);

$router->get('/up', [HealthController::class, 'index']); // v0.9.0 : supervision
$router->get('/health', [HealthController::class, 'index']); // P0 #15 : alias documenté par la roadmap

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

// P0 #15, douzième jalon : récupération de mot de passe + vérification d'email (signedRoute()).
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], [RedirectIfAuthenticated::class])->name('password.request');
$router->post('/forgot-password', [AuthController::class, 'sendResetLink'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/reset-password/{token}/{email}', [AuthController::class, 'showResetPassword'], [ValidateSignature::class])->name('password.reset');
$router->post('/reset-password/{token}/{email}', [AuthController::class, 'resetPassword'], [ValidateSignature::class, VerifyCsrfToken::class]);
$router->get('/verify-email/{id}', [AuthController::class, 'verifyEmail'], [ValidateSignature::class])
    ->where(['id' => '[0-9]+'])
    ->name('verification.verify');

// Démo v0.6.0 : routes ressources REST (7 routes générées : tags.index, tags.show, ...)
$router->resource('tags', TagController::class);

// Démo P0 #12 : JsonResource + CORS. HandleCors répond directement au préflight OPTIONS et ajoute
// les en-têtes Access-Control-* (voir config/cors.php) sur la vraie réponse.
$router->group(['prefix' => '/api', 'middleware' => [HandleCors::class]], function ($router) {
    $router->get('/posts', [PostController::class, 'apiIndex']);

    // Sans route OPTIONS explicite, le préflight ne matcherait aucune route (405, avant même
    // d'atteindre HandleCors) : chaque route API doit avoir sa contrepartie OPTIONS.
    $router->options('/posts', fn () => Response::html('', 204));

    // P0 #15, treizième jalon : authentification par jeton. AuthenticateWithToken n'est posée
    // que sur les routes qui en ont besoin (/me), pas sur le groupe entier — /posts reste public,
    // /tokens (l'émission elle-même) ne peut pas exiger le jeton qu'elle délivre.
    $router->post('/tokens', [TokenController::class, 'store']);
    $router->options('/tokens', fn () => Response::html('', 204));

    $router->get('/me', function (Request $request): Response {
        return Response::json(['user' => Auth::user()]);
    }, [AuthenticateWithToken::class]);
    $router->options('/me', fn () => Response::html('', 204));
});

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

// Démo v0.6.0 (Router 2.0) : une seule action pour plusieurs méthodes
$router->match(['GET', 'POST'], '/status-either', function (Request $request): Response {
    return Response::json(['method' => $request->method]);
});

// Exemple de groupe de routes
$router->group(['prefix' => '/api'], function ($router) {
    $router->get('/status', function (): Response {
        return Response::json(['status' => 'ok', 'framework' => 'NiangPro']);
    });
});
