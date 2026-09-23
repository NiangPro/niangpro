<?php
/**
 * @var array<string, array{label: string, icon: string, text: string}> $categories
 * @var array<string, array<string, mixed>> $stats
 */
layout('admin.layouts.app', [
    'title' => 'Catégories',
    'active' => '/admin/categories',
    'subtitle' => 'Les rayons de la boutique. Ils se définissent dans config/site.php (clé « categories »).',
]);
?>
<div class="admin-grid admin-grid--stats">
    <?php foreach ($categories as $slug => $category): ?>
        <?php $row = $stats[$slug] ?? ['products' => 0, 'stock' => 0]; ?>
        <article class="admin-card">
            <header class="admin-card__head">
                <span class="admin-stat__icon" style="--tone: var(--a-accent-text); --tone-soft: var(--a-accent-soft)"><?= component('components/icon', ['name' => $category['icon'] ?? 'folder']) ?></span>
                <span class="admin-chip admin-mono"><?= e($slug) ?></span>
            </header>
            <h2 style="font-size: 1.05rem"><?= e($category['label']) ?></h2>
            <p class="admin-muted" style="margin: 0.35rem 0 1rem; font-size: 0.875rem"><?= e($category['text'] ?? '') ?></p>
            <p class="admin-total"><span class="admin-muted">Produits</span><strong><?= (int) $row['products'] ?></strong></p>
            <p class="admin-total"><span class="admin-muted">Unités en stock</span><strong><?= (int) $row['stock'] ?></strong></p>
            <div class="admin-form__actions" style="justify-content: flex-start; margin-top: 0.75rem">
                <a class="admin-btn admin-btn--ghost admin-btn--sm" href="/admin/produits?categorie=<?= e($slug) ?>">Voir les produits</a>
                <a class="admin-btn admin-btn--ghost admin-btn--sm" href="/boutique?categorie=<?= e($slug) ?>" target="_blank" rel="noopener">Sur la boutique</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
