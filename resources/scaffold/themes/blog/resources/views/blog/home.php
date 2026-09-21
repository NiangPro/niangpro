<?php
/**
 * @var array<string, mixed>|null $featured  article le plus récent
 * @var list<array<string, mixed>> $posts    les suivants
 * @var array<string, int> $categories       slug de catégorie => nombre d'articles
 */

use App\Support\PostFormat;

layout('layouts.app', ['active' => '/']);
$featuredCategory = $featured ? config('site.categories.' . ($featured['category'] ?? ''), []) : [];
?>
<section class="hero">
    <div class="container">
        <span class="eyebrow">Le blog</span>
        <h1>Concevoir le web, <em>clairement</em></h1>
        <p class="lead"><?= e(config('site.tagline')) ?>. Des idées, des coulisses de projets et des guides à appliquer dès aujourd'hui.</p>

        <?php if ($featured): ?>
            <article class="featured card card--flush reveal">
                <a class="featured__media" href="/blog/<?= e($featured['slug'] ?? '') ?>" tabindex="-1" aria-hidden="true">
                    <?= component('components/art', ['seed' => $featured['slug'] ?? $featured['id'], 'label' => $featured['title'], 'ratio' => '16 / 10', 'glyph' => $featuredCategory['icon'] ?? 'pen']) ?>
                </a>
                <div class="featured__body">
                    <span class="badge">À la une</span>
                    <h2><a href="/blog/<?= e($featured['slug'] ?? '') ?>"><?= e($featured['title']) ?></a></h2>
                    <p class="muted"><?= e($featured['excerpt'] ?? '') ?></p>
                    <p class="post-meta">
                        <span><?= e($featured['author'] ?? '') ?></span>
                        <time datetime="<?= e(substr((string) $featured['created_at'], 0, 10)) ?>"><?= e(PostFormat::date((string) $featured['created_at'])) ?></time>
                        <span><?= PostFormat::readingMinutes((string) $featured['body']) ?> min de lecture</span>
                    </p>
                    <a class="link-arrow" href="/blog/<?= e($featured['slug'] ?? '') ?>">Lire l'article <?= component('components/icon', ['name' => 'arrow-right']) ?></a>
                </div>
            </article>
        <?php else: ?>
            <p class="alert alert--info">Aucun article pour l'instant. Lancez <code>./bin/niang db:seed</code> pour charger les articles de démonstration.</p>
        <?php endif; ?>
    </div>
</section>

<?php if ($posts): ?>
<section class="section">
    <div class="container">
        <div class="section-head section-head--left">
            <span class="eyebrow">Derniers articles</span>
            <h2>À lire en ce moment</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach ($posts as $post): ?>
                <?= component('components/post-card', ['post' => $post]) ?>
            <?php endforeach; ?>
        </div>
        <p class="text-center" style="margin-top: var(--space-6)"><a class="btn btn--ghost" href="/blog">Tous les articles</a></p>
    </div>
</section>
<?php endif; ?>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Explorer</span>
            <h2>Trois façons de lire</h2>
        </div>
        <div class="grid grid--3">
            <?php foreach (config('site.categories') as $slug => $category): ?>
                <a class="card reveal" href="/categories/<?= e($slug) ?>">
                    <span class="card__icon"><?= component('components/icon', ['name' => $category['icon']]) ?></span>
                    <h3><?= e($category['label']) ?> <span class="muted" style="font-weight:500">· <?= (int) ($categories[$slug] ?? 0) ?></span></h3>
                    <p class="muted"><?= e($category['text']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="text-center" style="margin-top: var(--space-6)"><a class="link-arrow" href="/tags">Parcourir par thème <?= component('components/icon', ['name' => 'arrow-right']) ?></a></p>
    </div>
</section>
