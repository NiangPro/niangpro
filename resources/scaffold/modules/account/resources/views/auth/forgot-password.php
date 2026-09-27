<?php layout('layouts.app', ['title' => 'Mot de passe oublié']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:30rem">
        <div class="text-center" style="margin-bottom: var(--space-5)">
            <h1 style="font-size: var(--step-3)">Mot de passe oublié</h1>
            <p class="muted">Indiquez votre adresse : nous vous envoyons un lien pour en choisir un nouveau.</p>
        </div>
        <form class="card" method="POST" action="/forgot-password" novalidate>
            <?= csrf_field() ?>
            <?= field('email', 'Adresse email', ['type' => 'email', 'autocomplete' => 'email']) ?>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Envoyer le lien</button>
        </form>
        <p class="text-center muted" style="margin-top: var(--space-5)"><a href="/login">Retour à la connexion</a></p>
    </div>
</section>
