<?php
/**
 * Champ de formulaire complet : libellé, saisie, ancienne valeur après une erreur, message d'erreur.
 *
 * @var string $name
 * @var string $label
 * @var string|null $type          text (défaut), email, password, tel...
 * @var int|null $rows             si renseigné, un <textarea> de cette hauteur
 * @var string|null $value         valeur par défaut, remplacée par old() après une erreur de validation
 * @var string|null $autocomplete
 * @var bool|null $required
 */
$type = $type ?? 'text';
$rows = $rows ?? null;
$autocomplete = $autocomplete ?? null;
$required = $required ?? true;
// Un mot de passe n'est jamais réaffiché, même après une erreur.
$current = $type === 'password' ? '' : (string) old($name, $value ?? '');
$hasError = errors($name) !== [];
$attributes = 'id="' . e($name) . '" name="' . e($name) . '"'
    . ($autocomplete ? ' autocomplete="' . e($autocomplete) . '"' : '')
    . ($required ? ' required' : '')
    . ($hasError ? ' aria-invalid="true" aria-describedby="' . e($name) . '-error"' : '');
?>
<div class="field">
    <label for="<?= e($name) ?>"><?= e($label) ?></label>
    <?php if ($rows): ?>
        <textarea <?= $attributes ?> rows="<?= (int) $rows ?>"><?= e($current) ?></textarea>
    <?php else: ?>
        <input <?= $attributes ?> type="<?= e($type) ?>" value="<?= e($current) ?>">
    <?php endif; ?>
    <?= component('components/field-errors', ['field' => $name]) ?>
</div>
