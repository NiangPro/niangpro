<?php
/** @var list<array<string, mixed>> $featured  projets mis en avant */
layout('layouts.app', ['active' => '/']);
$intro = config('site.intro');
?>
<section class="hero">
    <div class="container split">
        <div>
            <span class="eyebrow"><?= e($intro['greeting']) ?></span>
            <h1><?= e($intro['headline']) ?></h1>
            <p class="lead"><?= e($intro['text']) ?></p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="/projets">Voir mes projets</a>
                <a class="btn btn--ghost btn--lg" href="/a-propos">Mon parcours</a>
            </div>
            <ul class="stats">
                <?php foreach (config('site.stats') as $stat): ?>
                    <li><strong><?= e($stat['value']) ?></strong><span><?= e($stat['label']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="portrait">
            <?= component('components/art', ['seed' => 'amina-portrait', 'label' => 'Portrait stylisé d\'Amina Sow, illustration abstraite', 'ratio' => '4 / 5', 'glyph' => 'user']) ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head section-head--left">
            <span class="eyebrow">Projets phares</span>
            <h2>Une sélection de travaux récents</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach ($featured as $project): ?>
                <?= component('components/project-card', ['project' => $project]) ?>
            <?php endforeach; ?>
        </div>
        <p style="margin-top: var(--space-6)"><a class="link-arrow" href="/projets">Tous les projets <?= component('components/icon', ['name' => 'arrow-right']) ?></a></p>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Ma façon de travailler</span>
            <h2>Observer, simplifier, vérifier</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach ([
                ['eye', 'Observer', "Je commence par regarder de vrais utilisateurs faire de vraies tâches. Les hypothèses attendent."],
                ['sparkles', 'Simplifier', "Chaque écran, chaque option doit justifier sa présence. Ce qui ne sert pas disparaît."],
                ['shield', 'Vérifier', "Clavier, lecteur d'écran, petit écran, connexion lente : je teste avant de livrer."],
            ] as [$icon, $title, $text]): ?>
                <article class="card reveal">
                    <span class="card__icon"><?= component('components/icon', ['name' => $icon]) ?></span>
                    <h3><?= e($title) ?></h3>
                    <p class="muted"><?= e($text) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?= component('components/cta-band', [
            'title' => 'Un projet en tête ?',
            'text' => 'Parlez-m\'en en quelques lignes : je réponds dans la semaine.',
            'primary' => ['label' => 'Me contacter', 'href' => '/contact'],
        ]) ?>
    </div>
</section>
