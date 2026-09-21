<?php
layout('layouts.app', ['active' => '/']);

$projects = array_slice(config('site.projects'), 0, 3);
$services = array_slice(config('site.services'), 0, 3);
?>
<section class="hero">
    <div class="container split">
        <div>
            <span class="eyebrow"><?= e(config('site.tagline')) ?></span>
            <h1>Des intérieurs qui vous <em>ressemblent</em>, pensés dans les moindres détails</h1>
            <p class="lead">Nous concevons et rénovons logements, commerces et bureaux, de la première esquisse à la remise des clés, avec un seul interlocuteur.</p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="/contact">Demander un devis gratuit</a>
                <a class="btn btn--ghost btn--lg" href="/realisations">Voir nos réalisations</a>
            </div>
            <ul class="stats">
                <?php foreach (config('site.stats') as $stat): ?>
                    <li><strong><?= e($stat['value']) ?></strong><span><?= e($stat['label']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="hero-collage" aria-hidden="false">
            <?= component('components/art', ['seed' => 'hero-1', 'label' => "Salon lumineux aux tons chauds, illustration abstraite", 'ratio' => '4 / 5', 'glyph' => 'home']) ?>
            <?= component('components/art', ['seed' => 'hero-2', 'label' => "Détail d'un meuble sur mesure, illustration abstraite", 'ratio' => '1 / 1', 'glyph' => 'layers']) ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container split">
        <?= component('components/art', ['seed' => 'studio', 'label' => "L'équipe du studio au travail, illustration abstraite", 'ratio' => '4 / 3', 'glyph' => 'users']) ?>
        <div class="reveal">
            <span class="eyebrow">Le studio</span>
            <h2>Une équipe à taille humaine, un suivi de bout en bout</h2>
            <p class="muted">Fondé en 2010 à Lyon, Atelier Lumière réunit architectes d'intérieur, designers et conducteurs de travaux. Nous choisissons peu de projets pour les mener vraiment bien.</p>
            <ul class="checklist">
                <li><?= component('components/icon', ['name' => 'check']) ?> Un rendez-vous de cadrage gratuit, chez vous</li>
                <li><?= component('components/icon', ['name' => 'check']) ?> Un devis détaillé poste par poste, sans surprise</li>
                <li><?= component('components/icon', ['name' => 'check']) ?> Un chantier piloté et documenté chaque semaine</li>
            </ul>
            <a class="link-arrow" href="/a-propos">Découvrir le studio <?= component('components/icon', ['name' => 'arrow-right']) ?></a>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Nos points forts</span>
            <h2>Ce qui fait la différence</h2>
        </div>
        <div class="grid grid--4">
            <?php foreach (config('site.highlights') as $highlight): ?>
                <article class="card reveal">
                    <span class="card__icon"><?= component('components/icon', ['name' => $highlight['icon']]) ?></span>
                    <h3><?= e($highlight['title']) ?></h3>
                    <p class="muted"><?= e($highlight['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Services</span>
            <h2>Un accompagnement complet</h2>
            <p>Du simple conseil à la rénovation clé en main.</p>
        </div>
        <div class="grid grid--3">
            <?php foreach ($services as $service): ?>
                <a class="card reveal" href="/services">
                    <span class="card__icon"><?= component('components/icon', ['name' => $service['icon']]) ?></span>
                    <h3><?= e($service['title']) ?></h3>
                    <p class="muted"><?= e($service['text']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="text-center" style="margin-top: var(--space-6)">
            <a class="link-arrow" href="/services">Tous nos services <?= component('components/icon', ['name' => 'arrow-right']) ?></a>
        </p>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Réalisations</span>
            <h2>Quelques projets récents</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach ($projects as $project): ?>
                <a class="card card--flush gallery-item reveal" href="/realisations">
                    <?= component('components/art', ['seed' => $project['slug'], 'label' => $project['title'] . ' — ' . $project['type'], 'glyph' => $project['glyph']]) ?>
                    <div class="card__body">
                        <h3><?= e($project['title']) ?></h3>
                        <p class="muted"><?= e($project['type']) ?> · <?= e($project['surface']) ?> · <?= e((string) $project['year']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Témoignages</span>
            <h2>Ils nous ont fait confiance</h2>
        </div>
        <?= component('components/testimonials', ['items' => config('site.testimonials')]) ?>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?= component('components/cta-band', [
            'title' => 'Parlons de votre projet',
            'text' => "Décrivez-nous votre intérieur et vos envies : nous vous répondons sous un jour ouvré.",
            'primary' => ['label' => 'Demander un devis', 'href' => '/contact'],
            'secondary' => ['label' => 'Voir la FAQ', 'href' => '/faq'],
        ]) ?>
    </div>
</section>
