<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\BillingController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\InvitationController;
use App\Controllers\MemberController;
use App\Controllers\OrganizationController;
use App\Controllers\ProjectController;
use App\Controllers\SocialAuthController;
use App\Controllers\TwoFactorController;
use App\Middleware\Authenticate;
use App\Middleware\EnsureTeamMember;
use App\Middleware\IdentifyTenant;
use App\Middleware\RedirectIfAuthenticated;
use App\Middleware\ThrottleRequests;
use App\Middleware\ValidateSignature;
use App\Middleware\VerifyCsrfToken;

// Supervision : à brancher sur votre outil de monitoring.
$router->get('/up', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);
$router->get('/health/ready', [HealthController::class, 'index']);
$router->get('/health/live', [HealthController::class, 'live']);

$router->get('/', [HomeController::class, 'index']);

// Comptes : inscription, connexion, mot de passe oublié, vérification d'email, OAuth, 2FA.
$router->get('/register', [AuthController::class, 'showRegister'], [RedirectIfAuthenticated::class])->name('register');
$router->post('/register', [AuthController::class, 'register'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/login', [AuthController::class, 'showLogin'], [RedirectIfAuthenticated::class])->name('login');
$router->post('/login', [AuthController::class, 'login'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->post('/logout', [AuthController::class, 'logout'], [VerifyCsrfToken::class]);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], [RedirectIfAuthenticated::class])->name('password.request');
$router->post('/forgot-password', [AuthController::class, 'sendResetLink'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/reset-password/{token}/{email}', [AuthController::class, 'showResetPassword'], [ValidateSignature::class])->name('password.reset');
$router->post('/reset-password/{token}/{email}', [AuthController::class, 'resetPassword'], [ValidateSignature::class, VerifyCsrfToken::class]);
$router->get('/verify-email/{id}', [AuthController::class, 'verifyEmail'], [ValidateSignature::class])
    ->where(['id' => '[0-9]+'])
    ->name('verification.verify');
$router->get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'], [RedirectIfAuthenticated::class])
    ->where(['provider' => 'google|github']);
$router->get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'], [ThrottleRequests::class])
    ->where(['provider' => 'google|github']);
$router->get('/two-factor-challenge', [TwoFactorController::class, 'showChallenge']);
$router->post('/two-factor-challenge', [TwoFactorController::class, 'challenge'], [VerifyCsrfToken::class, ThrottleRequests::class]);

// Paiement : appelé par le prestataire, sans session (voir App\Billing\BillingProvider::webhook()).
$router->post('/billing/webhook', [BillingController::class, 'webhook'], [ThrottleRequests::class]);

$router->group(['middleware' => [Authenticate::class]], function ($router) {
    $router->get('/compte', [AccountController::class, 'show'])->name('account');
    $router->post('/compte/profil', [AccountController::class, 'updateProfile'], [VerifyCsrfToken::class]);
    $router->post('/compte/mot-de-passe', [AccountController::class, 'updatePassword'], [VerifyCsrfToken::class, ThrottleRequests::class]);
    $router->post('/compte/supprimer', [AccountController::class, 'destroy'], [VerifyCsrfToken::class, ThrottleRequests::class]);
    $router->get('/user/two-factor', [TwoFactorController::class, 'show']);
    $router->post('/user/two-factor', [TwoFactorController::class, 'enable'], [VerifyCsrfToken::class, ThrottleRequests::class]);
    $router->post('/user/two-factor/confirm', [TwoFactorController::class, 'confirm'], [VerifyCsrfToken::class, ThrottleRequests::class]);
    $router->post('/user/two-factor/disable', [TwoFactorController::class, 'disable'], [VerifyCsrfToken::class, ThrottleRequests::class]);

    $router->get('/organisations', [OrganizationController::class, 'index'])->name('organizations');
    $router->post('/organisations', [OrganizationController::class, 'store'], [VerifyCsrfToken::class, ThrottleRequests::class]);

    $router->get('/invitations/{token}', [InvitationController::class, 'show'])->where(['token' => '[a-f0-9]{64}']);
    $router->post('/invitations/{token}', [InvitationController::class, 'accept'], [VerifyCsrfToken::class])->where(['token' => '[a-f0-9]{64}']);

    // Une organisation : /o/<slug>/... Réservé à ses membres (404 pour les autres) ; écriture
    // réservée aux administrateurs là où c'est indiqué.
    $admin = EnsureTeamMember::class . ':admin';
    $id = ['id' => '[0-9]+'];

    $router->group(['prefix' => '/o/{tenant}', 'middleware' => [IdentifyTenant::class, EnsureTeamMember::class]], function ($router) use ($admin, $id) {
        $router->get('/', [OrganizationController::class, 'dashboard']);

        $router->get('/projets', [ProjectController::class, 'index']);
        $router->post('/projets', [ProjectController::class, 'store'], [VerifyCsrfToken::class]);
        $router->post('/projets/{id}/supprimer', [ProjectController::class, 'destroy'], [$admin, VerifyCsrfToken::class])->where($id);

        $router->get('/membres', [MemberController::class, 'index']);
        $router->post('/membres/quitter', [MemberController::class, 'leave'], [VerifyCsrfToken::class]);
        $router->post('/membres/invitations', [MemberController::class, 'invite'], [$admin, VerifyCsrfToken::class, ThrottleRequests::class]);
        $router->post('/membres/invitations/{id}/annuler', [MemberController::class, 'cancelInvitation'], [$admin, VerifyCsrfToken::class])->where($id);
        $router->post('/membres/{id}/role', [MemberController::class, 'updateRole'], [$admin, VerifyCsrfToken::class])->where($id);
        $router->post('/membres/{id}/retirer', [MemberController::class, 'remove'], [$admin, VerifyCsrfToken::class])->where($id);

        $router->get('/abonnement', [BillingController::class, 'show'], [$admin]);
        $router->post('/abonnement', [BillingController::class, 'checkout'], [EnsureTeamMember::class . ':owner', VerifyCsrfToken::class]);
        $router->post('/abonnement/resilier', [BillingController::class, 'cancel'], [EnsureTeamMember::class . ':owner', VerifyCsrfToken::class]);
    });
});
