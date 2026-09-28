<?php layout('layouts.app', ['title' => 'Double authentification']); ?>

<h1>Double authentification</h1>
<?php if ($message = flashed('success')): ?>
    <p class="success"><?= e($message) ?></p>
<?php endif; ?>
<?= component('components/field-errors', ['field' => 'code']) ?>

<?php if ($setup !== null): ?>
    <p>1. Ajoutez ce compte dans votre application d'authentification (Google Authenticator, Aegis, 1Password...) :
        <a href="<?= e($setup['uri']) ?>">ouvrir dans l'application</a> (sur mobile), ou saisissez la clé :</p>
    <p><code><?= e(implode(' ', str_split($setup['secret'], 4))) ?></code></p>
    <p>2. Conservez ces codes de secours en lieu sûr. Chacun remplace une fois un code de l'application ; ils ne seront plus affichés :</p>
    <ul>
        <?php foreach ($setup['recovery_codes'] as $code): ?>
            <li><code><?= e($code) ?></code></li>
        <?php endforeach; ?>
    </ul>
    <p>3. Saisissez le code affiché par l'application pour confirmer :</p>
    <form method="POST" action="/user/two-factor/confirm">
        <?= csrf_field() ?>
        <label for="code">Code</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code">
        <button type="submit">Confirmer</button>
    </form>
<?php elseif ($enabled): ?>
    <p>La double authentification est <strong>activée</strong>. Codes de secours restants : <?= (int) $remaining ?>.</p>
    <form method="POST" action="/user/two-factor/disable">
        <?= csrf_field() ?>
        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" autocomplete="current-password">
        <button type="submit">Désactiver</button>
    </form>
<?php else: ?>
    <p>Protégez votre compte : en plus du mot de passe, un code de votre téléphone sera demandé à la connexion.</p>
    <form method="POST" action="/user/two-factor">
        <?= csrf_field() ?>
        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" autocomplete="current-password">
        <button type="submit">Activer</button>
    </form>
<?php endif; ?>
