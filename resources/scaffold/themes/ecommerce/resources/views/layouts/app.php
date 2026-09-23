<?php
/**
 * Layout de la boutique : en-tête avec compte et panier (pastille du nombre d'articles), pied de page.
 *
 * @var string $content
 * @var string|null $title
 * @var string|null $description
 * @var string|null $active
 */

use App\Models\User;
use App\Support\Cart;
use Niang\Core\Auth;

$count = Cart::count();
$accountHref = Auth::check() ? '/compte/commandes' : '/login';
$accountLabel = Auth::check() ? 'Mon compte et mes commandes' : 'Se connecter';

$actions = (User::isAdmin(Auth::user())
        ? '<a class="icon-btn" href="/admin" aria-label="Administration">' . component('components/icon', ['name' => 'grid']) . '</a>'
        : '')
    . '<a class="icon-btn" href="' . e($accountHref) . '" aria-label="' . e($accountLabel) . '">'
    . component('components/icon', ['name' => 'user']) . '</a>'
    . '<a class="icon-btn" href="/panier" aria-label="Panier, ' . $count . ' article' . ($count > 1 ? 's' : '') . '">'
    . component('components/icon', ['name' => 'bag'])
    . ($count > 0 ? '<span class="cart-badge">' . $count . '</span>' : '')
    . '</a>';

layout('layouts.base', [
    'title' => $title ?? null,
    'description' => $description ?? null,
    'bodyClass' => 'theme-boutique',
    'header' => component('components/site-header', [
        'brand' => config('site.name'),
        'nav' => config('site.nav'),
        'actions' => $actions,
        'active' => $active ?? '',
    ]),
    'footer' => component('components/site-footer', [
        'brand' => config('site.name'),
        'tagline' => config('site.tagline'),
        'columns' => config('site.footer'),
        'legal' => config('site.legal'),
    ]),
]);
?>
<?= $content ?>
