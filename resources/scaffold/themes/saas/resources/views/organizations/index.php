<?php layout('layouts.app', ['title' => 'Organisations', 'active' => '/organisations']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:40rem">
        <h1 style="font-size: var(--step-3)">Organisations</h1>

        <?php if ($organizations === []): ?>
            <p class="lead">Créez votre première organisation : votre équipe, vos projets, votre abonnement.</p>
        <?php else: ?>
            <div class="stack" style="margin-bottom: var(--space-6)">
                <?php foreach ($organizations as $organization): ?>
                    <a class="card card--hover" href="/o/<?= e($organization['slug']) ?>">
                        <h3 style="margin:0"><?= e($organization['name']) ?></h3>
                        <p class="muted" style="margin:0"><?= e(\App\Models\Membership::ROLES[$organization['role']] ?? $organization['role']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="card" method="POST" action="/organisations" novalidate>
            <h2 style="font-size: var(--step-1)">Nouvelle organisation</h2>
            <?= csrf_field() ?>
            <?= field('name', 'Nom de l\'organisation', ['autocomplete' => 'organization']) ?>
            <button class="btn btn--primary" type="submit">Créer</button>
        </form>
    </div>
</section>
