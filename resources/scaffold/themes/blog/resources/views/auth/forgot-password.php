<?php layout('layouts.app', ['title' => 'Mot de passe oublié', 'active' => '/login']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:30rem">
        <div class="text-center" style="margin-bottom: var(--space-5)">
            <h1 style="font-size: var(--step-3)">Mot de passe oublié</h1>
            <p class="muted">Indiquez votre email, nous vous envoyons un lien de réinitialisation.</p>
        </div>
        <?php if ($message = flashed('success')): ?>
            <p class="alert alert--info"><?= e($message) ?></p>
        <?php endif; ?>
        <form class="card" method="POST" action="/forgot-password" novalidate>
            <?= csrf_field() ?>
            <?= component('components/field', ['name' => 'email', 'label' => 'Adresse email', 'type' => 'email', 'autocomplete' => 'email']) ?>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Envoyer le lien</button>
        </form>
        <p class="text-center muted" style="margin-top: var(--space-5)"><a href="/login">Retour à la connexion</a></p>
    </div>
</section>
