<?php
/**
 * Carte produit : visuel, nom, prix (avec ancien prix barré en promotion), ajout direct au panier.
 *
 * @var array<string, mixed> $product
 */

use App\Models\Product;
use App\Support\Money;

$category = config('site.categories.' . $product['category'], []);
$url = '/boutique/' . $product['slug'];
$soldOut = (int) $product['stock'] < 1;
?>
<article class="card card--flush product-card reveal">
    <a class="product-card__media" href="<?= e($url) ?>" tabindex="-1" aria-hidden="true">
        <?= component('components/product-art', ['product' => $product]) ?>
        <?php if ($soldOut): ?>
            <span class="badge product-card__badge product-card__badge--soldout">Épuisé</span>
        <?php elseif (Product::isOnSale($product)): ?>
            <span class="badge product-card__badge">−<?= Product::discountPercent($product) ?> %</span>
        <?php endif; ?>
    </a>
    <div class="card__body">
        <p class="product-card__category"><?= e($category['label'] ?? $product['category']) ?></p>
        <h3><a href="<?= e($url) ?>"><?= e($product['name']) ?></a></h3>
        <p class="price">
            <span class="price__now"><?= e(Money::format((int) $product['price_cents'])) ?></span>
            <?php if (Product::isOnSale($product)): ?>
                <s class="price__old"><span class="sr-only">Ancien prix : </span><?= e(Money::format((int) $product['old_price_cents'])) ?></s>
            <?php endif; ?>
        </p>
        <?php if ($soldOut): ?>
            <button class="btn btn--ghost btn--block btn--sm" type="button" disabled>Épuisé</button>
        <?php else: ?>
            <form method="POST" action="/panier/ajouter" style="margin-top:auto">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <button class="btn btn--primary btn--block btn--sm" type="submit">Ajouter au panier</button>
            </form>
        <?php endif; ?>
    </div>
</article>
