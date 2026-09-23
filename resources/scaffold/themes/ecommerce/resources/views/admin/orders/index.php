<?php
/**
 * @var \Niang\Core\Database\Paginator $orders
 * @var string $status
 * @var string $search
 * @var array<string, int> $counts
 */

use App\Models\Order;
use App\Support\AdminFormat;
use App\Support\Money;

layout('admin.layouts.app', [
    'title' => 'Commandes',
    'active' => '/admin/commandes',
    'subtitle' => 'Suivez et faites avancer chaque commande, du paiement à l\'expédition.',
]);
$tabUrl = static fn (string $s): string => '/admin/commandes' . ($s !== '' ? '?statut=' . $s : '');
?>
<section class="admin-card admin-card--flush">
    <form class="admin-toolbar" method="GET" action="/admin/commandes" role="search">
        <nav class="admin-tabs" aria-label="Filtrer par statut">
            <a href="<?= e($tabUrl('')) ?>"<?= $status === '' ? ' aria-current="page"' : '' ?>>Toutes<small><?= (int) $counts[''] ?></small></a>
            <?php foreach (Order::STATUSES as $s): ?>
                <a href="<?= e($tabUrl($s)) ?>"<?= $status === $s ? ' aria-current="page"' : '' ?>><?= e(Order::statusLabel($s)) ?><small><?= (int) ($counts[$s] ?? 0) ?></small></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($status !== ''): ?><input type="hidden" name="statut" value="<?= e($status) ?>"><?php endif; ?>
        <label class="admin-search">
            <span class="visually-hidden">Rechercher une commande</span>
            <?= component('components/icon', ['name' => 'search']) ?>
            <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="Référence, client, email…">
        </label>
    </form>

    <?php if (!$orders->items): ?>
        <?= component('admin/components/empty', ['title' => 'Aucune commande trouvée', 'text' => $search !== '' || $status !== '' ? 'Essayez un autre filtre ou une autre recherche.' : 'Les commandes passées sur la boutique apparaîtront ici.', 'icon' => 'package']) ?>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Référence</th><th>Client</th><th>Date</th><th>Statut</th><th class="num">Total</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    <?php foreach ($orders->items as $order): ?>
                        <tr>
                            <td><a class="admin-table__main admin-mono" href="/admin/commandes/<?= (int) $order['id'] ?>"><?= e($order['reference']) ?></a></td>
                            <td><?= e($order['name']) ?><span class="admin-table__sub"><?= e($order['email']) ?></span></td>
                            <td class="admin-nowrap"><?= e(AdminFormat::date($order['created_at'])) ?><span class="admin-table__sub"><?= e(AdminFormat::ago($order['created_at'])) ?></span></td>
                            <td><span class="admin-badge admin-badge--<?= e(Order::statusTone($order['status'])) ?>"><?= e(Order::statusLabel($order['status'])) ?></span></td>
                            <td class="num"><strong><?= e(Money::format((int) $order['total_cents'])) ?></strong></td>
                            <td><div class="admin-table__actions"><a class="admin-btn admin-btn--ghost admin-btn--sm" href="/admin/commandes/<?= (int) $order['id'] ?>">Détail</a></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?= component('admin/components/pagination', ['paginator' => $orders, 'path' => '/admin/commandes', 'query' => ['statut' => $status, 'q' => $search]]) ?>
</section>
