<?php
/**
 * @var list<array{product: array<string, mixed>, quantity: int, line_total: int}> $lines
 * @var int $subtotal  centimes
 * @var int $shipping  centimes
 */

use App\Support\Money;

layout('layouts.app', ['title' => 'Panier', 'active' => '/panier']);

$freeFrom = (int) config('site.shipping.free_from_cents', 5000);
?>
<?= component('components/page-hero', ['title' => 'Votre panier', 'crumbs' => []]) ?>

<section class="section">
    <div class="container">
        <?php if (!$lines): ?>
            <div class="card text-center" style="align-items:center; max-width:32rem; margin-inline:auto">
                <span class="card__icon"><?= component('components/icon', ['name' => 'bag']) ?></span>
                <h2 style="font-size: var(--step-2)">Votre panier est vide</h2>
                <p class="muted">Parcourez la boutique pour y ajouter des articles.</p>
                <a class="btn btn--primary" href="/boutique">Voir la boutique</a>
            </div>
        <?php else: ?>
            <div class="checkout-layout">
                <div class="cart-lines">
                    <?php foreach ($lines as $line): ?>
                        <?php $product = $line['product']; ?>
                        <article class="cart-line card">
                            <a class="cart-line__media" href="/boutique/<?= e($product['slug']) ?>" tabindex="-1" aria-hidden="true">
                                <?= component('components/product-art', ['product' => $product]) ?>
                            </a>
                            <div class="cart-line__info">
                                <h2><a href="/boutique/<?= e($product['slug']) ?>"><?= e($product['name']) ?></a></h2>
                                <p class="muted"><?= e(Money::format((int) $product['price_cents'])) ?> l'unité</p>
                                <form class="cluster" method="POST" action="/panier/<?= (int) $product['id'] ?>/modifier">
                                    <?= csrf_field() ?>
                                    <label class="sr-only" for="qty-<?= (int) $product['id'] ?>">Quantité de <?= e($product['name']) ?></label>
                                    <div class="qty" data-qty>
                                        <button type="button" data-qty-step="-1" aria-label="Diminuer la quantité"><?= component('components/icon', ['name' => 'minus']) ?></button>
                                        <input id="qty-<?= (int) $product['id'] ?>" name="quantity" type="number" value="<?= (int) $line['quantity'] ?>" min="0" max="<?= min(20, (int) $product['stock']) ?>" inputmode="numeric">
                                        <button type="button" data-qty-step="1" aria-label="Augmenter la quantité"><?= component('components/icon', ['name' => 'plus']) ?></button>
                                    </div>
                                    <button class="btn btn--ghost btn--sm" type="submit">Mettre à jour</button>
                                </form>
                            </div>
                            <div class="cart-line__side">
                                <strong class="price__now"><?= e(Money::format($line['line_total'])) ?></strong>
                                <form method="POST" action="/panier/<?= (int) $product['id'] ?>/retirer">
                                    <?= csrf_field() ?>
                                    <button class="link-button" type="submit" aria-label="Retirer <?= e($product['name']) ?> du panier"><?= component('components/icon', ['name' => 'trash']) ?> Retirer</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="card summary" aria-label="Récapitulatif">
                    <h2>Récapitulatif</h2>
                    <dl class="summary__rows">
                        <div><dt>Sous-total</dt><dd><?= e(Money::format($subtotal)) ?></dd></div>
                        <div><dt>Livraison</dt><dd><?= $shipping === 0 ? 'Offerte' : e(Money::format($shipping)) ?></dd></div>
                        <div class="summary__total"><dt>Total</dt><dd><?= e(Money::format($subtotal + $shipping)) ?></dd></div>
                    </dl>
                    <?php if ($shipping > 0): ?>
                        <p class="muted" style="font-size: var(--step--1)">Plus que <?= e(Money::format($freeFrom - $subtotal)) ?> pour la livraison offerte.</p>
                    <?php endif; ?>
                    <a class="btn btn--primary btn--block btn--lg" href="/commande">Passer commande</a>
                    <a class="link-arrow" style="justify-content:center; margin-top: var(--space-4)" href="/boutique">Continuer mes achats</a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
