<?php layout('layouts.app', ['title' => 'Connexion', 'active' => '/login']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:30rem">
        <div class="text-center" style="margin-bottom: var(--space-5)">
            <h1 style="font-size: var(--step-3)">Connexion</h1>
            <p class="muted">Retrouvez vos commandes et commandez plus vite.</p>
        </div>
        <form class="card" method="POST" action="/login" novalidate>
            <?= csrf_field() ?>
            <?= field('email', 'Adresse email', ['type' => 'email', 'autocomplete' => 'email']) ?>
            <?= field('password', 'Mot de passe', ['type' => 'password', 'autocomplete' => 'current-password']) ?>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Se connecter</button>
        </form>
        <p class="text-center muted" style="margin-top: var(--space-5)">Pas encore de compte ? <a href="/register">Créer un compte</a></p>
        <p class="text-center muted"><a href="/forgot-password">Mot de passe oublié ?</a></p>
    </div>
</section>
