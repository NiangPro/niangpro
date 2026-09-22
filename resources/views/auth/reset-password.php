<?php layout('layouts.app', ['title' => 'Réinitialiser le mot de passe']); ?>

<h1>Réinitialiser le mot de passe</h1>
<?php if ($message = flashed('success')): ?>
    <p class="success"><?= e($message) ?></p>
<?php endif; ?>
<form method="POST" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <label for="password">Nouveau mot de passe</label>
    <input id="password" name="password" type="password">
    <?= component('components/field-errors', ['field' => 'password']) ?>

    <label for="password_confirmation">Confirmer le mot de passe</label>
    <input id="password_confirmation" name="password_confirmation" type="password">

    <button type="submit">Réinitialiser</button>
</form>
