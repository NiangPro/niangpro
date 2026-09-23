<?php
/**
 * @var array<string, int> $stats
 * @var list<array{label: string, value: int, display: string}> $chart
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $topTags
 * @var list<array<string, mixed>> $latest
 */

use App\Support\AdminFormat;
use Niang\Core\Auth;

layout('admin.layouts.app', [
    'title' => 'Tableau de bord',
    'active' => '/admin',
    'subtitle' => 'Le rythme de publication et ce que lisent vos lecteurs.',
    'actions' => '<a class="admin-btn admin-btn--ghost" href="/" target="_blank" rel="noopener">' . component('components/icon', ['name' => 'external']) . 'Voir le blog</a>'
        . '<a class="admin-btn admin-btn--primary" href="/admin/articles/nouveau">' . component('components/icon', ['name' => 'plus']) . 'Nouvel article</a>',
]);
$firstName = explode(' ', (string) (Auth::user()['name'] ?? ''))[0];
$last = $latest[0] ?? null;
$categoryLabels = array_map(static fn (array $c): string => $c['label'], (array) config('site.categories', []));
?>
<section class="admin-hero">
    <div>
        <h2>Bonjour <?= e($firstName) ?>, prêt à écrire ?</h2>
        <p>
            <?php if ($last): ?>
                Dernier article : « <?= e($last['title']) ?> », <?= e(AdminFormat::ago($last['created_at'])) ?>.
            <?php else: ?>
                Aucun article publié pour l'instant : lancez-vous !
            <?php endif; ?>
        </p>
    </div>
    <a class="admin-btn admin-btn--ghost" href="/admin/articles/nouveau">Rédiger un article <?= component('components/icon', ['name' => 'arrow-right']) ?></a>
</section>

<div class="admin-grid admin-grid--stats">
    <?= component('admin/components/stat', ['label' => 'Articles publiés', 'value' => AdminFormat::number($stats['posts']), 'icon' => 'book', 'hint' => $stats['thisMonth'] . ' ce mois-ci']) ?>
    <?= component('admin/components/stat', ['label' => 'Temps de lecture moyen', 'value' => max(1, $stats['readingMinutes']) . ' min', 'icon' => 'clock', 'tone' => 'violet', 'hint' => 'par article']) ?>
    <?= component('admin/components/stat', ['label' => 'Tags', 'value' => (string) $stats['tags'], 'icon' => 'hash', 'tone' => 'amber', 'hint' => count($categories) . ' catégories']) ?>
    <?= component('admin/components/stat', ['label' => 'Auteurs', 'value' => (string) $stats['authors'], 'icon' => 'users', 'tone' => 'sky', 'hint' => 'ont signé au moins un article']) ?>
</div>

<div class="admin-grid admin-grid--main">
    <section class="admin-card">
        <header class="admin-card__head">
            <div>
                <h2>Publications des <?= count($chart) ?> derniers mois</h2>
                <p>Nombre d'articles publiés chaque mois.</p>
            </div>
            <span class="admin-chip"><?= array_sum(array_column($chart, 'value')) ?> articles</span>
        </header>
        <?= component('admin/components/area-chart', ['points' => $chart, 'caption' => 'Articles publiés par mois']) ?>
    </section>

    <section class="admin-card">
        <header class="admin-card__head">
            <h2>Par catégorie</h2>
            <a class="admin-card__link" href="/admin/categories">Détails</a>
        </header>
        <?= component('admin/components/bar-list', ['rows' => $categories, 'empty' => 'Aucun article classé pour le moment.']) ?>
    </section>
</div>

<div class="admin-grid admin-grid--main">
    <section class="admin-card admin-card--flush">
        <header class="admin-card__head">
            <h2>Derniers articles</h2>
            <a class="admin-card__link" href="/admin/articles">Tous les articles</a>
        </header>
        <?php if (!$latest): ?>
            <?= component('admin/components/empty', ['title' => 'Aucun article', 'text' => 'Vos articles apparaîtront ici.', 'icon' => 'pen', 'action' => '<a class="admin-btn admin-btn--primary" href="/admin/articles/nouveau">Écrire le premier</a>']) ?>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Article</th><th>Catégorie</th><th>Publié</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        <?php foreach ($latest as $post): ?>
                            <tr>
                                <td><a class="admin-table__main" href="/admin/articles/<?= (int) $post['id'] ?>/modifier"><?= e($post['title']) ?></a><span class="admin-table__sub"><?= e((string) ($post['author'] ?? '')) ?></span></td>
                                <td><span class="admin-badge admin-badge--teal"><?= e($categoryLabels[$post['category']] ?? '—') ?></span></td>
                                <td class="admin-nowrap"><?= e(AdminFormat::date($post['created_at'])) ?></td>
                                <td><div class="admin-table__actions"><a class="admin-btn admin-btn--ghost admin-btn--sm" href="/admin/articles/<?= (int) $post['id'] ?>/modifier">Modifier</a></div></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <header class="admin-card__head">
            <h2>Tags les plus utilisés</h2>
            <a class="admin-card__link" href="/admin/tags">Gérer</a>
        </header>
        <?= component('admin/components/bar-list', [
            'rows' => array_map(static fn (array $t): array => ['label' => '#' . $t['name'], 'value' => (int) $t['n'], 'display' => $t['n'] . ' article' . ($t['n'] > 1 ? 's' : '')], $topTags),
            'empty' => 'Aucun tag utilisé pour le moment.',
        ]) ?>
    </section>
</div>
