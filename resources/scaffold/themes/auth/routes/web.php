<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\AccountController;
use App\Controllers\AppController;
use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\SocialAuthController;
use App\Controllers\TwoFactorController;
use App\Middleware\Authenticate;
use App\Middleware\RedirectIfAuthenticated;
use App\Middleware\ThrottleRequests;
use App\Middleware\ValidateSignature;
use App\Middleware\VerifyCsrfToken;

// Supervision : à brancher sur votre outil de monitoring.
$router->get('/up', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);
$router->get('/health/ready', [HealthController::class, 'index']);
$router->get('/health/live', [HealthController::class, 'live']);

$router->get('/', [AppController::class, 'home']);

// Inscription, connexion, déconnexion.
$router->get('/register', [AuthController::class, 'showRegister'], [RedirectIfAuthenticated::class])->name('register');
$router->post('/register', [AuthController::class, 'register'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/login', [AuthController::class, 'showLogin'], [RedirectIfAuthenticated::class])->name('login');
$router->post('/login', [AuthController::class, 'login'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->post('/logout', [AuthController::class, 'logout'], [VerifyCsrfToken::class]);

// Mot de passe oublié et vérification d'email : liens signés et à durée limitée.
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], [RedirectIfAuthenticated::class])->name('password.request');
$router->post('/forgot-password', [AuthController::class, 'sendResetLink'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/reset-password/{token}/{email}', [AuthController::class, 'showResetPassword'], [ValidateSignature::class])->name('password.reset');
$router->post('/reset-password/{token}/{email}', [AuthController::class, 'resetPassword'], [ValidateSignature::class, VerifyCsrfToken::class]);
$router->get('/verify-email/{id}', [AuthController::class, 'verifyEmail'], [ValidateSignature::class])
    ->where(['id' => '[0-9]+'])
    ->name('verification.verify');

// Connexion avec Google ou GitHub : 404 tant que le fournisseur n'est pas configuré dans .env.
$router->get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'], [RedirectIfAuthenticated::class])
    ->where(['provider' => 'google|github']);
$router->get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'], [ThrottleRequests::class])
    ->where(['provider' => 'google|github']);

// Double authentification.
$router->get('/two-factor-challenge', [TwoFactorController::class, 'showChallenge']);
$router->post('/two-factor-challenge', [TwoFactorController::class, 'challenge'], [VerifyCsrfToken::class, ThrottleRequests::class]);

// Espace membre.
$router->group(['middleware' => [Authenticate::class]], function ($router) {
    $router->get('/tableau-de-bord', [AppController::class, 'dashboard'])->name('dashboard');

    $router->get('/compte', [AccountController::class, 'show'])->name('account');
    $router->post('/compte/profil', [AccountController::class, 'updateProfile'], [VerifyCsrfToken::class]);
    $router->post('/compte/mot-de-passe', [AccountController::class, 'updatePassword'], [VerifyCsrfToken::class, ThrottleRequests::class]);
    $router->post('/compte/supprimer', [AccountController::class, 'destroy'], [VerifyCsrfToken::class, ThrottleRequests::class]);

    $router->get('/user/two-factor', [TwoFactorController::class, 'show']);
    $router->post('/user/two-factor', [TwoFactorController::class, 'enable'], [VerifyCsrfToken::class, ThrottleRequests::class]);
    $router->post('/user/two-factor/confirm', [TwoFactorController::class, 'confirm'], [VerifyCsrfToken::class, ThrottleRequests::class]);
    $router->post('/user/two-factor/disable', [TwoFactorController::class, 'disable'], [VerifyCsrfToken::class, ThrottleRequests::class]);
});
