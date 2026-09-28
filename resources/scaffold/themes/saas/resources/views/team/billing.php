<?php

use App\Support\Team;

layout('layouts.app', ['title' => 'Abonnement']);
?>
<?= component('components/team-nav', ['active' => '/abonnement']) ?>

<section class="section">
    <div class="container">
        <h1 style="font-size: var(--step-3)">Abonnement</h1>
        <p class="muted">
            Plan actuel : <strong><?= e($plans[$current]['name'] ?? $current) ?></strong>
            <?php if ($organization['subscription_ends_at']): ?> — renouvellement le <?= e(date('d/m/Y', strtotime((string) $organization['subscription_ends_at']))) ?><?php endif; ?>
        </p>

        <div class="grid grid--2">
            <?php foreach ($plans as $key => $plan): ?>
                <article class="card">
                    <h2 style="font-size: var(--step-1)"><?= e($plan['name']) ?></h2>
                    <p style="font-size: var(--step-2); margin:0"><?= e((string) $plan['price']) ?> <?= e(config('billing.currency')) ?> / mois</p>
                    <ul class="checklist">
                        <li><?= $plan['limits']['projects'] === null ? 'Projets illimités' : e($plan['limits']['projects'] . ' projets') ?></li>
                        <li><?= $plan['limits']['members'] === null ? 'Membres illimités' : e($plan['limits']['members'] . ' membres') ?></li>
                    </ul>
                    <?php if ($key === $current): ?>
                        <p class="badge">Plan actuel</p>
                        <?php if ($isOwner && $key !== config('billing.default_plan')): ?>
                            <form method="POST" action="<?= e(Team::url('/abonnement/resilier')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--ghost btn--sm" type="submit">Résilier</button>
                            </form>
                        <?php endif; ?>
                    <?php elseif ($isOwner && $key !== config('billing.default_plan')): ?>
                        <form method="POST" action="<?= e(Team::url('/abonnement')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="plan" value="<?= e($key) ?>">
                            <button class="btn btn--primary" type="submit">Choisir ce plan</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$isOwner): ?><p class="muted" style="margin-top: var(--space-5)">Seul un propriétaire peut changer d'abonnement.</p><?php endif; ?>
    </div>
</section>
