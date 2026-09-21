<?php
/**
 * Visuel d'un produit : son image (colonne `image`, chemin sous public/) si elle est renseignée,
 * sinon une illustration générée à partir du slug.
 *
 * @var array<string, mixed> $product
 * @var int|null $variant  numéro de vue (pour la galerie de la fiche produit)
 * @var string|null $ratio
 */
$variant = $variant ?? 0;
$ratio = $ratio ?? '1 / 1';
$glyph = config('site.categories.' . $product['category'] . '.icon');
?>
<?php if (!empty($product['image']) && $variant === 0): ?>
    <div class="art" style="aspect-ratio: <?= e($ratio) ?>"><img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover"></div>
<?php else: ?>
    <?= component('components/art', [
        'seed' => $product['slug'] . '-' . $variant,
        'label' => $product['name'] . ($variant ? ' — vue ' . ($variant + 1) : ''),
        'ratio' => $ratio,
        'glyph' => $glyph,
    ]) ?>
<?php endif; ?>
