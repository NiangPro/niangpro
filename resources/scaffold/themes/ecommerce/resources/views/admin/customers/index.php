<?php
/**
 * @var \Niang\Core\Database\Paginator $customers
 * @var string $search
 */

use App\Support\AdminFormat;
use App\Support\Money;

layout('admin.layouts.app', [
    'title' => 'Clients',
    'active' => '/admin/clients',
    'subtitle' => 'Les comptes créés sur la boutique et leur historique d\'achat.',
]);
?>
<section class="admin-card admin-card--flush">
    <form class="admin-toolbar" method="GET" action="/admin/clients" role="search">
        <label class="admin-search">
            <span class="visually-hidden">Rechercher un client</span>
            <?= component('components/icon', ['name' => 'search']) ?>
            <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="Nom ou email…">
        </label>
    </form>

    <?php if (!$customers->items): ?>
        <?= component('admin/components/empty', ['title' => 'Aucun client', 'text' => $search !== '' ? 'Aucun compte ne correspond à cette recherche.' : 'Les clients qui créent un compte apparaîtront ici.', 'icon' => 'users']) ?>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Client</th><th>Inscrit</th><th class="num">Commandes</th><th class="num">Total dépensé</th><th>Dernier achat</th></tr></thead>
                <tbody>
                    <?php foreach ($customers->items as $customer): ?>
                        <tr>
                            <td>
                                <div class="admin-cell">
                                    <span class="admin-avatar"><?= e(mb_strtoupper(mb_substr($customer['name'], 0, 1))) ?></span>
                                    <span><span class="admin-table__main"><?= e($customer['name']) ?></span><a class="admin-table__sub" href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a></span>
                                </div>
                            </td>
                            <td class="admin-nowrap"><?= e(AdminFormat::date($customer['created_at'])) ?></td>
                            <td class="num"><?= (int) $customer['orders_count'] ?></td>
                            <td class="num"><strong><?= e(Money::format((int) $customer['spent_cents'])) ?></strong></td>
                            <td class="admin-nowrap"><?= $customer['last_order_at'] ? e(AdminFormat::ago($customer['last_order_at'])) : '<span class="admin-muted">—</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?= component('admin/components/pagination', ['paginator' => $customers, 'path' => '/admin/clients', 'query' => ['q' => $search]]) ?>
</section>
