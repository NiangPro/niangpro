<?php
/**
 * Champ de formulaire de l'administration (libellé, saisie, ancienne valeur, erreur).
 *
 * @var string $name
 * @var string $label
 * @var string|null $type      text (défaut), email, password, number, textarea, select
 * @var mixed $value           valeur par défaut, remplacée par old() après une erreur
 * @var array<string, string>|null $options  pour un select : valeur => libellé
 * @var int|null $rows
 * @var bool|null $required
 * @var string|null $hint
 * @var string|null $attrs     attributs HTML supplémentaires déjà sûrs (ex. 'min="0" step="0.01"')
 */
$type = $type ?? 'text';
$required = $required ?? false;
$current = $type === 'password' ? '' : (string) old($name, $value ?? '');
$fieldErrors = errors($name);
$id = 'f-' . $name;
$attributes = 'id="' . e($id) . '" name="' . e($name) . '"' . ($required ? ' required' : '')
    . ($fieldErrors ? ' aria-invalid="true" aria-describedby="' . e($id) . '-error"' : '') . (isset($attrs) ? ' ' . $attrs : '');
?>
<div class="admin-field<?= $fieldErrors ? ' has-error' : '' ?>">
    <label for="<?= e($id) ?>"><?= e($label) ?><?= $required ? ' <span aria-hidden="true">*</span>' : '' ?></label>
    <?php if ($type === 'textarea'): ?>
        <textarea <?= $attributes ?> rows="<?= (int) ($rows ?? 6) ?>"><?= e($current) ?></textarea>
    <?php elseif ($type === 'select'): ?>
        <select <?= $attributes ?>>
            <?php foreach ($options ?? [] as $optionValue => $optionLabel): ?>
                <option value="<?= e((string) $optionValue) ?>"<?= (string) $optionValue === $current ? ' selected' : '' ?>><?= e($optionLabel) ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <input <?= $attributes ?> type="<?= e($type) ?>" value="<?= e($current) ?>">
    <?php endif; ?>
    <?php if (!empty($hint)): ?><small class="admin-field__hint"><?= e($hint) ?></small><?php endif; ?>
    <?php if ($fieldErrors): ?><small class="admin-field__error" id="<?= e($id) ?>-error"><?= e($fieldErrors[0]) ?></small><?php endif; ?>
</div>
