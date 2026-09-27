<?php layout('layouts.app', ['title' => 'Tableau de bord', 'active' => '/tableau-de-bord']); ?>

<section class="section">
    <div class="container">
        <span class="eyebrow">Espace membre</span>
        <h1 style="font-size: var(--step-3)">Bonjour <?= e($user['name']) ?></h1>
        <p class="lead">C'est ici que commence votre produit : remplacez cette page par ce que vos utilisateurs viennent faire.</p>

        <div class="grid grid--3" style="margin-top: var(--space-6)">
            <a class="card card--hover" href="/compte">
                <span class="card__icon"><?= component('components/icon', ['name' => 'user']) ?></span>
                <h3>Mon compte</h3>
                <p class="muted">Nom, adresse email, mot de passe.</p>
            </a>
            <a class="card card--hover" href="/user/two-factor">
                <span class="card__icon"><?= component('components/icon', ['name' => 'shield']) ?></span>
                <h3>Double authentification</h3>
                <p class="muted">Un code de votre téléphone en plus du mot de passe.</p>
            </a>
            <div class="card">
                <span class="card__icon"><?= component('components/icon', ['name' => 'code']) ?></span>
                <h3>À vous de jouer</h3>
                <p class="muted">Routes : <code>routes/web.php</code>. Cette page : <code>resources/views/pages/dashboard.php</code>.</p>
            </div>
        </div>
    </div>
</section>
