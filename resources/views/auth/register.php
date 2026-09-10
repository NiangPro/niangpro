<?php layout('layouts.app', ['title' => 'Créer un compte']); ?>

<h1>Créer un compte</h1>
<form method="POST" action="/register">
    <?= csrf_field() ?>

    <label for="name">Nom</label>
    <input id="name" name="name" value="<?= e(old('name', '')) ?>">
    <?= component('components/field-errors', ['field' => 'name']) ?>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="<?= e(old('email', '')) ?>">
    <?= component('components/field-errors', ['field' => 'email']) ?>

    <label for="password">Mot de passe</label>
    <input id="password" name="password" type="password">
    <?= component('components/field-errors', ['field' => 'password']) ?>

    <label for="password_confirmation">Confirmer le mot de passe</label>
    <input id="password_confirmation" name="password_confirmation" type="password">

    <button type="submit">Créer mon compte</button>
</form>
<p class="link">Déjà inscrit ? <a href="/login">Se connecter</a></p>
