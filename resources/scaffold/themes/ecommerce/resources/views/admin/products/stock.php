<?php
/**
 * @var list<array<string, mixed>> $products
 * @var bool $lowOnly
 * @var int $units
 * @var int $value
 * @var int $outOfStock
 * @var int $low
 */

use App\Models\Product;
use App\Support\AdminFormat;
use App\Support\Money;

layout('admin.layouts.app', [
    'title' => 'Stock',
    'active' => '/admin/stock',
    'subtitle' => 'Ajustez les quantités disponibles ; une commande décrémente le stock automatiquement.',
]);
?>
<div class="admin-grid admin-grid--stats">
    <?= component('admin/components/stat', ['label' => 'Unités en stock', 'value' => AdminFormat::number($units), 'icon' => 'layers']) ?>
    <?= component('admin/components/stat', ['label' => 'Valeur du stock', 'value' => Money::format($value), 'icon' => 'credit-card', 'tone' => 'violet', 'hint' => 'au prix de vente']) ?>
    <?= component('admin/components/stat', ['label' => 'Stock faible', 'value' => (string) $low, 'icon' => 'alert', 'tone' => 'amber', 'hint' => Product::LOW_STOCK . ' unités ou moins']) ?>
    <?= component('admin/components/stat', ['label' => 'Épuisés', 'value' => (string) $outOfStock, 'icon' => 'package', 'tone' => 'rose']) ?>
</div>

<section class="admin-card admin-card--flush">
    <div class="admin-toolbar">
        <nav class="admin-tabs" aria-label="Filtrer le stock">
            <a href="/admin/stock"<?= !$lowOnly ? ' aria-current="page"' : '' ?>>Tous les produits</a>
            <a href="/admin/stock?filtre=faible"<?= $lowOnly ? ' aria-current="page"' : '' ?>>Stock faible<small><?= $low ?></small></a>
        </nav>
    </div>
    <?php if (!$products): ?>
        <?= component('admin/components/empty', ['title' => 'Rien à signaler', 'text' => 'Aucun produit en stock faible.', 'icon' => 'check']) ?>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Produit</th><th class="num">Prix</th><th>Niveau</th><th class="num">Ajuster</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php $stock = (int) $product['stock']; ?>
                        <tr>
                            <td><div class="admin-cell"><span class="admin-thumb"><?= e(mb_strtoupper(mb_substr($product['name'], 0, 1))) ?></span><a class="admin-table__main" href="/admin/produits/<?= (int) $product['id'] ?>/modifier"><?= e($product['name']) ?></a></div></td>
                            <td class="num"><?= e(Money::format((int) $product['price_cents'])) ?></td>
                            <td><span class="admin-badge admin-badge--<?= $stock === 0 ? 'rose' : ($stock <= Product::LOW_STOCK ? 'amber' : 'green') ?>"><?= $stock === 0 ? 'Épuisé' : ($stock <= Product::LOW_STOCK ? 'Faible' : 'Disponible') ?></span></td>
                            <td class="num">
                                <form class="admin-stock-form" method="POST" action="/admin/stock/<?= (int) $product['id'] ?>">
                                    <?= csrf_field() ?>
                                    <?php if ($lowOnly): ?><input type="hidden" name="filtre" value="faible"><?php endif; ?>
                                    <label class="visually-hidden" for="stock-<?= (int) $product['id'] ?>">Stock de <?= e($product['name']) ?></label>
                                    <input class="admin-input" id="stock-<?= (int) $product['id'] ?>" type="number" name="stock" min="0" step="1" value="<?= $stock ?>">
                                    <button class="admin-btn admin-btn--ghost admin-btn--sm" type="submit">OK</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
