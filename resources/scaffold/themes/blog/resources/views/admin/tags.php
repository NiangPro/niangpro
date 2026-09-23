<?php
/** @var list<array<string, mixed>> $tags */
layout('admin.layouts.app', [
    'title' => 'Tags',
    'active' => '/admin/tags',
    'subtitle' => 'Des thèmes transverses aux catégories, affichés sur /tags.',
]);
?>
<div class="admin-grid admin-grid--main">
    <section class="admin-card admin-card--flush">
        <header class="admin-card__head"><h2><?= count($tags) ?> tag<?= count($tags) > 1 ? 's' : '' ?></h2></header>
        <?php if (!$tags): ?>
            <?= component('admin/components/empty', ['title' => 'Aucun tag', 'text' => 'Créez votre premier tag avec le formulaire.', 'icon' => 'hash']) ?>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Tag</th><th class="num">Articles</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        <?php foreach ($tags as $tag): ?>
                            <tr>
                                <td><a class="admin-table__main" href="/tags/<?= e($tag['name']) ?>" target="_blank" rel="noopener">#<?= e($tag['name']) ?></a></td>
                                <td class="num"><?= (int) $tag['posts_count'] ?></td>
                                <td>
                                    <div class="admin-table__actions">
                                        <form method="POST" action="/admin/tags/<?= (int) $tag['id'] ?>/supprimer" data-confirm="Supprimer le tag #<?= e($tag['name']) ?> ? Il sera retiré de <?= (int) $tag['posts_count'] ?> article(s).">
                                            <?= csrf_field() ?>
                                            <button class="admin-btn admin-btn--danger admin-btn--sm admin-btn--icon" type="submit" aria-label="Supprimer le tag <?= e($tag['name']) ?>" title="Supprimer"><?= component('components/icon', ['name' => 'trash']) ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <header class="admin-card__head"><h2>Nouveau tag</h2></header>
        <form class="admin-form" method="POST" action="/admin/tags" novalidate>
            <?= csrf_field() ?>
            <?= component('admin/components/field', ['name' => 'name', 'label' => 'Nom', 'required' => true, 'hint' => 'Converti en minuscules sans accents : « Accessibilité » devient « accessibilite ».']) ?>
            <div class="admin-form__actions"><button class="admin-btn admin-btn--primary" type="submit">Créer le tag</button></div>
        </form>
    </section>
</div>
