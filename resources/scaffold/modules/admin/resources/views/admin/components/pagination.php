<?php
/**
 * Pagination d'un Niang\Core\Database\Paginator, en conservant les filtres de l'URL courante.
 *
 * @var \Niang\Core\Database\Paginator $paginator
 * @var string $path    ex. '/admin/produits'
 * @var array<string, scalar> $query  filtres à conserver (sans « page »)
 */
$last = $paginator->lastPage();
$current = $paginator->currentPage;
$url = static fn (int $page): string => $path . '?' . http_build_query(array_filter([...$query, 'page' => $page], static fn ($v) => $v !== '' && $v !== null));
$from = $paginator->total === 0 ? 0 : ($current - 1) * $paginator->perPage + 1;
$to = min($paginator->total, $current * $paginator->perPage);
?>
<div class="admin-pagination">
    <p class="admin-muted"><?= $from ?>–<?= $to ?> sur <?= $paginator->total ?></p>
    <?php if ($last > 1): ?>
        <nav aria-label="Pagination">
            <?php if ($current > 1): ?><a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= e($url($current - 1)) ?>" rel="prev">Précédent</a><?php endif; ?>
            <?php for ($p = 1; $p <= $last; $p++): ?>
                <a class="admin-page<?= $p === $current ? ' is-current' : '' ?>" href="<?= e($url($p)) ?>"<?= $p === $current ? ' aria-current="page"' : '' ?>><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($current < $last): ?><a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= e($url($current + 1)) ?>" rel="next">Suivant</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
