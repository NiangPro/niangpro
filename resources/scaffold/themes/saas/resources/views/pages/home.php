<?php layout('layouts.app', ['active' => '/']); ?>

<section class="hero hero--centered">
    <div class="container container--narrow">
        <h1><?= e(config('site.name')) ?></h1>
        <p class="lead"><?= e(config('site.tagline')) ?></p>
        <div class="hero__actions">
            <?php if ($user !== null): ?>
                <a class="btn btn--primary btn--lg" href="/organisations">Mes organisations</a>
            <?php else: ?>
                <a class="btn btn--primary btn--lg" href="/register">Commencer gratuitement</a>
                <a class="btn btn--ghost btn--lg" href="/login">Se connecter</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="grid grid--3">
            <?php foreach (config('site.features') as $feature): ?>
                <article class="card">
                    <span class="card__icon"><?= component('components/icon', ['name' => $feature['icon']]) ?></span>
                    <h3><?= e($feature['title']) ?></h3>
                    <p class="muted"><?= e($feature['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="tarifs">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Tarifs</span>
            <h2>Un plan pour chaque équipe</h2>
        </div>
        <div class="grid grid--2">
            <?php foreach ($plans as $plan): ?>
                <article class="card">
                    <h3><?= e($plan['name']) ?></h3>
                    <p style="font-size: var(--step-3); margin: 0"><?= e((string) $plan['price']) ?> <?= e(config('billing.currency')) ?><span class="muted" style="font-size: var(--step-0)"> / mois</span></p>
                    <ul class="checklist">
                        <li><?= $plan['limits']['projects'] === null ? 'Projets illimités' : e($plan['limits']['projects'] . ' projets') ?></li>
                        <li><?= $plan['limits']['members'] === null ? 'Membres illimités' : e($plan['limits']['members'] . ' membres') ?></li>
                    </ul>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
