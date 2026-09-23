<?php
/**
 * @var array<string, mixed> $order
 * @var list<array<string, mixed>> $items
 */

use App\Models\Order;
use App\Support\AdminFormat;
use App\Support\Money;

layout('admin.layouts.app', [
    'title' => 'Commande ' . $order['reference'],
    'active' => '/admin/commandes',
    'subtitle' => 'Passée le ' . AdminFormat::date($order['created_at']) . ' par ' . $order['name'] . '.',
    'actions' => '<a class="admin-btn admin-btn--ghost" href="/admin/commandes">' . component('components/icon', ['name' => 'arrow-left']) . 'Toutes les commandes</a>',
]);
?>
<div class="admin-grid admin-grid--main">
    <section class="admin-card admin-card--flush">
        <header class="admin-card__head">
            <h2>Articles</h2>
            <span class="admin-badge admin-badge--<?= e(Order::statusTone($order['status'])) ?>"><?= e(Order::statusLabel($order['status'])) ?></span>
        </header>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Produit</th><th class="num">Prix unitaire</th><th class="num">Quantité</th><th class="num">Total</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><div class="admin-cell"><span class="admin-thumb"><?= e(mb_strtoupper(mb_substr($item['name'], 0, 1))) ?></span><span class="admin-table__main"><?= e($item['name']) ?></span></div></td>
                            <td class="num"><?= e(Money::format((int) $item['unit_price_cents'])) ?></td>
                            <td class="num">× <?= (int) $item['quantity'] ?></td>
                            <td class="num"><strong><?= e(Money::format((int) $item['unit_price_cents'] * (int) $item['quantity'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="padding: 1rem 1.25rem 1.25rem; max-width: 22rem; margin-left: auto">
            <p class="admin-total"><span class="admin-muted">Sous-total</span><span><?= e(Money::format((int) $order['subtotal_cents'])) ?></span></p>
            <p class="admin-total"><span class="admin-muted">Livraison</span><span><?= (int) $order['shipping_cents'] === 0 ? 'Offerte' : e(Money::format((int) $order['shipping_cents'])) ?></span></p>
            <p class="admin-total admin-total--grand"><span>Total</span><span><?= e(Money::format((int) $order['total_cents'])) ?></span></p>
        </div>
    </section>

    <div class="admin-stack">
        <section class="admin-card">
            <header class="admin-card__head"><h2>Statut</h2></header>
            <form class="admin-form" method="POST" action="/admin/commandes/<?= (int) $order['id'] ?>/statut">
                <?= csrf_field() ?>
                <?= component('admin/components/field', [
                    'name' => 'status',
                    'label' => 'Faire passer la commande à',
                    'type' => 'select',
                    'value' => $order['status'],
                    'options' => array_combine(Order::STATUSES, array_map([Order::class, 'statusLabel'], Order::STATUSES)),
                ]) ?>
                <div class="admin-form__actions"><button class="admin-btn admin-btn--primary" type="submit">Mettre à jour</button></div>
            </form>
        </section>

        <section class="admin-card">
            <header class="admin-card__head"><h2>Client et livraison</h2></header>
            <dl class="admin-dl">
                <div><dt>Nom</dt><dd><?= e($order['name']) ?></dd></div>
                <div><dt>Email</dt><dd><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd></div>
                <?php if (!empty($order['phone'])): ?><div><dt>Téléphone</dt><dd><?= e($order['phone']) ?></dd></div><?php endif; ?>
                <div><dt>Adresse</dt><dd><?= e($order['address']) ?><br><?= e($order['postal_code']) ?> <?= e($order['city']) ?></dd></div>
                <div><dt>Compte</dt><dd><?= $order['user_id'] ? 'Client inscrit' : 'Commande sans compte' ?></dd></div>
            </dl>
        </section>
    </div>
</div>
