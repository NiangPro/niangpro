<?php layout('layouts.app', ['title' => 'À propos', 'active' => '/a-propos', 'description' => 'Maison Nomade : des objets du quotidien fabriqués en petites séries par des artisans français.']); ?>

<?= component('components/page-hero', ['title' => 'À propos', 'lead' => 'Nous choisissons peu d\'objets, mais nous les choisissons bien.', 'crumbs' => []]) ?>

<section class="section">
    <div class="container split">
        <div class="prose">
            <span class="eyebrow">Notre histoire</span>
            <h2>Partie d'une étagère mal remplie</h2>
            <p>Maison Nomade est née en 2019 à Nantes, du constat qu'il était difficile de trouver de beaux objets du quotidien qui ne soient ni jetables ni hors de prix.</p>
            <p>Nous visitons nous-mêmes chaque atelier, nous testons chaque pièce pendant plusieurs semaines, et nous ne gardons que ce que nous utiliserions chez nous.</p>
            <p>Aujourd'hui, une quinzaine d'artisans français fabriquent nos collections en petites séries : verre soufflé en Lorraine, grès en Bourgogne, lin en Normandie.</p>
        </div>
        <?= component('components/art', ['seed' => 'atelier-nomade', 'label' => "Un atelier d'artisan, illustration abstraite", 'ratio' => '4 / 3', 'glyph' => 'heart']) ?>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head"><span class="eyebrow">Nos engagements</span><h2>Ce que nous vous promettons</h2></div>
        <div class="grid grid--3">
            <?php foreach ([
                ['leaf', 'Matières durables', 'Lin, verre, grès, liège, coton bio : des matériaux qui vieillissent bien et se recyclent.'],
                ['users', 'Artisans identifiés', 'Chaque fiche produit indique où et par qui l\'objet est fabriqué.'],
                ['refresh', 'Réparer avant de jeter', 'Une pièce abîmée ? Nous la faisons réparer ou nous la remplaçons.'],
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
