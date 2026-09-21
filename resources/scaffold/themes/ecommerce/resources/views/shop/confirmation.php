<?php
/**
 * @var array<string, mixed> $order
 * @var list<array<string, mixed>> $items
 */

use App\Models\Order;
use App\Support\Money;

layout('layouts.app', ['title' => 'Commande enregistrée', 'active' => '/panier']);
?>
<section class="section">
    <div class="container container--narrow">
        <div class="text-center" style="margin-bottom: var(--space-6)">
            <span class="card__icon" style="margin-inline:auto"><?= component('components/icon', ['name' => 'check']) ?></span>
            <h1>Merci pour votre commande !</h1>
            <p class="lead" style="margin-inline:auto">Référence <strong><?= e($order['reference']) ?></strong> — un récapitulatif sera envoyé à <?= e($order['email']) ?>.</p>
        </div>

        <?php if (config('site.demo_notice')): ?>
            <p class="alert alert--info"><strong>Mode démonstration :</strong> aucun paiement n'a été demandé et aucun email n'est réellement envoyé. La commande est enregistrée avec le statut « <?= e(Order::statusLabel($order['status'])) ?> ».</p>
        <?php endif; ?>

        <div class="card">
            <h2>Détail</h2>
            <ul class="summary__items">
                <?php foreach ($items as $item): ?>
                    <li><span><?= (int) $item['quantity'] ?> × <?= e($item['name']) ?></span><span><?= e(Money::format((int) $item['quantity'] * (int) $item['unit_price_cents'])) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <dl class="summary__rows">
                <div><dt>Sous-total</dt><dd><?= e(Money::format((int) $order['subtotal_cents'])) ?></dd></div>
                <div><dt>Livraison</dt><dd><?= (int) $order['shipping_cents'] === 0 ? 'Offerte' : e(Money::format((int) $order['shipping_cents'])) ?></dd></div>
                <div class="summary__total"><dt>Total</dt><dd><?= e(Money::format((int) $order['total_cents'])) ?></dd></div>
            </dl>
            <hr>
            <h3>Adresse de livraison</h3>
            <p class="muted"><?= e($order['name']) ?><br><?= e($order['address']) ?><br><?= e($order['postal_code']) ?> <?= e($order['city']) ?></p>
        </div>

        <p class="text-center" style="margin-top: var(--space-6)"><a class="btn btn--primary" href="/boutique">Continuer mes achats</a></p>
    </div>
</section>
