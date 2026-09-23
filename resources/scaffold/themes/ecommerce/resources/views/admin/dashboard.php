<?php
/**
 * @var array<string, int|float|null> $stats
 * @var list<array{label: string, value: int, display: string}> $chart
 * @var list<array<string, mixed>> $statuses
 * @var list<array<string, mixed>> $latest
 * @var list<array<string, mixed>> $topProducts
 * @var list<array<string, mixed>> $lowStock
 */

use App\Models\Order;
use App\Models\Product;
use App\Support\AdminFormat;
use App\Support\Money;
use Niang\Core\Auth;

layout('admin.layouts.app', [
    'title' => 'Tableau de bord',
    'active' => '/admin',
    'subtitle' => "L'activité de la boutique sur les 30 derniers jours.",
    'actions' => '<a class="admin-btn admin-btn--ghost" href="/admin/commandes">' . component('components/icon', ['name' => 'package']) . 'Commandes</a>'
        . '<a class="admin-btn admin-btn--primary" href="/admin/produits/nouveau">' . component('components/icon', ['name' => 'plus']) . 'Nouveau produit</a>',
]);
$firstName = explode(' ', (string) (Auth::user()['name'] ?? ''))[0];
?>
<section class="admin-hero">
    <div>
        <h2>Bonjour <?= e($firstName) ?>, voici votre boutique aujourd'hui.</h2>
        <p>
            <?php if ($stats['pending'] > 0): ?>
                <?= (int) $stats['pending'] ?> commande<?= $stats['pending'] > 1 ? 's attendent' : ' attend' ?> un paiement ou un traitement.
            <?php else: ?>
                Aucune commande en attente : tout est à jour.
            <?php endif; ?>
            <?= count($lowStock) ? count($lowStock) . ' produit' . (count($lowStock) > 1 ? 's' : '') . ' en stock faible.' : '' ?>
        </p>
    </div>
    <a class="admin-btn admin-btn--ghost" href="/admin/commandes?statut=pending">Traiter les commandes <?= component('components/icon', ['name' => 'arrow-right']) ?></a>
</section>

<div class="admin-grid admin-grid--stats">
    <?= component('admin/components/stat', ['label' => "Chiffre d'affaires (30 j)", 'value' => Money::format((int) $stats['revenue']), 'icon' => 'trending-up', 'trend' => $stats['revenueTrend'], 'hint' => $stats['revenueTrend'] !== null ? 'vs 30 jours précédents' : 'hors commandes annulées']) ?>
    <?= component('admin/components/stat', ['label' => 'Commandes (30 j)', 'value' => AdminFormat::number((int) $stats['orders']), 'icon' => 'package', 'tone' => 'violet', 'hint' => $stats['pending'] . ' en attente']) ?>
    <?= component('admin/components/stat', ['label' => 'Panier moyen', 'value' => Money::format((int) $stats['average']), 'icon' => 'cart', 'tone' => 'amber', 'hint' => 'hors commandes annulées']) ?>
    <?= component('admin/components/stat', ['label' => 'Clients inscrits', 'value' => AdminFormat::number((int) $stats['customers']), 'icon' => 'users', 'tone' => 'sky', 'hint' => $stats['products'] . ' produits au catalogue']) ?>
</div>

<div class="admin-grid admin-grid--main">
    <section class="admin-card">
        <header class="admin-card__head">
            <div>
                <h2>Ventes des 14 derniers jours</h2>
                <p>Montant total des commandes, hors annulations.</p>
            </div>
            <span class="admin-chip"><?= e(Money::format(array_sum(array_column($chart, 'value')))) ?></span>
        </header>
        <?= component('admin/components/area-chart', ['points' => $chart, 'caption' => 'Ventes par jour sur les 14 derniers jours']) ?>
    </section>

    <section class="admin-card">
        <header class="admin-card__head">
            <h2>Commandes par statut</h2>
            <a class="admin-card__link" href="/admin/commandes">Tout voir</a>
        </header>
        <?= component('admin/components/bar-list', ['rows' => $statuses, 'empty' => 'Aucune commande pour le moment.']) ?>
    </section>
</div>

<div class="admin-grid admin-grid--main">
    <section class="admin-card admin-card--flush">
        <header class="admin-card__head">
            <h2>Dernières commandes</h2>
            <a class="admin-card__link" href="/admin/commandes">Toutes les commandes</a>
        </header>
        <?php if (!$latest): ?>
            <?= component('admin/components/empty', ['title' => 'Aucune commande', 'text' => 'Les commandes passées sur la boutique apparaîtront ici.', 'icon' => 'package']) ?>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Commande</th><th>Client</th><th>Statut</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($latest as $order): ?>
                            <tr>
                                <td><a class="admin-table__main admin-mono" href="/admin/commandes/<?= (int) $order['id'] ?>"><?= e($order['reference']) ?></a><span class="admin-table__sub"><?= e(AdminFormat::ago($order['created_at'])) ?></span></td>
                                <td><?= e($order['name']) ?><span class="admin-table__sub"><?= e($order['city']) ?></span></td>
                                <td><span class="admin-badge admin-badge--<?= e(Order::statusTone($order['status'])) ?>"><?= e(Order::statusLabel($order['status'])) ?></span></td>
                                <td class="num"><strong><?= e(Money::format((int) $order['total_cents'])) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <div class="admin-stack">
        <section class="admin-card">
            <header class="admin-card__head"><h2>Meilleures ventes</h2></header>
            <?= component('admin/components/bar-list', [
                'rows' => array_map(static fn (array $p): array => ['label' => $p['name'], 'value' => (int) $p['sold'], 'display' => $p['sold'] . ' vendu' . ($p['sold'] > 1 ? 's' : '')], $topProducts),
                'empty' => 'Pas encore de ventes.',
            ]) ?>
        </section>

        <section class="admin-card">
            <header class="admin-card__head">
                <h2>Stock faible</h2>
                <a class="admin-card__link" href="/admin/stock?filtre=faible">Gérer le stock</a>
            </header>
            <?php if (!$lowStock): ?>
                <p class="admin-muted">Tous les produits ont plus de <?= Product::LOW_STOCK ?> unités en stock.</p>
            <?php else: ?>
                <ul class="admin-list">
                    <?php foreach ($lowStock as $product): ?>
                        <li>
                            <span class="admin-thumb"><?= e(mb_strtoupper(mb_substr($product['name'], 0, 1))) ?></span>
                            <span class="admin-list__body"><strong><?= e($product['name']) ?></strong><small><?= e((string) config('site.categories.' . $product['category'] . '.label', $product['category'])) ?></small></span>
                            <span class="admin-badge admin-badge--<?= (int) $product['stock'] === 0 ? 'rose' : 'amber' ?>"><?= (int) $product['stock'] === 0 ? 'Épuisé' : (int) $product['stock'] . ' restant' . ((int) $product['stock'] > 1 ? 's' : '') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
