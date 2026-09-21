<?php layout('layouts.app', ['title' => 'Politique de confidentialité', 'active' => '/politique-de-confidentialite']); ?>

<?= component('components/page-hero', ['title' => 'Politique de confidentialité', 'crumbs' => []]) ?>

<section class="section">
    <div class="container container--narrow prose">
        <p class="alert alert--info"><strong>Contenu d'exemple.</strong> À adapter à votre activité et à vos outils réels, idéalement avec un conseil juridique.</p>

        <h2>Données collectées</h2>
        <p>Lorsque vous utilisez le formulaire de contact, nous recueillons votre nom, votre adresse email et le contenu de votre message, uniquement pour répondre à votre demande.</p>

        <h2>Durée de conservation</h2>
        <p>Ces données sont conservées au plus 3 ans après notre dernier échange, puis supprimées.</p>

        <h2>Vos droits</h2>
        <p>Conformément au RGPD, vous disposez d'un droit d'accès, de rectification, d'effacement et d'opposition. Pour l'exercer, écrivez à <a href="mailto:<?= e(config('site.contact.email')) ?>"><?= e(config('site.contact.email')) ?></a>.</p>

        <h2>Cookies</h2>
        <p>Ce site n'utilise qu'un cookie de session technique, indispensable au fonctionnement du formulaire de contact. Aucun traceur publicitaire ou statistique n'est déposé.</p>
    </div>
</section>
