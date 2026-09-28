<?php
/**
 * @var string $content
 * @var string|null $title
 * @var string|null $description
 * @var string|null $active
 */

use Niang\Core\Auth;

$user = Auth::user();
$nav = config('site.nav');

if ($user !== null) {
    $nav = [['label' => 'Tableau de bord', 'href' => '/tableau-de-bord'], ['label' => 'Mon compte', 'href' => '/compte']];
    $actions = '<form method="POST" action="/logout">' . csrf_field()
        . '<button class="btn btn--ghost btn--sm" type="submit">Déconnexion</button></form>';
} else {
    $actions = '<a class="btn btn--ghost btn--sm" href="/login">Connexion</a>';
}

layout('layouts.base', [
    'title' => $title ?? null,
    'description' => $description ?? null,
    'bodyClass' => 'theme-auth',
    'header' => component('components/site-header', [
        'brand' => config('site.name'),
        'nav' => $nav,
        'actions' => $actions,
        'cta' => $user === null ? ['label' => 'Créer un compte', 'href' => '/register'] : null,
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
