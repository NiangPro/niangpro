<?php

/** @var \Niang\Core\Router $router */

use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\PostController as AdminPostController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\Admin\TagController as AdminTagController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\BlogController;
use App\Controllers\ContactController;
use App\Controllers\HealthController;
use App\Controllers\PostController;
use App\Controllers\TagController;
use App\Middleware\EnsureUserIsAdmin;
use App\Middleware\RedirectIfAuthenticated;
use App\Middleware\ThrottleRequests;
use App\Middleware\ValidateSignature;
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

// Espace rédaction : connexion (pas d'inscription publique — les comptes se gèrent dans /admin/utilisateurs)
// et récupération de mot de passe. Un administrateur arrive sur /admin après connexion.
$router->get('/login', [AuthController::class, 'showLogin'], [RedirectIfAuthenticated::class])->name('login');
$router->post('/login', [AuthController::class, 'login'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->post('/logout', [AuthController::class, 'logout'], [VerifyCsrfToken::class]);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], [RedirectIfAuthenticated::class])->name('password.request');
$router->post('/forgot-password', [AuthController::class, 'sendResetLink'], [VerifyCsrfToken::class, ThrottleRequests::class]);
$router->get('/reset-password/{token}/{email}', [AuthController::class, 'showResetPassword'], [ValidateSignature::class])->name('password.reset');
$router->post('/reset-password/{token}/{email}', [AuthController::class, 'resetPassword'], [ValidateSignature::class, VerifyCsrfToken::class]);

// Administration : réservée aux comptes « admin » (EnsureUserIsAdmin). Compte de test créé par
// `niang db:seed` : voir database/seeders/AdminUserSeeder.php.
$router->group(['prefix' => '/admin', 'middleware' => [EnsureUserIsAdmin::class]], function ($router) {
    $id = ['id' => '[0-9]+'];

    $router->get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    $router->get('/articles', [AdminPostController::class, 'index'])->name('admin.posts');
    $router->get('/articles/nouveau', [AdminPostController::class, 'create']);
    $router->post('/articles', [AdminPostController::class, 'store'], [VerifyCsrfToken::class]);
    $router->get('/articles/{id}/modifier', [AdminPostController::class, 'edit'])->where($id);
    $router->post('/articles/{id}', [AdminPostController::class, 'update'], [VerifyCsrfToken::class])->where($id);
    $router->post('/articles/{id}/supprimer', [AdminPostController::class, 'destroy'], [VerifyCsrfToken::class])->where($id);

    $router->get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories');

    $router->get('/tags', [AdminTagController::class, 'index'])->name('admin.tags');
    $router->post('/tags', [AdminTagController::class, 'store'], [VerifyCsrfToken::class]);
    $router->post('/tags/{id}/supprimer', [AdminTagController::class, 'destroy'], [VerifyCsrfToken::class])->where($id);

    $router->get('/utilisateurs', [AdminUserController::class, 'index'])->name('admin.users');
    $router->post('/utilisateurs', [AdminUserController::class, 'store'], [VerifyCsrfToken::class]);
    $router->post('/utilisateurs/{id}/role', [AdminUserController::class, 'updateRole'], [VerifyCsrfToken::class])->where($id);

    $router->get('/parametres', [AdminSettingsController::class, 'index'])->name('admin.settings');
    $router->post('/parametres/profil', [AdminSettingsController::class, 'updateProfile'], [VerifyCsrfToken::class]);
    $router->post('/parametres/mot-de-passe', [AdminSettingsController::class, 'updatePassword'], [VerifyCsrfToken::class]);
});
