<?php layout('layouts.app', ['title' => 'FAQ — livraison et retours', 'active' => '/faq', 'description' => 'Délais, frais de livraison, retours et remboursements : tout ce qu\'il faut savoir avant de commander.']); ?>

<?= component('components/page-hero', ['title' => 'Livraison et retours', 'lead' => 'Tout ce qu\'il faut savoir avant et après votre commande.', 'crumbs' => []]) ?>

<section class="section">
    <div class="container">
        <?= component('components/faq', ['items' => config('site.faq'), 'openFirst' => true]) ?>
        <p class="text-center muted" style="margin-top: var(--space-6)">Une autre question ? <a href="/contact">Écrivez-nous</a>.</p>
    </div>
</section>
