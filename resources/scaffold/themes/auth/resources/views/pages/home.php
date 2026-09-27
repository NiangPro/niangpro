<?php layout('layouts.app', ['active' => '/']); ?>

<section class="hero hero--centered">
    <div class="container container--narrow">
        <h1><?= e(config('site.name')) ?></h1>
        <p class="lead"><?= e(config('site.tagline')) ?></p>
        <div class="hero__actions">
            <?php if ($user !== null): ?>
                <a class="btn btn--primary btn--lg" href="/tableau-de-bord">Aller au tableau de bord</a>
            <?php else: ?>
                <a class="btn btn--primary btn--lg" href="/register">Créer un compte</a>
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
