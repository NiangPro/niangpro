<?php layout('layouts.app', ['title' => 'Services', 'active' => '/services', 'description' => "Conception intérieure, rénovation clé en main, agencement sur mesure, éclairage, home staging : les services d'Atelier Lumière."]); ?>

<?= component('components/page-hero', [
    'title' => 'Nos services',
    'lead' => 'Du simple conseil à la rénovation complète : choisissez ce dont vous avez besoin.',
    'crumbs' => [],
]) ?>

<section class="section">
    <div class="container">
        <div class="grid grid--2">
            <?php foreach (config('site.services') as $service): ?>
                <article class="card reveal">
                    <span class="card__icon"><?= component('components/icon', ['name' => $service['icon']]) ?></span>
                    <h2 style="font-size: var(--step-2)"><?= e($service['title']) ?></h2>
                    <p class="muted"><?= e($service['text']) ?></p>
                    <ul class="checklist" style="margin-top:auto">
                        <?php foreach ($service['includes'] as $item): ?>
                            <li><?= component('components/icon', ['name' => 'check']) ?> <?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Comment ça se passe</span>
            <h2>Une méthode claire, en quatre temps</h2>
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

<section class="section section--tight">
    <div class="container">
        <?= component('components/cta-band', [
            'title' => 'Un devis gratuit en 48 heures',
            'text' => 'Décrivez votre projet, nous vous proposons un premier rendez-vous et une fourchette de budget.',
            'primary' => ['label' => 'Demander un devis', 'href' => '/contact'],
        ]) ?>
    </div>
</section>
