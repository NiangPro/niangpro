<?php
/**
 * Rédaction et modification d'un article (même formulaire).
 *
 * @var array<string, mixed>|null $post
 * @var array<string, string> $categories
 * @var list<array<string, mixed>> $allTags
 * @var list<int> $selectedTags
 */
$editing = $post !== null;

layout('admin.layouts.app', [
    'title' => $editing ? 'Modifier l\'article' : 'Nouvel article',
    'active' => '/admin/articles',
    'subtitle' => $editing ? $post['title'] : 'Il sera publié dès l\'enregistrement.',
    'actions' => ($editing && $post['slug'] ? '<a class="admin-btn admin-btn--ghost" href="/blog/' . e($post['slug']) . '" target="_blank" rel="noopener">' . component('components/icon', ['name' => 'eye']) . 'Voir sur le blog</a>' : '')
        . '<a class="admin-btn admin-btn--ghost" href="/admin/articles">' . component('components/icon', ['name' => 'arrow-left']) . 'Tous les articles</a>',
]);
$oldTags = old('tags');
$checked = is_array($oldTags) ? array_map('intval', $oldTags) : $selectedTags;
?>
<form method="POST" action="<?= $editing ? '/admin/articles/' . (int) $post['id'] : '/admin/articles' ?>" novalidate>
    <?= csrf_field() ?>
    <div class="admin-grid admin-grid--main">
        <section class="admin-card">
            <div class="admin-form">
                <?= component('admin/components/field', ['name' => 'title', 'label' => 'Titre', 'value' => $post['title'] ?? '', 'required' => true]) ?>
                <?= component('admin/components/field', ['name' => 'excerpt', 'label' => 'Chapeau', 'type' => 'textarea', 'rows' => 2, 'value' => $post['excerpt'] ?? '', 'hint' => 'Une ou deux phrases affichées dans les listes d\'articles.']) ?>
                <?= component('admin/components/field', [
                    'name' => 'body',
                    'label' => 'Contenu',
                    'type' => 'textarea',
                    'rows' => 18,
                    'value' => $post['body'] ?? '',
                    'required' => true,
                    'hint' => 'Paragraphes séparés par une ligne vide · « ## » intertitre · « > » citation · « - » liste.',
                ]) ?>
            </div>
        </section>

        <div class="admin-stack">
            <section class="admin-card">
                <header class="admin-card__head"><h2>Publication</h2></header>
                <div class="admin-form">
                    <?= component('admin/components/field', ['name' => 'category', 'label' => 'Catégorie', 'type' => 'select', 'options' => $categories, 'value' => $post['category'] ?? array_key_first($categories), 'required' => true]) ?>
                    <?= component('admin/components/field', ['name' => 'author', 'label' => 'Auteur', 'value' => $post['author'] ?? '', 'hint' => 'Vide : votre nom.']) ?>
                    <?= component('admin/components/field', ['name' => 'published_at', 'label' => 'Date de publication', 'type' => 'date', 'value' => $editing ? substr((string) $post['created_at'], 0, 10) : '', 'hint' => $editing ? '' : 'Vide : aujourd\'hui.']) ?>
                    <?= component('admin/components/field', ['name' => 'slug', 'label' => 'Adresse (slug)', 'value' => $post['slug'] ?? '', 'hint' => 'Vide : déduite du titre.']) ?>
                </div>
            </section>

            <section class="admin-card">
                <header class="admin-card__head">
                    <h2>Tags</h2>
                    <a class="admin-card__link" href="/admin/tags">Gérer</a>
                </header>
                <?php if (!$allTags): ?>
                    <p class="admin-muted">Aucun tag : créez-en dans la section Tags.</p>
                <?php else: ?>
                    <div class="admin-checks">
                        <?php foreach ($allTags as $tag): ?>
                            <label class="admin-check"><input type="checkbox" name="tags[]" value="<?= (int) $tag['id'] ?>"<?= in_array((int) $tag['id'], $checked, true) ? ' checked' : '' ?>> #<?= e($tag['name']) ?></label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="admin-form__actions">
                <a class="admin-btn admin-btn--ghost" href="/admin/articles">Annuler</a>
                <button class="admin-btn admin-btn--primary" type="submit"><?= $editing ? 'Enregistrer' : 'Publier l\'article' ?></button>
            </div>
        </div>
    </div>
</form>
