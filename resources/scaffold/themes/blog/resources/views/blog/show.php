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
$headings = PostFormat::headings((string) $post['body']);
$minutes = PostFormat::readingMinutes((string) $post['body']);
$date = PostFormat::date((string) $post['created_at']);
$isoDate = substr((string) $post['created_at'], 0, 10);
?>
<div class="reading-progress" aria-hidden="true"><span data-reading-progress></span></div>

<article class="post">
    <header class="post-hero">
        <div class="post-hero__art" aria-hidden="true">
            <?= component('components/art', ['seed' => $post['slug'] ?? $post['id'], 'label' => '', 'ratio' => '21 / 9', 'glyph' => $category['icon'] ?? 'pen']) ?>
        </div>

        <div class="container post-hero__inner">
            <ol class="breadcrumb" aria-label="Fil d'Ariane">
                <li><a href="/">Accueil</a></li>
                <li><a href="/blog">Articles</a></li>
                <li aria-current="page"><?= e($post['title']) ?></li>
            </ol>

            <div class="post-hero__card">
                <div class="post-hero__chips">
                    <?php if ($category): ?>
                        <a class="post-chip post-chip--accent" href="/categories/<?= e($post['category']) ?>"><?= component('components/icon', ['name' => $category['icon'] ?? 'pen']) ?> <?= e($category['label']) ?></a>
                    <?php endif; ?>
                    <span class="post-chip"><?= component('components/icon', ['name' => 'clock']) ?> <?= $minutes ?> min de lecture</span>
                </div>

                <h1 class="post-hero__title"><?= e($post['title']) ?></h1>

                <?php if (!empty($post['excerpt'])): ?>
                    <p class="post-hero__excerpt"><?= e($post['excerpt']) ?></p>
                <?php endif; ?>

                <div class="post-byline">
                    <?php if (!empty($post['author'])): ?>
                        <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($post['author'], 0, 1))) ?></span>
                    <?php endif; ?>
                    <p class="post-byline__text">
                        <?php if (!empty($post['author'])): ?><strong><?= e($post['author']) ?></strong><?php endif; ?>
                        <span class="post-meta">
                            <span>Publié le <time datetime="<?= e($isoDate) ?>"><?= e($date) ?></time></span>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </header>

    <div class="container post-layout<?= $headings ? '' : ' post-layout--no-toc' ?>">
        <aside class="post-rail" aria-label="Partager l'article">
            <button class="post-rail__btn" type="button" data-copy-link aria-label="Copier le lien de l'article">
                <?= component('components/icon', ['name' => 'link']) ?>
                <span class="post-rail__label">Copier le lien</span>
            </button>
            <button class="post-rail__btn" type="button" data-share hidden aria-label="Partager l'article">
                <?= component('components/icon', ['name' => 'arrow-up-right']) ?>
                <span class="post-rail__label">Partager</span>
            </button>
            <a class="post-rail__btn post-rail__btn--top" href="#contenu" aria-label="Revenir en haut de la page">
                <?= component('components/icon', ['name' => 'chevron-down']) ?>
                <span class="post-rail__label">Haut de page</span>
            </a>
            <p class="sr-only" role="status" data-copy-status></p>
        </aside>

        <?php if ($headings): ?>
            <nav class="post-toc" aria-label="Sommaire de l'article">
                <details open>
                    <summary><?= component('components/icon', ['name' => 'book']) ?> Sommaire</summary>
                    <ol>
                        <?php foreach ($headings as $heading): ?>
                            <li><a href="#<?= e($heading['id']) ?>" data-toc-link><?= e($heading['text']) ?></a></li>
                        <?php endforeach; ?>
                    </ol>
                </details>
            </nav>
        <?php endif; ?>

        <div class="post-main">
            <div class="prose article-body" data-article-body>
                <?= PostFormat::html((string) $post['body']) ?>
            </div>

            <?php if (!empty($post['tags'])): ?>
                <div class="post-tags" aria-label="Thèmes de l'article">
                    <?php foreach ($post['tags'] as $tag): ?>
                        <a class="tag" href="/tags/<?= e($tag['name']) ?>">#<?= e($tag['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($post['author'])): ?>
                <aside class="author-card">
                    <span class="avatar avatar--lg" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($post['author'], 0, 1))) ?></span>
                    <div>
                        <p class="author-card__eyebrow">Écrit par</p>
                        <p class="author-card__name"><?= e($post['author']) ?></p>
                        <p class="muted">Rédaction du <?= e(config('site.name')) ?>. Publié le <time datetime="<?= e($isoDate) ?>"><?= e($date) ?></time>.</p>
                    </div>
                    <a class="btn btn--ghost btn--sm" href="/blog">Tous les articles</a>
                </aside>
            <?php endif; ?>
        </div>
    </div>
</article>

<?php if ($related): ?>
<section class="section section--alt post-related">
    <div class="container">
        <div class="section-head section-head--left">
            <span class="eyebrow">Continuer la lecture</span>
            <h2>À lire ensuite</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach ($related as $item): ?>
                <?= component('components/post-card', ['post' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
