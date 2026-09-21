<?php layout('layouts.app', ['title' => 'Réalisations', 'active' => '/realisations', 'description' => "Logements, commerces et bureaux : une sélection de projets d'Atelier Lumière."]); ?>

<?= component('components/page-hero', [
    'title' => 'Nos réalisations',
    'lead' => "Une sélection de projets livrés ces trois dernières années. Les illustrations sont des visuels de démonstration : remplacez-les par vos propres photos.",
    'crumbs' => [],
]) ?>

<section class="section">
    <div class="container">
        <div class="grid grid--3">
            <?php foreach (config('site.projects') as $project): ?>
                <figure class="card card--flush gallery-item reveal">
                    <?= component('components/art', ['seed' => $project['slug'], 'label' => $project['title'] . ' — ' . $project['type'], 'glyph' => $project['glyph']]) ?>
                    <figcaption class="card__body">
                        <h2 style="font-size: var(--step-1); margin:0"><?= e($project['title']) ?></h2>
                        <div class="tags">
                            <span class="tag"><?= e($project['type']) ?></span>
                            <span class="tag"><?= e($project['surface']) ?></span>
                            <span class="tag"><?= e((string) $project['year']) ?></span>
                        </div>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?= component('components/cta-band', [
            'title' => 'Votre projet pourrait figurer ici',
            'primary' => ['label' => 'Démarrer un projet', 'href' => '/contact'],
        ]) ?>
    </div>
</section>
