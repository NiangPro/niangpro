<?php
/**
 * Carte d'article : illustration, catégorie, titre, chapeau, auteur, date et temps de lecture.
 *
 * @var array<string, mixed> $post
 */

use App\Support\PostFormat;

$category = config('site.categories.' . ($post['category'] ?? ''), []);
$url = !empty($post['slug']) ? '/blog/' . $post['slug'] : '/blog';
$excerpt = $post['excerpt'] ?? mb_substr(strip_tags((string) $post['body']), 0, 160) . '…';
?>
<article class="card card--flush post-card reveal">
    <a class="post-card__media" href="<?= e($url) ?>" tabindex="-1" aria-hidden="true">
        <?= component('components/art', ['seed' => $post['slug'] ?? $post['id'], 'label' => $post['title'], 'ratio' => '16 / 9', 'glyph' => $category['icon'] ?? 'pen']) ?>
    </a>
    <div class="card__body">
        <?php if ($category): ?>
            <a class="badge badge--soft" style="align-self:flex-start; text-decoration:none" href="/categories/<?= e($post['category']) ?>"><?= e($category['label']) ?></a>
        <?php endif; ?>
        <h3><a href="<?= e($url) ?>"><?= e($post['title']) ?></a></h3>
        <p class="muted"><?= e($excerpt) ?></p>
        <p class="post-meta">
            <?php if (!empty($post['author'])): ?><span><?= e($post['author']) ?></span><?php endif; ?>
            <time datetime="<?= e(substr((string) $post['created_at'], 0, 10)) ?>"><?= e(PostFormat::date((string) $post['created_at'])) ?></time>
            <span><?= PostFormat::readingMinutes((string) $post['body']) ?> min de lecture</span>
        </p>
    </div>
</article>
