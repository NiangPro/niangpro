<?php layout('layouts.app', ['title' => 'Créer un compte', 'active' => '/register']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:30rem">
        <div class="text-center" style="margin-bottom: var(--space-5)">
            <h1 style="font-size: var(--step-3)">Créer un compte</h1>
            <p class="muted">Suivez vos commandes en un coup d'œil.</p>
        </div>
        <form class="card" method="POST" action="/register" novalidate>
            <?= csrf_field() ?>
            <?= field('name', 'Nom', ['autocomplete' => 'name']) ?>
            <?= field('email', 'Adresse email', ['type' => 'email', 'autocomplete' => 'email']) ?>
            <?= field('password', 'Mot de passe (8 caractères minimum)', ['type' => 'password', 'autocomplete' => 'new-password']) ?>
            <?= field('password_confirmation', 'Confirmer le mot de passe', ['type' => 'password', 'autocomplete' => 'new-password']) ?>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Créer mon compte</button>
        </form>
        <p class="text-center muted" style="margin-top: var(--space-5)">Déjà inscrit ? <a href="/login">Se connecter</a></p>
    </div>
</section>
