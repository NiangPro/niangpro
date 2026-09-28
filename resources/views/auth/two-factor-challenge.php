<?php layout('layouts.app', ['title' => 'Double authentification']); ?>

<h1>Double authentification</h1>
<p>Saisissez le code à 6 chiffres affiché par votre application d'authentification, ou l'un de vos codes de secours.</p>
<form method="POST" action="/two-factor-challenge">
    <?= csrf_field() ?>

    <label for="code">Code</label>
    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus>
    <?= component('components/field-errors', ['field' => 'code']) ?>

    <button type="submit">Vérifier</button>
</form>
<p class="link"><a href="/login">Retour à la connexion</a></p>
