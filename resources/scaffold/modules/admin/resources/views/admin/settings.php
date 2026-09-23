<?php
/** @var array<string, mixed> $user */
layout('admin.layouts.app', [
    'title' => 'Paramètres',
    'active' => '/admin/parametres',
    'subtitle' => 'Identité du site et accès à votre compte administrateur.',
]);
$contact = (array) config('site.contact', []);
?>
<div class="admin-grid admin-grid--2">
    <section class="admin-card">
        <header class="admin-card__head">
            <h2>Identité du site</h2>
            <span class="admin-chip">config/site.php</span>
        </header>
        <dl class="admin-dl">
            <div><dt>Nom</dt><dd><?= e((string) config('site.name')) ?></dd></div>
            <div><dt>Slogan</dt><dd><?= e((string) config('site.tagline', '—')) ?></dd></div>
            <div><dt>Description</dt><dd><?= e((string) config('site.description', '—')) ?></dd></div>
            <?php foreach (['email' => 'Email de contact', 'phone' => 'Téléphone', 'address' => 'Adresse'] as $key => $label): ?>
                <?php if (!empty($contact[$key])): ?><div><dt><?= e($label) ?></dt><dd><?= e((string) $contact[$key]) ?></dd></div><?php endif; ?>
            <?php endforeach; ?>
        </dl>
        <p class="admin-muted admin-small">Ces valeurs se modifient dans <code>config/site.php</code> (ou <code>SITE_NAME</code> dans <code>.env</code>).</p>
    </section>

    <div class="admin-stack">
        <section class="admin-card">
            <header class="admin-card__head"><h2>Mon profil</h2></header>
            <form method="POST" action="/admin/parametres/profil" class="admin-form" novalidate>
                <?= csrf_field() ?>
                <?= component('admin/components/field', ['name' => 'name', 'label' => 'Nom', 'value' => $user['name'], 'required' => true]) ?>
                <?= component('admin/components/field', ['name' => 'email', 'label' => 'Email de connexion', 'type' => 'email', 'value' => $user['email'], 'required' => true]) ?>
                <div class="admin-form__actions"><button class="admin-btn admin-btn--primary" type="submit">Enregistrer</button></div>
            </form>
        </section>

        <section class="admin-card">
            <header class="admin-card__head"><h2>Mot de passe</h2></header>
            <form method="POST" action="/admin/parametres/mot-de-passe" class="admin-form" novalidate>
                <?= csrf_field() ?>
                <?= component('admin/components/field', ['name' => 'current_password', 'label' => 'Mot de passe actuel', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="current-password"']) ?>
                <?= component('admin/components/field', ['name' => 'password', 'label' => 'Nouveau mot de passe', 'type' => 'password', 'required' => true, 'hint' => '8 caractères minimum.', 'attrs' => 'autocomplete="new-password"']) ?>
                <?= component('admin/components/field', ['name' => 'password_confirmation', 'label' => 'Confirmer', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="new-password"']) ?>
                <div class="admin-form__actions"><button class="admin-btn admin-btn--primary" type="submit">Changer le mot de passe</button></div>
            </form>
        </section>
    </div>
</div>
