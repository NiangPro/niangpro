<?php
layout('layouts.app');

$hero = config('site.hero');
$signup = config('site.signup_url');
?>
<section class="hero" id="accueil">
    <div class="container split">
        <div>
            <span class="badge"><?= e($hero['badge']) ?></span>
            <h1 style="margin-top: var(--space-4)"><?= e($hero['title']) ?></h1>
            <p class="lead"><?= e($hero['text']) ?></p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="<?= e($signup) ?>">Essayer gratuitement</a>
                <a class="btn btn--ghost btn--lg" href="#fonctionnement">Voir comment ça marche</a>
            </div>
            <p class="muted" style="margin-top: var(--space-5)"><?= component('components/icon', ['name' => 'users']) ?> <?= e($hero['trust']) ?></p>
        </div>

        <div class="mock" role="img" aria-label="Aperçu de la vue semaine de Boussole : cinq colonnes de tâches réparties du lundi au vendredi">
            <div class="mock__bar"><i></i><i></i><i></i></div>
            <div class="mock__grid" aria-hidden="true">
                <?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven'] as $i => $day): ?>
                    <div class="mock__col">
                        <b><?= e($day) ?></b>
                        <?php for ($n = 0; $n <= ($i * 2 + 1) % 3; $n++): ?>
                            <span class="mock__task mock__task--<?= ($i + $n) % 3 ?>"></span>
                        <?php endfor; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="section" id="fonctionnalites">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Fonctionnalités</span>
            <h2>Tout ce qu'il faut, rien de plus</h2>
            <p>Six fonctions bien pensées, pas un catalogue de cent options.</p>
        </div>
        <div class="grid grid--3">
            <?php foreach (config('site.features') as $feature): ?>
                <article class="card reveal">
                    <span class="card__icon"><?= component('components/icon', ['name' => $feature['icon']]) ?></span>
                    <h3><?= e($feature['title']) ?></h3>
                    <p class="muted"><?= e($feature['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--alt" id="fonctionnement">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Comment ça marche</span>
            <h2>Opérationnel en trois étapes</h2>
        </div>
        <ol class="how">
            <?php foreach (config('site.steps') as $step): ?>
                <li class="reveal">
                    <h3><?= e($step['title']) ?></h3>
                    <p class="muted"><?= e($step['text']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section class="section" id="temoignages">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Avis</span>
            <h2>Ils ont adopté Boussole</h2>
        </div>
        <?= component('components/testimonials', ['items' => config('site.testimonials')]) ?>
    </div>
</section>

<section class="section section--alt" id="tarifs">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Tarifs</span>
            <h2>Un prix simple, sans surprise</h2>
            <p>Sans engagement. Changez de plan ou arrêtez quand vous voulez.</p>
        </div>

        <div class="billing" data-billing hidden>
            <button type="button" data-billing-choice="monthly" aria-pressed="true">Mensuel</button>
            <button type="button" data-billing-choice="yearly" aria-pressed="false">Annuel <span class="badge">−20 %</span></button>
        </div>

        <div class="pricing">
            <?php foreach (config('site.plans') as $plan): ?>
                <article class="card plan<?= $plan['featured'] ? ' plan--featured' : '' ?> reveal">
                    <?php if ($plan['featured']): ?><span class="badge plan__badge">Le plus choisi</span><?php endif; ?>
                    <h3><?= e($plan['name']) ?></h3>
                    <p class="muted"><?= e($plan['audience']) ?></p>

                    <?php if ($plan['monthly'] === null): ?>
                        <p class="plan__price"><strong>Sur devis</strong></p>
                    <?php else: ?>
                        <p class="plan__price">
                            <strong data-price="monthly"><?= (int) $plan['monthly'] ?> €</strong>
                            <strong data-price="yearly" hidden><?= (int) $plan['yearly'] ?> €</strong>
                            <span class="muted"><?= e($plan['unit']) ?></span>
                        </p>
                    <?php endif; ?>

                    <ul class="checklist">
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li><?= component('components/icon', ['name' => 'check']) ?> <?= e($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="btn <?= $plan['featured'] ? 'btn--primary' : 'btn--ghost' ?> btn--block" style="margin-top:auto" href="<?= e($plan['monthly'] === null ? 'mailto:' . config('site.contact.email') : config('site.signup_url')) ?>"><?= e($plan['cta']) ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="faq">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">FAQ</span>
            <h2>Questions fréquentes</h2>
        </div>
        <?= component('components/faq', ['items' => config('site.faq'), 'openFirst' => true]) ?>
    </div>
</section>

<section class="section section--tight" id="essai">
    <div class="container">
        <?= component('components/cta-band', [
            'title' => 'Prêt à reprendre la main sur votre semaine ?',
            'text' => "Créez votre équipe en deux minutes. Aucune carte bancaire demandée.",
            'primary' => ['label' => 'Essayer gratuitement', 'href' => $signup],
            'secondary' => ['label' => 'Voir les tarifs', 'href' => '#tarifs'],
        ]) ?>
    </div>
</section>
