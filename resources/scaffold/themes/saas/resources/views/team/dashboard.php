<?php layout('layouts.app', ['title' => $organization['name']]); ?>
<?= component('components/team-nav', ['active' => '/']) ?>

<section class="section">
    <div class="container">
        <span class="eyebrow">Plan <?= e($plan['name']) ?></span>
        <h1 style="font-size: var(--step-3)"><?= e($organization['name']) ?></h1>

        <div class="grid grid--3" style="margin: var(--space-5) 0">
            <div class="card"><p class="muted" style="margin:0">Projets</p><p style="font-size: var(--step-3); margin:0"><?= (int) $projects ?><?= $plan['limits']['projects'] !== null ? ' / ' . (int) $plan['limits']['projects'] : '' ?></p></div>
            <div class="card"><p class="muted" style="margin:0">Membres</p><p style="font-size: var(--step-3); margin:0"><?= (int) $members ?><?= $plan['limits']['members'] !== null ? ' / ' . (int) $plan['limits']['members'] : '' ?></p></div>
            <div class="card"><p class="muted" style="margin:0">Votre rôle</p><p style="font-size: var(--step-2); margin:0"><?= e(\App\Models\Membership::ROLES[\App\Support\Team::role()] ?? '') ?></p></div>
        </div>

        <h2 style="font-size: var(--step-1)">Projets récents</h2>
        <?php if ($recent === []): ?>
            <p class="muted">Aucun projet pour l'instant. <a href="<?= e(\App\Support\Team::url('/projets')) ?>">Créer le premier</a></p>
        <?php else: ?>
            <ul>
                <?php foreach ($recent as $project): ?>
                    <li><?= e($project['name']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
