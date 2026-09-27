<?php
/**
 * Navigation d'une organisation.
 *
 * @var string $active '/', '/projets', '/membres' ou '/abonnement'
 */

use App\Support\Team;

$links = ['/' => 'Tableau de bord', '/projets' => 'Projets', '/membres' => 'Membres'];

if (Team::hasRole('admin')) {
    $links['/abonnement'] = 'Abonnement';
}
?>
<div class="team-bar">
    <div class="container team-bar__inner">
        <strong><?= e(Team::current()['name']) ?></strong>
        <nav class="cluster" aria-label="Organisation">
            <?php foreach ($links as $path => $label): ?>
                <a href="<?= e(Team::url($path === '/' ? '' : $path)) ?>"<?= $active === $path ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
