<?php layout('layouts.app', ['title' => 'FAQ', 'active' => '/faq', 'description' => 'Budget, délais, garanties : les réponses aux questions les plus fréquentes.']); ?>

<?= component('components/page-hero', [
    'title' => 'Questions fréquentes',
    'lead' => 'Budget, délais, garanties : nous avons rassemblé ce qu\'on nous demande le plus souvent.',
    'crumbs' => [],
]) ?>

<section class="section">
    <div class="container">
        <?= component('components/faq', ['items' => config('site.faq'), 'openFirst' => true]) ?>
        <p class="text-center muted" style="margin-top: var(--space-6)">Une autre question ? <a href="/contact">Écrivez-nous</a>.</p>
    </div>
</section>
