<?php layout('layouts.app', ['title' => 'À propos', 'active' => '/a-propos', 'description' => "Atelier Lumière : un studio lyonnais d'architecture d'intérieur, sa méthode, ses valeurs et son équipe."]); ?>

<?= component('components/page-hero', [
    'title' => 'À propos',
    'lead' => "Un studio lyonnais d'architecture d'intérieur qui préfère peu de projets, bien menés.",
    'crumbs' => [],
]) ?>

<section class="section">
    <div class="container split">
        <div class="prose">
            <span class="eyebrow">Notre histoire</span>
            <h2>Née d'un chantier qui aurait pu mieux se passer</h2>
            <p>En 2010, Inès Marchand rénove son propre appartement et découvre ce que vivent tant de particuliers : des artisans qui ne se parlent pas, des devis qui gonflent, des délais qui glissent.</p>
            <p>Elle fonde Atelier Lumière avec une idée simple : qu'une seule équipe conçoive, chiffre et pilote, pour que la personne qui vous a écouté soit aussi celle qui surveille le chantier.</p>
            <p>Quinze ans plus tard, nous sommes six, toujours à Lyon, et toujours fidèles à cette promesse.</p>
        </div>
        <?= component('components/art', ['seed' => 'histoire', 'label' => "Le premier atelier du studio, illustration abstraite", 'ratio' => '4 / 3', 'glyph' => 'pen']) ?>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Nos valeurs</span>
            <h2>Ce qui guide nos décisions</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach ([
                ['leaf', 'Durable', 'Matériaux sains, filières locales, réemploi quand il est possible : un bon intérieur se garde longtemps.'],
                ['eye', 'Transparent', 'Chaque devis est détaillé, chaque écart de budget est expliqué avant d\'être engagé.'],
                ['award', 'Exigeant', 'Nous ne rendons un chantier que lorsque nous accepterions d\'y vivre nous-mêmes.'],
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

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Notre méthode</span>
            <h2>Quatre étapes, du premier café à la remise des clés</h2>
        </div>
        <ol class="steps">
            <?php foreach (config('site.process') as $step): ?>
                <li class="reveal">
                    <h3><?= e($step['title']) ?></h3>
                    <p class="muted"><?= e($step['text']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">L'équipe</span>
            <h2>Les personnes derrière vos projets</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach (config('site.team') as $member): ?>
                <article class="card text-center reveal" style="align-items:center">
                    <span class="avatar" style="width:4.5rem;height:4.5rem;font-size:1.6rem;margin-bottom:var(--space-4)" aria-hidden="true"><?= e(mb_substr($member['name'], 0, 1)) ?></span>
                    <h3><?= e($member['name']) ?></h3>
                    <p class="muted"><?= e($member['role']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?= component('components/cta-band', [
            'title' => 'Envie de travailler avec nous ?',
            'text' => 'Racontez-nous votre projet, nous revenons vers vous rapidement.',
            'primary' => ['label' => 'Nous contacter', 'href' => '/contact'],
        ]) ?>
    </div>
</section>
