<?php
/**
 * @var array<string, mixed> $product
 * @var list<array<string, mixed>> $related  produits de la même catégorie
 */

use App\Models\Product;
use App\Support\Money;

layout('layouts.app', [
    'title' => $product['name'],
    'description' => mb_substr((string) $product['description'], 0, 155),
    'active' => '/boutique',
]);

$category = config('site.categories.' . $product['category'], []);
$soldOut = (int) $product['stock'] < 1;
$maxQuantity = min(20, max(1, (int) $product['stock']));
?>
<section class="section section--tight">
    <div class="container">
        <ol class="breadcrumb" aria-label="Fil d'Ariane">
            <li><a href="/">Accueil</a></li>
            <li><a href="/boutique">Boutique</a></li>
            <li><a href="/boutique?categorie=<?= e($product['category']) ?>"><?= e($category['label'] ?? $product['category']) ?></a></li>
            <li aria-current="page"><?= e($product['name']) ?></li>
        </ol>

        <div class="split" style="align-items:start">
            <div class="gallery" data-gallery>
                <div class="gallery__main">
                    <?php foreach ([0, 1, 2] as $variant): ?>
                        <div data-slide="<?= $variant ?>"<?= $variant > 0 ? ' hidden' : '' ?>>
                            <?= component('components/product-art', ['product' => $product, 'variant' => $variant, 'ratio' => '1 / 1']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="gallery__thumbs" role="group" aria-label="Autres vues du produit">
                    <?php foreach ([0, 1, 2] as $variant): ?>
                        <button type="button" data-thumb="<?= $variant ?>" aria-label="Voir la vue <?= $variant + 1 ?>" aria-pressed="<?= $variant === 0 ? 'true' : 'false' ?>">
                            <?= component('components/product-art', ['product' => $product, 'variant' => $variant, 'ratio' => '1 / 1']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="stack">
                <span class="eyebrow" style="margin:0"><?= e($category['label'] ?? $product['category']) ?></span>
                <h1 style="font-size: var(--step-3); margin:0"><?= e($product['name']) ?></h1>
                <p class="price price--lg">
                    <span class="price__now"><?= e(Money::format((int) $product['price_cents'])) ?></span>
                    <?php if (Product::isOnSale($product)): ?>
                        <s class="price__old"><span class="sr-only">Ancien prix : </span><?= e(Money::format((int) $product['old_price_cents'])) ?></s>
                        <span class="badge">−<?= Product::discountPercent($product) ?> %</span>
                    <?php endif; ?>
                </p>
                <p><?= e($product['description']) ?></p>

                <p class="stock<?= $soldOut ? ' stock--out' : '' ?>">
                    <?php if ($soldOut): ?>
                        Épuisé pour le moment
                    <?php elseif ((int) $product['stock'] <= 5): ?>
                        Plus que <?= (int) $product['stock'] ?> en stock
                    <?php else: ?>
                        En stock, expédié sous 48 h
                    <?php endif; ?>
                </p>

                <?php if (!$soldOut): ?>
                    <form method="POST" action="/panier/ajouter" class="cluster" style="align-items:flex-end">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <div class="field" style="margin:0">
                            <label for="quantity">Quantité</label>
                            <div class="qty" data-qty>
                                <button type="button" data-qty-step="-1" aria-label="Diminuer la quantité"><?= component('components/icon', ['name' => 'minus']) ?></button>
                                <input id="quantity" name="quantity" type="number" value="1" min="1" max="<?= $maxQuantity ?>" inputmode="numeric">
                                <button type="button" data-qty-step="1" aria-label="Augmenter la quantité"><?= component('components/icon', ['name' => 'plus']) ?></button>
                            </div>
                        </div>
                        <button class="btn btn--primary btn--lg" type="submit"><?= component('components/icon', ['name' => 'bag']) ?> Ajouter au panier</button>
                    </form>
                <?php endif; ?>

                <ul class="checklist muted" style="margin-top: var(--space-4)">
                    <li><?= component('components/icon', ['name' => 'truck']) ?> Livraison offerte dès 50 €</li>
                    <li><?= component('components/icon', ['name' => 'refresh']) ?> Retours gratuits sous 30 jours</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php if ($related): ?>
<section class="section section--alt">
    <div class="container">
        <div class="section-head"><h2>Vous aimerez aussi</h2></div>
        <div class="grid grid--4">
            <?php foreach ($related as $item): ?>
                <?= component('components/product-card', ['product' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
