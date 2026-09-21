<?php
/**
 * @var list<array<string, mixed>> $projects
 * @var string $category  slug de la catégorie filtrée, '' pour toutes
 */
layout('layouts.app', ['title' => 'Projets', 'active' => '/projets', 'description' => 'Applications, systèmes de design et sites : une sélection de projets récents.']);
$categories = config('site.categories');
?>
<?= component('components/page-hero', [
    'title' => 'Projets',
    'lead' => 'Une sélection de travaux, du produit numérique au site accessible. Les projets et clients sont fictifs : remplacez-les par les vôtres.',
    'crumbs' => [],
]) ?>

<section class="section">
    <div class="container">
        <div class="tags filters" role="group" aria-label="Filtrer par catégorie">
            <a class="tag<?= $category === '' ? ' tag--active' : '' ?>" href="/projets"<?= $category === '' ? ' aria-current="true"' : '' ?>>Tous</a>
            <?php foreach ($categories as $slug => $label): ?>
                <a class="tag<?= $category === $slug ? ' tag--active' : '' ?>" href="/projets?categorie=<?= e($slug) ?>"<?= $category === $slug ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (!$projects): ?>
            <p class="muted">Aucun projet dans cette catégorie pour l'instant.</p>
        <?php endif; ?>

        <div class="grid grid--3">
            <?php foreach ($projects as $project): ?>
                <?= component('components/project-card', ['project' => $project]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
