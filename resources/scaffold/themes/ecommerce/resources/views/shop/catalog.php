<?php
/**
 * @var list<array<string, mixed>> $products
 * @var string $category  slug de la catégorie filtrée, '' pour toutes
 * @var string $sort      clé de tri
 * @var string $search    terme recherché
 */
layout('layouts.app', ['title' => 'Boutique', 'active' => '/boutique']);

$categories = config('site.categories');
$sorts = ['nouveautes' => 'Nouveautés', 'prix-asc' => 'Prix croissant', 'prix-desc' => 'Prix décroissant', 'nom' => 'Nom (A–Z)'];
// Garde le tri et la recherche en changeant de catégorie.
$link = static fn (string $slug): string => '/boutique?' . http_build_query(array_filter(['categorie' => $slug, 'tri' => $sort !== 'nouveautes' ? $sort : '', 'q' => $search]));
?>
<?= component('components/page-hero', [
    'title' => $category !== '' ? $categories[$category]['label'] : 'Toute la boutique',
    'lead' => $category !== '' ? $categories[$category]['text'] : 'Des objets choisis pour la maison, la cuisine et le bureau.',
    'crumbs' => $category !== '' ? [['label' => 'Boutique', 'href' => '/boutique']] : [],
]) ?>

<section class="section section--tight">
    <div class="container">
        <form class="toolbar" method="GET" action="/boutique" role="search">
            <div class="tags" role="group" aria-label="Catégories">
                <a class="tag<?= $category === '' ? ' tag--active' : '' ?>" href="<?= e($link('')) ?>"<?= $category === '' ? ' aria-current="true"' : '' ?>>Tout</a>
                <?php foreach ($categories as $slug => $item): ?>
                    <a class="tag<?= $category === $slug ? ' tag--active' : '' ?>" href="<?= e($link($slug)) ?>"<?= $category === $slug ? ' aria-current="true"' : '' ?>><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($category !== ''): ?><input type="hidden" name="categorie" value="<?= e($category) ?>"><?php endif; ?>
            <div class="toolbar__controls">
                <label class="sr-only" for="q">Rechercher un produit</label>
                <input id="q" name="q" type="search" placeholder="Rechercher…" value="<?= e($search) ?>">
                <label class="sr-only" for="tri">Trier par</label>
                <select id="tri" name="tri" data-autosubmit>
                    <?php foreach ($sorts as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $sort === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn--ghost btn--sm" type="submit">Appliquer</button>
            </div>
        </form>

        <p class="muted" role="status"><?= count($products) ?> produit<?= count($products) > 1 ? 's' : '' ?></p>

        <?php if (!$products): ?>
            <div class="card text-center" style="align-items:center">
                <h2 style="font-size: var(--step-1)">Aucun produit trouvé</h2>
                <p class="muted">Essayez une autre recherche ou parcourez toute la boutique.</p>
                <a class="btn btn--primary" href="/boutique">Voir tous les produits</a>
            </div>
        <?php endif; ?>

        <div class="grid grid--4">
            <?php foreach ($products as $product): ?>
                <?= component('components/product-card', ['product' => $product]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
