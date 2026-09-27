<?php layout('layouts.app', ['title' => 'Nouveau mot de passe']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:30rem">
        <div class="text-center" style="margin-bottom: var(--space-5)">
            <h1 style="font-size: var(--step-3)">Nouveau mot de passe</h1>
        </div>
        <form class="card" method="POST" action="<?= e($action) ?>" novalidate>
            <?= csrf_field() ?>
            <?= field('password', 'Nouveau mot de passe', ['type' => 'password', 'autocomplete' => 'new-password']) ?>
            <?= field('password_confirmation', 'Confirmer le mot de passe', ['type' => 'password', 'autocomplete' => 'new-password']) ?>
            <button class="btn btn--primary btn--block btn--lg" type="submit">Enregistrer</button>
        </form>
    </div>
</section>
