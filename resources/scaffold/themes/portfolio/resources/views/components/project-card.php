<?php
/**
 * Carte de projet : le visuel prend toute la place, le texte apparaît en dessous.
 *
 * @var array<string, mixed> $project
 */
$category = config('site.categories.' . $project['category'], $project['category']);
?>
<a class="card card--flush project-card reveal" href="/projets/<?= e($project['slug']) ?>">
    <?= component('components/art', ['seed' => $project['slug'], 'label' => 'Aperçu du projet ' . $project['title'], 'ratio' => '4 / 3', 'glyph' => $project['glyph']]) ?>
    <div class="card__body">
        <p class="project-card__meta"><?= e($category) ?> · <?= e((string) $project['year']) ?></p>
        <h3><?= e($project['title']) ?></h3>
        <p class="muted"><?= e($project['summary']) ?></p>
        <span class="link-arrow" style="margin-top:auto">Voir le projet <?= component('components/icon', ['name' => 'arrow-right']) ?></span>
    </div>
</a>
