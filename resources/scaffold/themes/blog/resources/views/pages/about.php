<?php layout('layouts.app', ['title' => 'À propos', 'active' => '/a-propos', 'description' => 'Qui écrit Le Carnet Clair, et pourquoi.']); ?>

<?= component('components/page-hero', ['title' => 'À propos', 'lead' => 'Un carnet de notes publié en ligne, tenu par une petite équipe de designers et de développeurs.', 'crumbs' => []]) ?>

<section class="section">
    <div class="container split">
        <div class="prose">
            <span class="eyebrow">Pourquoi ce blog</span>
            <h2>Écrire pour comprendre</h2>
            <p>Le Carnet Clair est né d'une habitude : noter ce que nous apprenons à la fin de chaque projet. Publier ces notes nous oblige à les rendre claires — et, parfois, à découvrir que nous n'avions pas tout compris.</p>
            <p>Nous y parlons de conception, d'accessibilité et de performance, sans jargon inutile et sans promesse magique. Ce qui a marché, ce qui a raté, et pourquoi.</p>
            <p>Un article vous a servi, ou vous n'êtes pas d'accord ? <a href="/contact">Écrivez-nous</a>, nous lisons tout.</p>
        </div>
        <?= component('components/art', ['seed' => 'carnet-clair', 'label' => "Un carnet ouvert sur un bureau, illustration abstraite", 'ratio' => '4 / 3', 'glyph' => 'book']) ?>
    </div>
</section>
