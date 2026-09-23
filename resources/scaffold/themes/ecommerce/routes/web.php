<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\AccountController;
use App\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Controllers\Admin\ProductController as AdminProductController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\ContactController;
use App\Controllers\HealthController;
use App\Controllers\ShopController;
use App\Middleware\Authenticate;
use App\Middleware\EnsureUserIsAdmin;
use App\Middleware\RedirectIfAuthenticated;
use App\Middleware\ThrottleRequests;
use App\Middleware\ValidateSignature;
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

// Récupération de mot de passe + vérification d'email (signedRoute()) — voir AuthController.
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], [RedirectIfAuthenticated::class])->name('password.request');
$router->post('/forgot-password', [AuthController::class, 'sendResetLink'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/reset-password/{token}/{email}', [AuthController::class, 'showResetPassword'], [ValidateSignature::class])->name('password.reset');
$router->post('/reset-password/{token}/{email}', [AuthController::class, 'resetPassword'], [ValidateSignature::class, VerifyCsrfToken::class]);
$router->get('/verify-email/{id}', [AuthController::class, 'verifyEmail'], [ValidateSignature::class])
    ->where(['id' => '[0-9]+'])
    ->name('verification.verify');

// Pages d'information.
$router->get('/a-propos', [ShopController::class, 'about']);
$router->get('/faq', [ShopController::class, 'faq']);
$router->get('/cgv', [ShopController::class, 'terms']);
$router->get('/mentions-legales', [ShopController::class, 'legal']);

// Contact : validé par App\Requests\ContactRequest, protégé contre le CSRF et le spam.
$router->get('/contact', [ContactController::class, 'index'])->name('contact');
$router->post('/contact', [ContactController::class, 'store'], [VerifyCsrfToken::class, ThrottleRequests::class]);

// Administration : réservée aux comptes « admin » (EnsureUserIsAdmin), qui y arrivent directement
// après connexion. Compte de test créé par `niang db:seed` : voir database/seeders/AdminUserSeeder.php.
$router->group(['prefix' => '/admin', 'middleware' => [EnsureUserIsAdmin::class]], function ($router) {
    $id = ['id' => '[0-9]+'];

    $router->get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    $router->get('/commandes', [AdminOrderController::class, 'index'])->name('admin.orders');
    $router->get('/commandes/{id}', [AdminOrderController::class, 'show'])->where($id);
    $router->post('/commandes/{id}/statut', [AdminOrderController::class, 'updateStatus'], [VerifyCsrfToken::class])->where($id);

    $router->get('/clients', [AdminCustomerController::class, 'index'])->name('admin.customers');

    $router->get('/produits', [AdminProductController::class, 'index'])->name('admin.products');
    $router->get('/produits/nouveau', [AdminProductController::class, 'create']);
    $router->post('/produits', [AdminProductController::class, 'store'], [VerifyCsrfToken::class]);
    $router->get('/produits/{id}/modifier', [AdminProductController::class, 'edit'])->where($id);
    $router->post('/produits/{id}', [AdminProductController::class, 'update'], [VerifyCsrfToken::class])->where($id);
    $router->post('/produits/{id}/supprimer', [AdminProductController::class, 'destroy'], [VerifyCsrfToken::class])->where($id);

    $router->get('/categories', [AdminProductController::class, 'categories'])->name('admin.categories');
    $router->get('/stock', [AdminProductController::class, 'stock'])->name('admin.stock');
    $router->post('/stock/{id}', [AdminProductController::class, 'updateStock'], [VerifyCsrfToken::class])->where($id);

    $router->get('/parametres', [AdminSettingsController::class, 'index'])->name('admin.settings');
    $router->post('/parametres/profil', [AdminSettingsController::class, 'updateProfile'], [VerifyCsrfToken::class]);
    $router->post('/parametres/mot-de-passe', [AdminSettingsController::class, 'updatePassword'], [VerifyCsrfToken::class]);
});
