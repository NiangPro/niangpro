<?php layout('layouts.app', ['title' => 'Mentions légales', 'active' => '/mentions-legales']); ?>

<?= component('components/page-hero', ['title' => 'Mentions légales', 'crumbs' => []]) ?>

<section class="section">
    <div class="container container--narrow prose">
        <p class="alert alert--info"><strong>Contenu d'exemple.</strong> Ces informations concernent une entreprise fictive : remplacez-les par les vôtres avant la mise en ligne.</p>

        <h2>Éditeur du site</h2>
        <p>Maison Nomade SAS, au capital de 20 000 €, immatriculée au RCS de Nantes sous le numéro 000 000 000.<br>
        Siège social : <?= e(config('site.contact.address')) ?>.<br>
        Contact : <a href="mailto:<?= e(config('site.contact.email')) ?>"><?= e(config('site.contact.email')) ?></a></p>

        <h2>Hébergement</h2>
        <p>Le site est hébergé par [nom de l'hébergeur], [adresse de l'hébergeur].</p>

        <h2>Propriété intellectuelle</h2>
        <p>Les textes, visuels et marques de ce site sont protégés. Toute reproduction sans autorisation écrite préalable est interdite.</p>

        <h2>Cookies</h2>
        <p>Le site utilise uniquement un cookie de session technique, nécessaire au fonctionnement du panier et de la connexion. Aucun traceur publicitaire n'est déposé.</p>
    </div>
</section>
