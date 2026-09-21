<?php
/**
 * Pied de page multi-colonnes.
 *
 * @var string $brand
 * @var string|null $tagline
 * @var list<array{title: string, links: list<array{label: string, href: string}>}>|null $columns
 * @var list<array{label: string, href: string}>|null $legal  liens discrets tout en bas (mentions légales...)
 */
$columns = $columns ?? [];
$legal = $legal ?? [];
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-grid__about">
                <a class="brand" href="/" aria-label="<?= e($brand) ?> — accueil">
                    <span class="brand__mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($brand, 0, 1))) ?></span>
                    <span><?= e($brand) ?></span>
                </a>
                <?php if (!empty($tagline)): ?>
                    <p style="margin-top: var(--space-4)"><?= e($tagline) ?></p>
                <?php endif; ?>
            </div>

            <?php foreach ($columns as $column): ?>
                <nav aria-label="<?= e($column['title']) ?>">
                    <h2><?= e($column['title']) ?></h2>
                    <ul>
                        <?php foreach ($column['links'] as $link): ?>
                            <li><a href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endforeach; ?>
        </div>

        <div class="footer-bottom">
            <span>© <?= e(date('Y')) ?> <?= e($brand) ?>. Tous droits réservés.</span>
            <?php if ($legal): ?>
                <ul>
                    <?php foreach ($legal as $link): ?>
                        <li><a href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</footer>
