<?php layout('layouts.app', ['title' => 'Double authentification', 'active' => '/compte']); ?>

<section class="section">
    <div class="container container--narrow" style="max-width:36rem">
        <p class="breadcrumb"><a href="/compte">Mon compte</a> / Double authentification</p>
        <h1 style="font-size: var(--step-3)">Double authentification</h1>
        <?= component('components/field-errors', ['field' => 'code']) ?>

        <div class="card stack">
            <?php if ($setup !== null): ?>
                <p>1. Ajoutez ce compte dans votre application d'authentification (Google Authenticator, Aegis, 1Password...) :
                    <a href="<?= e($setup['uri']) ?>">ouvrir dans l'application</a>, ou saisissez la clé :</p>
                <p><code><?= e(implode(' ', str_split($setup['secret'], 4))) ?></code></p>
                <p>2. Conservez ces codes de secours en lieu sûr. Chacun remplace une fois un code de l'application ; ils ne seront plus affichés :</p>
                <ul class="cluster">
                    <?php foreach ($setup['recovery_codes'] as $code): ?>
                        <li><code><?= e($code) ?></code></li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" action="/user/two-factor/confirm" novalidate>
                    <?= csrf_field() ?>
                    <?= field('code', '3. Code affiché par l\'application', ['autocomplete' => 'one-time-code']) ?>
                    <button class="btn btn--primary" type="submit">Confirmer</button>
                </form>
            <?php elseif ($enabled): ?>
                <p>La double authentification est <strong>activée</strong>. Codes de secours restants : <?= (int) $remaining ?>.</p>
                <form method="POST" action="/user/two-factor/disable" novalidate>
                    <?= csrf_field() ?>
                    <?= field('password', 'Mot de passe', ['type' => 'password', 'autocomplete' => 'current-password']) ?>
                    <button class="btn btn--ghost" type="submit">Désactiver</button>
                </form>
            <?php else: ?>
                <p>En plus du mot de passe, un code de votre téléphone sera demandé à la connexion.</p>
                <form method="POST" action="/user/two-factor" novalidate>
                    <?= csrf_field() ?>
                    <?= field('password', 'Mot de passe', ['type' => 'password', 'autocomplete' => 'current-password']) ?>
                    <button class="btn btn--primary" type="submit">Activer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
