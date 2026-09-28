<?php

use App\Support\Team;

layout('layouts.app', ['title' => 'Projets']);
?>
<?= component('components/team-nav', ['active' => '/projets']) ?>

<section class="section">
    <div class="container container--narrow" style="max-width:44rem">
        <h1 style="font-size: var(--step-3)">Projets</h1>

        <?php if ($projects === []): ?>
            <p class="muted">Aucun projet.</p>
        <?php else: ?>
            <div class="stack" style="margin-bottom: var(--space-6)">
                <?php foreach ($projects as $project): ?>
                    <div class="card" style="display:flex; justify-content:space-between; gap: var(--space-4); align-items:center">
                        <div>
                            <h3 style="margin:0"><?= e($project['name']) ?></h3>
                            <?php if ($project['description']): ?><p class="muted" style="margin:0"><?= e($project['description']) ?></p><?php endif; ?>
                        </div>
                        <?php if (Team::hasRole('admin')): ?>
                            <form method="POST" action="<?= e(Team::url('/projets/' . $project['id'] . '/supprimer')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost btn--sm" type="submit">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($canCreate): ?>
            <form class="card" method="POST" action="<?= e(Team::url('/projets')) ?>" novalidate>
                <h2 style="font-size: var(--step-1)">Nouveau projet</h2>
                <?= csrf_field() ?>
                <?= field('name', 'Nom') ?>
                <?= field('description', 'Description', ['rows' => 3, 'required' => false]) ?>
                <button class="btn btn--primary" type="submit">Créer</button>
            </form>
        <?php else: ?>
            <p class="alert alert--info">Votre plan permet <?= (int) $limit ?> projets. <?php if (Team::hasRole('admin')): ?><a href="<?= e(Team::url('/abonnement')) ?>">Passer au plan supérieur</a><?php endif; ?></p>
        <?php endif; ?>
    </div>
</section>
