<?php
/**
 * @var \Niang\Core\Database\Paginator $products
 * @var string $category
 * @var string $search
 * @var array<string, string> $categories
 */

use App\Models\Product;
use App\Support\Money;

layout('admin.layouts.app', [
    'title' => 'Produits',
    'active' => '/admin/produits',
    'subtitle' => 'Le catalogue de la boutique : prix, stock, mise en avant.',
    'actions' => '<a class="admin-btn admin-btn--primary" href="/admin/produits/nouveau">' . component('components/icon', ['name' => 'plus']) . 'Nouveau produit</a>',
]);
?>
<section class="admin-card admin-card--flush">
    <form class="admin-toolbar" method="GET" action="/admin/produits" role="search">
        <label class="admin-search">
            <span class="visually-hidden">Rechercher un produit</span>
            <?= component('components/icon', ['name' => 'search']) ?>
            <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="Nom ou adresse du produit…">
        </label>
        <label class="visually-hidden" for="filter-category">Catégorie</label>
        <select class="admin-input" id="filter-category" name="categorie" data-autosubmit>
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $slug => $label): ?>
                <option value="<?= e($slug) ?>"<?= $slug === $category ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="admin-btn admin-btn--ghost admin-btn--sm" type="submit">Filtrer</button>
    </form>

    <?php if (!$products->items): ?>
        <?= component('admin/components/empty', [
            'title' => 'Aucun produit trouvé',
            'text' => 'Ajoutez votre premier produit, ou modifiez votre recherche.',
            'icon' => 'tag',
            'action' => '<a class="admin-btn admin-btn--primary" href="/admin/produits/nouveau">Ajouter un produit</a>',
        ]) ?>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Produit</th><th>Catégorie</th><th class="num">Prix</th><th class="num">Stock</th><th>Visibilité</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    <?php foreach ($products->items as $product): ?>
                        <?php $stock = (int) $product['stock']; ?>
                        <tr>
                            <td>
                                <div class="admin-cell">
                                    <span class="admin-thumb"><?= e(mb_strtoupper(mb_substr($product['name'], 0, 1))) ?></span>
                                    <span><a class="admin-table__main" href="/admin/produits/<?= (int) $product['id'] ?>/modifier"><?= e($product['name']) ?></a><span class="admin-table__sub admin-mono">/boutique/<?= e($product['slug']) ?></span></span>
                                </div>
                            </td>
                            <td><?= e($categories[$product['category']] ?? $product['category']) ?></td>
                            <td class="num">
                                <strong><?= e(Money::format((int) $product['price_cents'])) ?></strong>
                                <?php if (Product::isOnSale($product)): ?><span class="admin-table__sub">−<?= Product::discountPercent($product) ?> % · <s><?= e(Money::format((int) $product['old_price_cents'])) ?></s></span><?php endif; ?>
                            </td>
                            <td class="num"><span class="admin-badge admin-badge--<?= $stock === 0 ? 'rose' : ($stock <= Product::LOW_STOCK ? 'amber' : 'green') ?>"><?= $stock ?></span></td>
                            <td><?= $product['featured'] ? '<span class="admin-badge admin-badge--violet">À la une</span>' : '<span class="admin-badge">Standard</span>' ?></td>
                            <td>
                                <div class="admin-table__actions">
                                    <a class="admin-btn admin-btn--ghost admin-btn--sm admin-btn--icon" href="/boutique/<?= e($product['slug']) ?>" target="_blank" rel="noopener" aria-label="Voir « <?= e($product['name']) ?> » sur la boutique" title="Voir sur la boutique"><?= component('components/icon', ['name' => 'eye']) ?></a>
                                    <a class="admin-btn admin-btn--ghost admin-btn--sm admin-btn--icon" href="/admin/produits/<?= (int) $product['id'] ?>/modifier" aria-label="Modifier « <?= e($product['name']) ?> »" title="Modifier"><?= component('components/icon', ['name' => 'pen']) ?></a>
                                    <form method="POST" action="/admin/produits/<?= (int) $product['id'] ?>/supprimer" data-confirm="Supprimer « <?= e($product['name']) ?> » du catalogue ?">
                                        <?= csrf_field() ?>
                                        <button class="admin-btn admin-btn--danger admin-btn--sm admin-btn--icon" type="submit" aria-label="Supprimer « <?= e($product['name']) ?> »" title="Supprimer"><?= component('components/icon', ['name' => 'trash']) ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?= component('admin/components/pagination', ['paginator' => $products, 'path' => '/admin/produits', 'query' => ['q' => $search, 'categorie' => $category]]) ?>
</section>
