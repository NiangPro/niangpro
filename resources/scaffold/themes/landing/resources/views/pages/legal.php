<?php layout('layouts.app', ['title' => 'Mentions légales']); ?>

<?= component('components/page-hero', ['title' => 'Mentions légales', 'crumbs' => []]) ?>

<section class="section">
    <div class="container container--narrow prose">
        <p class="alert alert--info"><strong>Contenu d'exemple.</strong> Ces informations concernent une entreprise fictive : remplacez-les par les vôtres avant la mise en ligne.</p>

        <h2>Éditeur du site</h2>
        <p>Boussole SAS, au capital de 15 000 €, immatriculée au RCS de Bordeaux sous le numéro 000 000 000.<br>
        Siège social : <?= e(config('site.contact.address')) ?>.<br>
        Contact : <a href="mailto:<?= e(config('site.contact.email')) ?>"><?= e(config('site.contact.email')) ?></a></p>

        <h2>Hébergement</h2>
        <p>Le site est hébergé par [nom de l'hébergeur], [adresse de l'hébergeur].</p>

        <h2>Données personnelles</h2>
        <p>Cette page ne collecte aucune donnée personnelle et n'utilise aucun cookie de suivi. Les données saisies lors de l'inscription au service sont traitées conformément à la politique de confidentialité du produit.</p>

        <h2>Propriété intellectuelle</h2>
        <p>Les textes, visuels et marques de ce site sont protégés. Toute reproduction sans autorisation écrite préalable est interdite.</p>
    </div>
</section>
