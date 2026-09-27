<?php layout('layouts.app', ['title' => 'Connexion', 'active' => '/login']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:30rem">
        <div class="text-center" style="margin-bottom: var(--space-5)">
            <h1 style="font-size: var(--step-3)">Connexion</h1>
            <p class="muted">Content de vous revoir.</p>
        </div>
        <form class="card" method="POST" action="/login" novalidate>
            <?= csrf_field() ?>
            <?= field('email', 'Adresse email', ['type' => 'email', 'autocomplete' => 'email']) ?>
            <?= field('password', 'Mot de passe', ['type' => 'password', 'autocomplete' => 'current-password']) ?>
            <label style="display:flex; gap:.5rem; align-items:center; margin-bottom: var(--space-4)"><input type="checkbox" name="remember" value="1"> Se souvenir de moi</label>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Se connecter</button>
        </form>
        <p class="text-center muted" style="margin-top: var(--space-5)">
            <a href="/forgot-password">Mot de passe oublié ?</a> · Pas encore de compte ? <a href="/register">Créer un compte</a>
        </p>
    </div>
</section>
