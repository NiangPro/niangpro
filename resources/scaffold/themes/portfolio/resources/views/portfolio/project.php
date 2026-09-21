<?php
/**
 * @var array<string, mixed> $project
 * @var array<string, mixed> $previous
 * @var array<string, mixed> $next
 */
layout('layouts.app', ['title' => $project['title'], 'description' => $project['summary'], 'active' => '/projets']);
$category = config('site.categories.' . $project['category'], $project['category']);
?>
<header class="page-hero">
    <div class="container">
        <ol class="breadcrumb" aria-label="Fil d'Ariane">
            <li><a href="/">Accueil</a></li>
            <li><a href="/projets">Projets</a></li>
            <li aria-current="page"><?= e($category) ?></li>
        </ol>
        <h1><?= e($project['title']) ?></h1>
        <p><?= e($project['summary']) ?></p>
    </div>
</header>

<section class="section section--tight">
    <div class="container">
        <dl class="facts">
            <div><dt>Client</dt><dd><?= e($project['client']) ?></dd></div>
            <div><dt>Année</dt><dd><?= e((string) $project['year']) ?></dd></div>
            <div><dt>Rôle</dt><dd><?= e($project['role']) ?></dd></div>
            <div><dt>Durée</dt><dd><?= e($project['duration']) ?></dd></div>
        </dl>

        <div class="gallery-grid">
            <?php foreach ($project['gallery'] as $index => $caption): ?>
                <figure class="reveal">
                    <?= component('components/art', ['seed' => $project['slug'] . '-' . $index, 'label' => $caption . ' — ' . $project['title'], 'ratio' => $index === 0 ? '16 / 9' : '4 / 3', 'glyph' => $project['glyph']]) ?>
                    <figcaption><?= e($caption) ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="container split" style="align-items:start">
        <div class="prose">
            <h2>Le défi</h2>
            <p><?= e($project['challenge']) ?></p>
            <h2>Ma démarche</h2>
            <p><?= e($project['approach']) ?></p>
            <h3>Outils et méthodes</h3>
            <div class="tags">
                <?php foreach ($project['stack'] as $item): ?><span class="tag"><?= e($item) ?></span><?php endforeach; ?>
            </div>
        </div>
        <div>
            <h2>Résultats</h2>
            <ul class="stats stats--stacked">
                <?php foreach ($project['results'] as $result): ?>
                    <li class="card"><strong><?= e($result['value']) ?></strong><span><?= e($result['label']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<nav class="section section--tight" aria-label="Autres projets">
    <div class="container project-nav">
        <a class="card" href="/projets/<?= e($previous['slug']) ?>" rel="prev">
            <span class="muted"><?= component('components/icon', ['name' => 'arrow-left']) ?> Projet précédent</span>
            <strong><?= e($previous['title']) ?></strong>
        </a>
        <a class="card" href="/projets/<?= e($next['slug']) ?>" rel="next" style="text-align:right">
            <span class="muted">Projet suivant <?= component('components/icon', ['name' => 'arrow-right']) ?></span>
            <strong><?= e($next['title']) ?></strong>
        </a>
    </div>
</nav>
