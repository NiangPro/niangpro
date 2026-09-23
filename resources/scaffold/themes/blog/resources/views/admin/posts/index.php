<?php
/**
 * @var \Niang\Core\Database\Paginator $posts
 * @var array<int, list<string>> $tags
 * @var string $category
 * @var string $search
 * @var array<string, string> $categories
 */

use App\Support\AdminFormat;

layout('admin.layouts.app', [
    'title' => 'Articles',
    'active' => '/admin/articles',
    'subtitle' => 'Rédigez, modifiez et organisez les articles du blog.',
    'actions' => '<a class="admin-btn admin-btn--primary" href="/admin/articles/nouveau">' . component('components/icon', ['name' => 'plus']) . 'Nouvel article</a>',
]);
?>
<section class="admin-card admin-card--flush">
    <form class="admin-toolbar" method="GET" action="/admin/articles" role="search">
        <label class="admin-search">
            <span class="visually-hidden">Rechercher un article</span>
            <?= component('components/icon', ['name' => 'search']) ?>
            <input class="admin-input" type="search" name="q" value="<?= e($search) ?>" placeholder="Titre, auteur…">
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

    <?php if (!$posts->items): ?>
        <?= component('admin/components/empty', [
            'title' => 'Aucun article trouvé',
            'text' => 'Écrivez un nouvel article, ou modifiez votre recherche.',
            'icon' => 'pen',
            'action' => '<a class="admin-btn admin-btn--primary" href="/admin/articles/nouveau">Nouvel article</a>',
        ]) ?>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Article</th><th>Catégorie</th><th>Tags</th><th>Publié</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    <?php foreach ($posts->items as $post): ?>
                        <tr>
                            <td>
                                <a class="admin-table__main" href="/admin/articles/<?= (int) $post['id'] ?>/modifier"><?= e($post['title']) ?></a>
                                <span class="admin-table__sub"><?= e((string) ($post['author'] ?: '—')) ?> · <span class="admin-mono">/blog/<?= e((string) $post['slug']) ?></span></span>
                            </td>
                            <td><span class="admin-badge admin-badge--teal"><?= e($categories[$post['category']] ?? '—') ?></span></td>
                            <td><span class="admin-muted"><?= e(implode(', ', array_map(static fn (string $t): string => '#' . $t, $tags[(int) $post['id']] ?? [])) ?: '—') ?></span></td>
                            <td class="admin-nowrap"><?= e(AdminFormat::date($post['created_at'])) ?></td>
                            <td>
                                <div class="admin-table__actions">
                                    <?php if ($post['slug']): ?>
                                        <a class="admin-btn admin-btn--ghost admin-btn--sm admin-btn--icon" href="/blog/<?= e($post['slug']) ?>" target="_blank" rel="noopener" aria-label="Lire « <?= e($post['title']) ?> » sur le blog" title="Voir sur le blog"><?= component('components/icon', ['name' => 'eye']) ?></a>
                                    <?php endif; ?>
                                    <a class="admin-btn admin-btn--ghost admin-btn--sm admin-btn--icon" href="/admin/articles/<?= (int) $post['id'] ?>/modifier" aria-label="Modifier « <?= e($post['title']) ?> »" title="Modifier"><?= component('components/icon', ['name' => 'pen']) ?></a>
                                    <form method="POST" action="/admin/articles/<?= (int) $post['id'] ?>/supprimer" data-confirm="Supprimer définitivement « <?= e($post['title']) ?> » ?">
                                        <?= csrf_field() ?>
                                        <button class="admin-btn admin-btn--danger admin-btn--sm admin-btn--icon" type="submit" aria-label="Supprimer « <?= e($post['title']) ?> »" title="Supprimer"><?= component('components/icon', ['name' => 'trash']) ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?= component('admin/components/pagination', ['paginator' => $posts, 'path' => '/admin/articles', 'query' => ['q' => $search, 'categorie' => $category]]) ?>
</section>
