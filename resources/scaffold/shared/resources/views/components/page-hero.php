<?php
/**
 * Bandeau de titre des pages intérieures.
 *
 * @var string $title
 * @var string|null $lead
 * @var list<array{label: string, href: string}>|null $crumbs  fil d'Ariane ; la page courante est ajoutée d'elle-même
 */
$crumbs = $crumbs ?? [];
?>
<div class="page-hero">
    <div class="container">
        <?php if ($crumbs): ?>
            <ol class="breadcrumb" aria-label="Fil d'Ariane">
                <li><a href="/">Accueil</a></li>
                <?php foreach ($crumbs as $crumb): ?>
                    <li><a href="<?= e($crumb['href']) ?>"><?= e($crumb['label']) ?></a></li>
                <?php endforeach; ?>
                <li aria-current="page"><?= e($title) ?></li>
            </ol>
        <?php endif; ?>
        <h1><?= e($title) ?></h1>
        <?php if (!empty($lead)): ?><p><?= e($lead) ?></p><?php endif; ?>
    </div>
</div>
