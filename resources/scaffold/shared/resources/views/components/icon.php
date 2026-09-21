<?php
/**
 * Icône SVG inline — aucune police d'icônes, aucun fichier externe.
 * component('components/icon', ['name' => 'check', 'class' => 'icon--big'])
 * Décorative par défaut (aria-hidden) : le texte voisin porte le sens ; passer 'label' pour une icône seule.
 * Les formes vivent dans components/icon-paths.
 *
 * @var string $name
 * @var string|null $class
 * @var string|null $label
 */
$inner = component('components/icon-paths', ['name' => $name]);
$class = trim('icon ' . ($class ?? ''));
$label = $label ?? null;
$a11y = $label !== null ? 'role="img" aria-label="' . e($label) . '"' : 'aria-hidden="true" focusable="false"';
?>
<?php if ($inner !== ''): ?><svg class="<?= e($class) ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" <?= $a11y ?>><?= $inner ?></svg><?php endif; ?>
