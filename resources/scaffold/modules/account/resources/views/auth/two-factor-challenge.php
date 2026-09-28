<?php layout('layouts.app', ['title' => 'Double authentification']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:30rem">
        <div class="text-center" style="margin-bottom: var(--space-5)">
            <h1 style="font-size: var(--step-3)">Double authentification</h1>
            <p class="muted">Saisissez le code à 6 chiffres de votre application d'authentification, ou l'un de vos codes de secours.</p>
        </div>
        <form class="card" method="POST" action="/two-factor-challenge" novalidate>
            <?= csrf_field() ?>
            <?= field('code', 'Code', ['autocomplete' => 'one-time-code']) ?>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Vérifier</button>
        </form>
        <p class="text-center muted" style="margin-top: var(--space-5)"><a href="/login">Retour à la connexion</a></p>
    </div>
</section>
