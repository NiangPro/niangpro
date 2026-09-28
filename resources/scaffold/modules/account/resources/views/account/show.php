<?php layout('layouts.app', ['title' => 'Mon compte', 'active' => '/compte']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:40rem">
        <h1 style="font-size: var(--step-3)">Mon compte</h1>

        <?php if ($user['email_verified_at'] === null): ?>
            <p class="alert alert--info">Adresse email pas encore confirmée : cliquez sur le lien reçu par email.</p>
        <?php endif; ?>

        <div class="stack">
            <form class="card" method="POST" action="/compte/profil" novalidate>
                <h2 style="font-size: var(--step-1)">Profil</h2>
                <?= csrf_field() ?>
                <?= field('name', 'Nom', ['value' => $user['name'], 'autocomplete' => 'name']) ?>
                <?= field('email', 'Adresse email', ['type' => 'email', 'value' => $user['email'], 'autocomplete' => 'email']) ?>
                <button class="btn btn--primary" type="submit">Enregistrer</button>
            </form>

            <form class="card" method="POST" action="/compte/mot-de-passe" novalidate>
                <h2 style="font-size: var(--step-1)">Mot de passe</h2>
                <?= csrf_field() ?>
                <?= field('current_password', 'Mot de passe actuel', ['type' => 'password', 'autocomplete' => 'current-password']) ?>
                <?= field('password', 'Nouveau mot de passe', ['type' => 'password', 'autocomplete' => 'new-password']) ?>
                <?= field('password_confirmation', 'Confirmer', ['type' => 'password', 'autocomplete' => 'new-password']) ?>
                <button class="btn btn--primary" type="submit">Changer le mot de passe</button>
            </form>

            <div class="card">
                <h2 style="font-size: var(--step-1)">Double authentification</h2>
                <p class="muted"><?= $twoFactor ? 'Activée.' : 'Désactivée : un mot de passe volé suffit pour entrer.' ?></p>
                <a class="link-arrow" href="/user/two-factor"><?= $twoFactor ? 'Gérer' : 'Activer' ?></a>
            </div>

            <form class="card" method="POST" action="/compte/supprimer" novalidate>
                <h2 style="font-size: var(--step-1)">Supprimer le compte</h2>
                <p class="muted">Définitif : votre compte et vos jetons d'accès sont effacés.</p>
                <?= csrf_field() ?>
                <?= field('delete_password', 'Mot de passe', ['type' => 'password', 'autocomplete' => 'current-password']) ?>
                <button class="btn btn--ghost" type="submit">Supprimer définitivement</button>
            </form>
        </div>
    </div>
</section>
