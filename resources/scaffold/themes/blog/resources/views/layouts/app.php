<?php
/**
 * @var string $content
 * @var string|null $title
 * @var string|null $description
 * @var string|null $active
 */

use App\Models\User;
use Niang\Core\Auth;

// Un administrateur connecté retrouve le tableau de bord depuis n'importe quelle page du blog.
$actions = User::isAdmin(Auth::user())
    ? '<a class="icon-btn" href="/admin" aria-label="Administration">' . component('components/icon', ['name' => 'grid']) . '</a>'
    : '';

layout('layouts.base', [
    'title' => $title ?? null,
    'description' => $description ?? null,
    'bodyClass' => 'theme-blog',
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
