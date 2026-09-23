<?php
/**
 * Création et modification d'un produit (même formulaire).
 *
 * @var array<string, mixed>|null $product
 * @var array<string, string> $categories
 */
$editing = $product !== null;
$euros = static fn ($cents): string => $cents === null || $cents === '' ? '' : number_format((int) $cents / 100, 2, '.', '');

layout('admin.layouts.app', [
    'title' => $editing ? 'Modifier « ' . $product['name'] . ' »' : 'Nouveau produit',
    'active' => '/admin/produits',
    'subtitle' => $editing ? 'Les commandes déjà passées ne changent pas : elles gardent le nom et le prix d\'origine.' : 'Il apparaîtra immédiatement dans la boutique.',
    'actions' => '<a class="admin-btn admin-btn--ghost" href="/admin/produits">' . component('components/icon', ['name' => 'arrow-left']) . 'Retour aux produits</a>',
]);
$featured = old('featured', $editing ? (string) $product['featured'] : '0');
?>
<form method="POST" action="<?= $editing ? '/admin/produits/' . (int) $product['id'] : '/admin/produits' ?>" novalidate>
    <?= csrf_field() ?>
    <div class="admin-grid admin-grid--main">
        <section class="admin-card">
            <header class="admin-card__head"><h2>Informations</h2></header>
            <div class="admin-form">
                <?= component('admin/components/field', ['name' => 'name', 'label' => 'Nom du produit', 'value' => $product['name'] ?? '', 'required' => true]) ?>
                <?= component('admin/components/field', ['name' => 'slug', 'label' => 'Adresse (slug)', 'value' => $product['slug'] ?? '', 'hint' => 'Laissez vide pour la déduire du nom. Ex. : tasse-en-gres → /boutique/tasse-en-gres']) ?>
                <?= component('admin/components/field', ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 6, 'value' => $product['description'] ?? '', 'required' => true]) ?>
                <?= component('admin/components/field', ['name' => 'image', 'label' => 'Image (chemin sous public/)', 'value' => $product['image'] ?? '', 'hint' => 'Ex. : /images/produits/tasse.jpg — vide : une illustration est générée.']) ?>
            </div>
        </section>

        <div class="admin-stack">
            <section class="admin-card">
                <header class="admin-card__head"><h2>Prix et stock</h2></header>
                <div class="admin-form">
                    <div class="admin-form__row">
                        <?= component('admin/components/field', ['name' => 'price', 'label' => 'Prix (€)', 'type' => 'number', 'value' => $euros($product['price_cents'] ?? null), 'required' => true, 'attrs' => 'min="0" step="0.01" inputmode="decimal"']) ?>
                        <?= component('admin/components/field', ['name' => 'old_price', 'label' => 'Prix barré (€)', 'type' => 'number', 'value' => $euros($product['old_price_cents'] ?? null), 'hint' => 'Promotion si supérieur au prix.', 'attrs' => 'min="0" step="0.01" inputmode="decimal"']) ?>
                    </div>
                    <?= component('admin/components/field', ['name' => 'stock', 'label' => 'Stock (unités)', 'type' => 'number', 'value' => (string) ($product['stock'] ?? '0'), 'required' => true, 'attrs' => 'min="0" step="1"']) ?>
                </div>
            </section>

            <section class="admin-card">
                <header class="admin-card__head"><h2>Organisation</h2></header>
                <div class="admin-form">
                    <?= component('admin/components/field', ['name' => 'category', 'label' => 'Catégorie', 'type' => 'select', 'options' => $categories, 'value' => $product['category'] ?? array_key_first($categories), 'required' => true]) ?>
                    <label class="admin-check"><input type="checkbox" name="featured" value="1"<?= $featured ? ' checked' : '' ?>> Mettre en avant sur l'accueil</label>
                </div>
            </section>

            <div class="admin-form__actions">
                <a class="admin-btn admin-btn--ghost" href="/admin/produits">Annuler</a>
                <button class="admin-btn admin-btn--primary" type="submit"><?= $editing ? 'Enregistrer les modifications' : 'Ajouter le produit' ?></button>
            </div>
        </div>
    </div>
</form>
