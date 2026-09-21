<?php
layout('layouts.app', ['title' => 'À propos et CV', 'active' => '/a-propos', 'description' => 'Parcours, compétences et méthode de travail d\'Amina Sow.']);
$about = config('site.about');
?>
<?= component('components/page-hero', ['title' => 'À propos', 'lead' => $about['lead'], 'crumbs' => []]) ?>

<section class="section">
    <div class="container split" style="align-items:start">
        <div class="portrait">
            <?= component('components/art', ['seed' => 'amina-portrait', 'label' => 'Portrait stylisé d\'Amina Sow, illustration abstraite', 'ratio' => '4 / 5', 'glyph' => 'user']) ?>
        </div>
        <div class="prose">
            <span class="eyebrow">Mon parcours</span>
            <h2>Entre la maquette et le code</h2>
            <?php foreach ($about['paragraphs'] as $paragraph): ?>
                <p><?= e($paragraph) ?></p>
            <?php endforeach; ?>
            <p><a class="btn btn--primary" href="/contact">Me contacter</a></p>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head"><span class="eyebrow">Compétences</span><h2>Ce que je sais faire</h2></div>
        <div class="grid grid--3">
            <?php foreach (config('site.skills') as $group): ?>
                <article class="card reveal">
                    <h3><?= e($group['title']) ?></h3>
                    <ul class="checklist" style="margin:0">
                        <?php foreach ($group['items'] as $item): ?>
                            <li><?= component('components/icon', ['name' => 'check']) ?> <?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container split" style="align-items:start">
        <div>
            <h2>Expérience</h2>
            <ol class="timeline">
                <?php foreach (config('site.experience') as $item): ?>
                    <li class="reveal">
                        <span class="timeline__period"><?= e($item['period']) ?></span>
                        <h3><?= e($item['title']) ?></h3>
                        <p class="muted"><?= e($item['text']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <div>
            <h2>Formation</h2>
            <ol class="timeline">
                <?php foreach (config('site.education') as $item): ?>
                    <li class="reveal">
                        <span class="timeline__period"><?= e($item['period']) ?></span>
                        <h3><?= e($item['title']) ?></h3>
                        <p class="muted"><?= e($item['text']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</section>
