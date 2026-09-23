<?php
/**
 * @var array<string, array{label: string, icon: string, text: string}> $categories
 * @var array<string, array<string, mixed>> $stats
 */

use App\Support\AdminFormat;

layout('admin.layouts.app', [
    'title' => 'Catégories',
    'active' => '/admin/categories',
    'subtitle' => 'Les rubriques du blog. Elles se définissent dans config/site.php (clé « categories »).',
]);
?>
<div class="admin-grid admin-grid--stats">
    <?php foreach ($categories as $slug => $category): ?>
        <?php $row = $stats[$slug] ?? ['posts' => 0, 'last_at' => null]; ?>
        <article class="admin-card">
            <header class="admin-card__head">
                <span class="admin-stat__icon" style="--tone: var(--a-accent-text); --tone-soft: var(--a-accent-soft)"><?= component('components/icon', ['name' => $category['icon'] ?? 'folder']) ?></span>
                <span class="admin-chip admin-mono"><?= e($slug) ?></span>
            </header>
            <h2 style="font-size: 1.05rem"><?= e($category['label']) ?></h2>
            <p class="admin-muted" style="margin: 0.35rem 0 1rem; font-size: 0.875rem"><?= e($category['text'] ?? '') ?></p>
            <p class="admin-total"><span class="admin-muted">Articles</span><strong><?= (int) $row['posts'] ?></strong></p>
            <p class="admin-total"><span class="admin-muted">Dernier article</span><strong><?= e(AdminFormat::ago($row['last_at'])) ?></strong></p>
            <div class="admin-form__actions" style="justify-content: flex-start; margin-top: 0.75rem">
                <a class="admin-btn admin-btn--ghost admin-btn--sm" href="/admin/articles?categorie=<?= e($slug) ?>">Voir les articles</a>
                <a class="admin-btn admin-btn--ghost admin-btn--sm" href="/categories/<?= e($slug) ?>" target="_blank" rel="noopener">Sur le blog</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
