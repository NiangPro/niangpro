<?php
/**
 * Bandeau d'appel à l'action de fin de page.
 *
 * @var string $title
 * @var string|null $text
 * @var array{label: string, href: string} $primary
 * @var array{label: string, href: string}|null $secondary
 */
$secondary = $secondary ?? null;
?>
<div class="cta-band reveal">
    <h2><?= e($title) ?></h2>
    <?php if (!empty($text)): ?><p><?= e($text) ?></p><?php endif; ?>
    <div class="cluster">
        <a class="btn btn--gold btn--lg" href="<?= e($primary['href']) ?>"><?= e($primary['label']) ?></a>
        <?php if ($secondary): ?>
            <a class="btn btn--ghost btn--lg" href="<?= e($secondary['href']) ?>"><?= e($secondary['label']) ?></a>
        <?php endif; ?>
    </div>
</div>
