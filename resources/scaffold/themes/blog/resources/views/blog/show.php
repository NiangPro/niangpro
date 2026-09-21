<?php
/**
 * @var array<string, mixed> $post     article avec ses tags (clé « tags »)
 * @var list<array<string, mixed>> $related
 */

use App\Support\PostFormat;

layout('layouts.app', [
    'title' => $post['title'],
    'description' => $post['excerpt'] ?? null,
    'active' => '/blog',
]);

$category = config('site.categories.' . ($post['category'] ?? ''), []);
?>
<article>
    <header class="page-hero">
        <div class="container container--narrow">
            <ol class="breadcrumb" aria-label="Fil d'Ariane">
                <li><a href="/">Accueil</a></li>
                <li><a href="/blog">Articles</a></li>
                <li aria-current="page"><?= e($post['title']) ?></li>
            </ol>
            <?php if ($category): ?>
                <a class="badge badge--soft" style="text-decoration:none" href="/categories/<?= e($post['category']) ?>"><?= e($category['label']) ?></a>
            <?php endif; ?>
            <h1 style="margin-top: var(--space-4)"><?= e($post['title']) ?></h1>
            <p class="post-meta" style="margin:0">
                <?php if (!empty($post['author'])): ?><span><?= e($post['author']) ?></span><?php endif; ?>
                <time datetime="<?= e(substr((string) $post['created_at'], 0, 10)) ?>"><?= e(PostFormat::date((string) $post['created_at'])) ?></time>
                <span><?= PostFormat::readingMinutes((string) $post['body']) ?> min de lecture</span>
            </p>
        </div>
    </header>

    <div class="container container--narrow" style="padding-top: var(--space-7)">
        <?= component('components/art', ['seed' => $post['slug'] ?? $post['id'], 'label' => 'Illustration de l\'article « ' . $post['title'] . ' »', 'ratio' => '16 / 8', 'glyph' => $category['icon'] ?? 'pen']) ?>

        <div class="prose article-body">
            <?php if (!empty($post['excerpt'])): ?><p class="lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
            <?= PostFormat::html((string) $post['body']) ?>
        </div>

        <?php if (!empty($post['tags'])): ?>
            <div class="tags" style="margin-top: var(--space-6)" aria-label="Thèmes de l'article">
                <?php foreach ($post['tags'] as $tag): ?>
                    <a class="tag" href="/tags/<?= e($tag['name']) ?>">#<?= e($tag['name']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($post['author'])): ?>
            <aside class="card author-box" style="margin-top: var(--space-6); flex-direction:row; align-items:center; gap:var(--space-4)">
                <span class="avatar" aria-hidden="true"><?= e(mb_substr($post['author'], 0, 1)) ?></span>
                <p style="margin:0"><strong><?= e($post['author']) ?></strong><br><span class="muted">Rédaction du <?= e(config('site.name')) ?></span></p>
            </aside>
        <?php endif; ?>
    </div>
</article>

<?php if ($related): ?>
<section class="section section--alt" style="margin-top: var(--space-8)">
    <div class="container">
        <div class="section-head"><h2>À lire ensuite</h2></div>
        <div class="grid grid--3">
            <?php foreach ($related as $item): ?>
                <?= component('components/post-card', ['post' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
