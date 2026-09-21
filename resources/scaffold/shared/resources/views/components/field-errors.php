<?php foreach (errors($field) as $error): ?>
    <p class="field__error" id="<?= e($field) ?>-error" role="alert"><?= e($error) ?></p>
<?php endforeach; ?>
