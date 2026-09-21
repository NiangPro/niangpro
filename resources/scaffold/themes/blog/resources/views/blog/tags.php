<?php
/**
 * @var list<array{name: string, posts_count: int|string}> $tags
 * @var array<string, int> $categories
 */
layout('layouts.app', ['title' => 'Catégories et thèmes', 'active' => '/tags', 'description' => 'Parcourez les articles par catégorie ou par thème.']);
?>
<?= component('components/page-hero', ['title' => 'Catégories et thèmes', 'lead' => 'Trois catégories pour choisir un format, des thèmes pour choisir un sujet.', 'crumbs' => []]) ?>

<section class="section">
    <div class="container">
        <div class="section-head section-head--left"><h2>Catégories</h2></div>
        <div class="grid grid--3">
            <?php foreach (config('site.categories') as $slug => $category): ?>
                <a class="card reveal" href="/categories/<?= e($slug) ?>">
                    <span class="card__icon"><?= component('components/icon', ['name' => $category['icon']]) ?></span>
                    <h3><?= e($category['label']) ?></h3>
                    <p class="muted"><?= e($category['text']) ?></p>
                    <p class="post-meta" style="margin-top:auto"><span><?= (int) ($categories[$slug] ?? 0) ?> article<?= ($categories[$slug] ?? 0) > 1 ? 's' : '' ?></span></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head section-head--left"><h2>Thèmes</h2></div>
        <?php if (!$tags): ?><p class="muted">Aucun thème pour l'instant.</p><?php endif; ?>
        <div class="tags tags--cloud">
            <?php foreach ($tags as $tag): ?>
                <a class="tag" href="/tags/<?= e($tag['name']) ?>">#<?= e($tag['name']) ?> <span class="muted"><?= (int) $tag['posts_count'] ?></span></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
