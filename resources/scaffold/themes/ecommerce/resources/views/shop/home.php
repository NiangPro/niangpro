<?php
/**
 * @var list<array<string, mixed>> $featured  produits mis en avant
 * @var list<array<string, mixed>> $onSale    produits en promotion
 */
layout('layouts.app', ['active' => '/']);
?>
<section class="hero hero--centered text-center">
    <div class="container">
        <span class="badge">Collection d'automne · −20 % sur une sélection</span>
        <h1 style="margin-top: var(--space-4)">Des objets simples, faits pour <em>durer</em></h1>
        <p class="lead" style="margin-inline:auto">Bougies, linge de maison, vaisselle et papeterie : des petites séries fabriquées par des artisans français, livrées chez vous en 48 heures.</p>
        <div class="hero__actions" style="justify-content:center">
            <a class="btn btn--primary btn--lg" href="/boutique">Découvrir la boutique</a>
            <a class="btn btn--ghost btn--lg" href="/boutique?tri=prix-asc">Les meilleurs prix</a>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <ul class="assurances">
            <?php foreach (config('site.assurances') as $item): ?>
                <li>
                    <span class="card__icon" style="margin:0"><?= component('components/icon', ['name' => $item['icon']]) ?></span>
                    <div><strong><?= e($item['title']) ?></strong><span class="muted"><?= e($item['text']) ?></span></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Catégories</span>
            <h2>Parcourir par univers</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach (config('site.categories') as $slug => $category): ?>
                <a class="card category-tile reveal" href="/boutique?categorie=<?= e($slug) ?>">
                    <span class="card__icon"><?= component('components/icon', ['name' => $category['icon']]) ?></span>
                    <h3><?= e($category['label']) ?></h3>
                    <p class="muted"><?= e($category['text']) ?></p>
                    <span class="link-arrow" style="margin-top:auto">Voir les produits <?= component('components/icon', ['name' => 'arrow-right']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Produits phares</span>
            <h2>Les préférés de nos clients</h2>
        </div>
        <?php if (!$featured): ?>
            <p class="text-center muted">Le catalogue est vide : lancez <code>./bin/niang db:seed</code> pour charger les produits de démonstration.</p>
        <?php endif; ?>
        <div class="grid grid--4">
            <?php foreach ($featured as $product): ?>
                <?= component('components/product-card', ['product' => $product]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($onSale): ?>
<section class="section">
    <div class="container">
        <div class="promo-band">
            <div>
                <span class="badge">Promotions</span>
                <h2 style="margin-top: var(--space-3)">Les bonnes affaires de la saison</h2>
                <p>Des prix réduits sur une sélection d'articles, dans la limite des stocks disponibles.</p>
                <a class="btn btn--gold" href="/boutique?tri=prix-asc">Voir toutes les offres</a>
            </div>
            <div class="grid grid--3">
                <?php foreach ($onSale as $product): ?>
                    <?= component('components/product-card', ['product' => $product]) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
