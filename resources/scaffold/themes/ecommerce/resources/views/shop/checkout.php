<?php
/**
 * @var list<array{product: array<string, mixed>, quantity: int, line_total: int}> $lines
 * @var int $subtotal
 * @var int $shipping
 * @var array<string, mixed> $customer  utilisateur connecté, ou tableau vide
 */

use App\Support\Money;

layout('layouts.app', ['title' => 'Commande', 'active' => '/panier']);
?>
<?= component('components/page-hero', ['title' => 'Finaliser ma commande', 'lead' => 'Renseignez votre adresse de livraison.', 'crumbs' => [['label' => 'Panier', 'href' => '/panier']]]) ?>

<section class="section">
    <div class="container">
        <div class="checkout-layout">
            <form class="card" method="POST" action="/commande" novalidate>
                <?= csrf_field() ?>
                <h2>Livraison</h2>
                <?= component('components/field', ['name' => 'name', 'label' => 'Nom complet', 'autocomplete' => 'name', 'value' => $customer['name'] ?? '']) ?>
                <div class="field-row">
                    <?= component('components/field', ['name' => 'email', 'label' => 'Adresse email', 'type' => 'email', 'autocomplete' => 'email', 'value' => $customer['email'] ?? '']) ?>
                    <?= component('components/field', ['name' => 'phone', 'label' => 'Téléphone (facultatif)', 'type' => 'tel', 'autocomplete' => 'tel', 'required' => false]) ?>
                </div>
                <?= component('components/field', ['name' => 'address', 'label' => 'Adresse', 'autocomplete' => 'street-address']) ?>
                <div class="field-row">
                    <?= component('components/field', ['name' => 'postal_code', 'label' => 'Code postal', 'autocomplete' => 'postal-code']) ?>
                    <?= component('components/field', ['name' => 'city', 'label' => 'Ville', 'autocomplete' => 'address-level2']) ?>
                </div>

                <h2 style="margin-top: var(--space-6)">Paiement</h2>
                <?php if (config('site.demo_notice')): ?>
                    <p class="alert alert--info">
                        <strong>Mode démonstration :</strong> aucun paiement n'est demandé, la commande est simplement enregistrée.
                        Le branchement d'un prestataire de paiement reste à faire (voir le TODO de <code>CheckoutController</code>).
                    </p>
                <?php endif; ?>
                <button class="btn btn--primary btn--block btn--lg" type="submit"><?= component('components/icon', ['name' => 'lock']) ?> Confirmer la commande · <?= e(Money::format($subtotal + $shipping)) ?></button>
            </form>

            <aside class="card summary" aria-label="Votre commande">
                <h2>Votre commande</h2>
                <ul class="summary__items">
                    <?php foreach ($lines as $line): ?>
                        <li><span><?= (int) $line['quantity'] ?> × <?= e($line['product']['name']) ?></span><span><?= e(Money::format($line['line_total'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <dl class="summary__rows">
                    <div><dt>Sous-total</dt><dd><?= e(Money::format($subtotal)) ?></dd></div>
                    <div><dt>Livraison</dt><dd><?= $shipping === 0 ? 'Offerte' : e(Money::format($shipping)) ?></dd></div>
                    <div class="summary__total"><dt>Total</dt><dd><?= e(Money::format($subtotal + $shipping)) ?></dd></div>
                </dl>
            </aside>
        </div>
    </div>
</section>
