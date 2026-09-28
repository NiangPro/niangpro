<?php
/**
 * @var array<string, mixed>|null $featured      article le plus récent
 * @var list<array<string, mixed>> $recent        les deux suivants, à côté de l'article à la une
 * @var list<array<string, mixed>> $posts         la rangée « À lire en ce moment »
 * @var array<string, int> $categories            slug de catégorie => nombre d'articles
 * @var list<array{name: string, posts_count: int|string}> $topics  thèmes les plus utilisés
 * @var array<string, int> $stats                 libellé => nombre (articles, auteurs, thèmes)
 */

use App\Support\PostFormat;

layout('layouts.app', ['active' => '/']);
$featuredCategory = $featured ? config('site.categories.' . ($featured['category'] ?? ''), []) : [];
$featuredUrl = $featured && !empty($featured['slug']) ? '/blog/' . $featured['slug'] : '/blog';
?>
<section class="bh" aria-labelledby="bh-title">
    <div class="bh__backdrop" aria-hidden="true">
        <span class="bh__orb bh__orb--teal"></span>
        <span class="bh__orb bh__orb--gold"></span>
        <span class="bh__orb bh__orb--violet"></span>
        <span class="bh__grid"></span>
    </div>

    <div class="container bh__inner">
        <div class="bh__intro">
            <?php if ($featured): ?>
                <a class="bh__pill" href="<?= e($featuredUrl) ?>">
                    <span class="bh__pulse" aria-hidden="true"></span>
                    <span class="bh__pill-label">Nouveau</span>
                    <span class="bh__pill-text"><?= e($featured['title']) ?></span>
                    <?= component('components/icon', ['name' => 'arrow-right']) ?>
                </a>
            <?php endif; ?>

            <h1 id="bh-title" class="bh__title">Concevoir le web, <em>clairement</em>.</h1>
            <p class="bh__lead"><?= e(config('site.tagline')) ?>. Des articles courts et concrets, à appliquer dès aujourd'hui.</p>

            <div class="bh__actions">
                <a class="btn btn--primary btn--lg" href="/blog">Lire les articles <?= component('components/icon', ['name' => 'arrow-right']) ?></a>
                <a class="btn btn--ghost btn--lg" href="/tags">Explorer les thèmes</a>
            </div>

            <?php if ($topics): ?>
                <ul class="bh__topics" aria-label="Thèmes les plus lus">
                    <?php foreach ($topics as $topic): ?>
                        <li><a href="/tags/<?= e($topic['name']) ?>">#<?= e($topic['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <dl class="bh__stats">
                <?php foreach ($stats as $label => $value): ?>
                    <div>
                        <dt><?= e(ucfirst($label)) ?></dt>
                        <dd><?= (int) $value ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>

        <div class="bh__showcase">
            <?php if ($featured): ?>
                <article class="bh-feature">
                    <a class="bh-feature__media" href="<?= e($featuredUrl) ?>" tabindex="-1" aria-hidden="true">
                        <?= component('components/art', ['seed' => $featured['slug'] ?? $featured['id'], 'label' => $featured['title'], 'ratio' => '16 / 10', 'glyph' => $featuredCategory['icon'] ?? 'pen']) ?>
                    </a>
                    <div class="bh-feature__chips">
                        <span class="badge">À la une</span>
                        <span class="bh-chip"><?= component('components/icon', ['name' => 'clock']) ?> <?= PostFormat::readingMinutes((string) $featured['body']) ?> min de lecture</span>
                    </div>
                    <div class="bh-feature__body">
                        <?php if ($featuredCategory): ?><p class="bh-feature__kicker"><?= e($featuredCategory['label']) ?></p><?php endif; ?>
                        <h2><a href="<?= e($featuredUrl) ?>"><?= e($featured['title']) ?></a></h2>
                        <p class="muted"><?= e($featured['excerpt'] ?? '') ?></p>
                        <div class="bh-feature__foot">
                            <p class="post-meta">
                                <?php if (!empty($featured['author'])): ?><span><?= e($featured['author']) ?></span><?php endif; ?>
                                <time datetime="<?= e(substr((string) $featured['created_at'], 0, 10)) ?>"><?= e(PostFormat::date((string) $featured['created_at'])) ?></time>
                            </p>
                            <a class="link-arrow" href="<?= e($featuredUrl) ?>">Lire l'article <?= component('components/icon', ['name' => 'arrow-right']) ?></a>
                        </div>
                    </div>
                </article>

                <?php if ($recent): ?>
                    <ul class="bh-recent" aria-label="Également récents">
                        <?php foreach ($recent as $item): ?>
                            <?php $itemCategory = config('site.categories.' . ($item['category'] ?? ''), []); ?>
                            <li>
                                <a class="bh-recent__item" href="<?= e(!empty($item['slug']) ? '/blog/' . $item['slug'] : '/blog') ?>">
                                    <span class="bh-recent__thumb" aria-hidden="true">
                                        <?= component('components/art', ['seed' => $item['slug'] ?? $item['id'], 'label' => '', 'ratio' => '1 / 1', 'glyph' => $itemCategory['icon'] ?? 'pen']) ?>
                                    </span>
                                    <span class="bh-recent__text">
                                        <?php if ($itemCategory): ?><span class="bh-recent__kicker"><?= e($itemCategory['label']) ?></span><?php endif; ?>
                                        <strong><?= e($item['title']) ?></strong>
                                        <span class="bh-recent__meta"><?= PostFormat::readingMinutes((string) $item['body']) ?> min de lecture</span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <p class="alert alert--info">Aucun article pour l'instant. Lancez <code>./bin/niang db:seed</code> pour charger les articles de démonstration.</p>
            <?php endif; ?>
        </div>
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
