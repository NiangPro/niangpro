<?php layout('layouts.app', ['title' => 'Connexion']); ?>

<h1>Connexion</h1>
<form method="POST" action="/login">
    <?= csrf_field() ?>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="<?= e(old('email', '')) ?>">
    <?= component('components/field-errors', ['field' => 'email']) ?>

    <label for="password">Mot de passe</label>
    <input id="password" name="password" type="password">
    <?= component('components/field-errors', ['field' => 'password']) ?>

    <label><input type="checkbox" name="remember" value="1"> Se souvenir de moi</label>

    <button type="submit">Se connecter</button>
</form>
<?php foreach (\Niang\Core\OAuth::configured() as $provider): ?>
    <p class="link"><a href="/auth/<?= e($provider) ?>/redirect">Continuer avec <?= e($provider === 'github' ? 'GitHub' : 'Google') ?></a></p>
<?php endforeach; ?>
<p class="link">Pas de compte ? <a href="/register">Créer un compte</a></p>
<p class="link"><a href="/forgot-password">Mot de passe oublié ?</a></p>
