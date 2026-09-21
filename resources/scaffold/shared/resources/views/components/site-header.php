<?php
/**
 * En-tête collant avec navigation responsive. Le menu burger est du JS vanilla (public/js/niang.js) ;
 * sans JS, la navigation reste simplement affichée.
 *
 * @var string $brand                                        nom du site
 * @var list<array{label: string, href: string}> $nav        liens de navigation
 * @var string|null $active                                  chemin de la page courante, ex. '/services'
 * @var array{label: string, href: string}|null $cta         bouton d'action à droite
 * @var string|null $actions                                 HTML déjà sûr affiché avant le bouton (ex. lien panier)
 */
$active = $active ?? '';
$cta = $cta ?? null;
$actions = $actions ?? '';
$isActive = static fn (string $href): bool => $href === '/'
    ? $active === '/'
    : ($href[0] === '/' && ($active === $href || str_starts_with($active, rtrim($href, '/') . '/')));
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="/" aria-label="<?= e($brand) ?> — accueil">
            <span class="brand__mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($brand, 0, 1))) ?></span>
            <span><?= e($brand) ?></span>
        </a>

        <nav class="site-nav" id="site-nav" aria-label="Navigation principale">
            <ul>
                <?php foreach ($nav as $item): ?>
                    <li><a href="<?= e($item['href']) ?>"<?= $isActive($item['href']) ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="site-header__actions">
            <?= $actions ?>
            <?php if ($cta): ?>
                <a class="btn btn--primary btn--sm" href="<?= e($cta['href']) ?>"><?= e($cta['label']) ?></a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-nav"
                aria-label="Ouvrir le menu" data-label-open="Ouvrir le menu" data-label-close="Fermer le menu">
            <?= component('components/icon', ['name' => 'menu', 'class' => 'icon--menu']) ?>
            <?= component('components/icon', ['name' => 'x', 'class' => 'icon--close']) ?>
        </button>
    </div>
</header>
