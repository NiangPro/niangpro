<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\ContactController;
use App\Controllers\HealthController;
use App\Controllers\ShopController;
use App\Middleware\Authenticate;
use App\Middleware\RedirectIfAuthenticated;
use App\Middleware\ThrottleRequests;
use App\Middleware\VerifyCsrfToken;

// Supervision : à brancher sur votre outil de monitoring.
$router->get('/up', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);

// Boutique : accueil, catalogue (?categorie=&tri=&q=), fiche produit.
$router->get('/', [ShopController::class, 'home']);
$router->get('/boutique', [ShopController::class, 'catalog']);
$router->get('/boutique/{slug}', [ShopController::class, 'show'])->where(['slug' => '[a-z0-9-]+']);

// Panier (en session).
$router->get('/panier', [CartController::class, 'show'])->name('cart');
$router->post('/panier/ajouter', [CartController::class, 'add'], [VerifyCsrfToken::class]);
$router->post('/panier/{id}/modifier', [CartController::class, 'update'], [VerifyCsrfToken::class])->where(['id' => '[0-9]+']);
$router->post('/panier/{id}/retirer', [CartController::class, 'remove'], [VerifyCsrfToken::class])->where(['id' => '[0-9]+']);

// Commande. Le paiement n'est pas branché : voir le TODO de CheckoutController.
$router->get('/commande', [CheckoutController::class, 'show'])->name('checkout');
$router->post('/commande', [CheckoutController::class, 'store'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/commande/confirmation/{reference}', [CheckoutController::class, 'confirmation'])->where(['reference' => '[A-Z0-9-]+']);

// Compte client.
$router->get('/register', [AuthController::class, 'showRegister'], [RedirectIfAuthenticated::class])->name('register');
$router->post('/register', [AuthController::class, 'register'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/login', [AuthController::class, 'showLogin'], [RedirectIfAuthenticated::class])->name('login');
$router->post('/login', [AuthController::class, 'login'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->post('/logout', [AuthController::class, 'logout'], [VerifyCsrfToken::class]);
$router->get('/compte/commandes', [AccountController::class, 'orders'], [Authenticate::class])->name('account.orders');

// Pages d'information.
$router->get('/a-propos', [ShopController::class, 'about']);
$router->get('/faq', [ShopController::class, 'faq']);
$router->get('/cgv', [ShopController::class, 'terms']);
$router->get('/mentions-legales', [ShopController::class, 'legal']);

// Contact : validé par App\Requests\ContactRequest, protégé contre le CSRF et le spam.
$router->get('/contact', [ContactController::class, 'index'])->name('contact');
$router->post('/contact', [ContactController::class, 'store'], [VerifyCsrfToken::class, ThrottleRequests::class]);
