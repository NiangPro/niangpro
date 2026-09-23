<?php layout('layouts.app', ['title' => 'Espace rédaction', 'active' => '/login']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:30rem">
        <div class="text-center" style="margin-bottom: var(--space-5)">
            <h1 style="font-size: var(--step-3)">Espace rédaction</h1>
            <p class="muted">Connectez-vous pour écrire et gérer les articles.</p>
        </div>
        <?php if ($message = flashed('success')): ?>
            <p class="alert alert--info"><?= e($message) ?></p>
        <?php endif; ?>
        <form class="card" method="POST" action="/login" novalidate>
            <?= csrf_field() ?>
            <?= component('components/field', ['name' => 'email', 'label' => 'Adresse email', 'type' => 'email', 'autocomplete' => 'email']) ?>
            <?= component('components/field', ['name' => 'password', 'label' => 'Mot de passe', 'type' => 'password', 'autocomplete' => 'current-password']) ?>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Se connecter</button>
        </form>
        <p class="text-center muted" style="margin-top: var(--space-5)"><a href="/forgot-password">Mot de passe oublié ?</a></p>
    </div>
</section>
