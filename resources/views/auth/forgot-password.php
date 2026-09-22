<?php layout('layouts.app', ['title' => 'Mot de passe oublié']); ?>

<h1>Mot de passe oublié</h1>
<?php if ($message = flashed('success')): ?>
    <p class="success"><?= e($message) ?></p>
<?php endif; ?>
<form method="POST" action="/forgot-password">
    <?= csrf_field() ?>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="<?= e(old('email', '')) ?>">
    <?= component('components/field-errors', ['field' => 'email']) ?>

    <button type="submit">Envoyer le lien de réinitialisation</button>
</form>
<p class="link"><a href="/login">Retour à la connexion</a></p>
