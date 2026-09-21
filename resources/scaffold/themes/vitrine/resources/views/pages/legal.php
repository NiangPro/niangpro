<?php layout('layouts.app', ['title' => 'Mentions légales', 'active' => '/mentions-legales']); ?>

<?= component('components/page-hero', ['title' => 'Mentions légales', 'crumbs' => []]) ?>

<section class="section">
    <div class="container container--narrow prose">
        <p class="alert alert--info"><strong>Contenu d'exemple.</strong> Ces informations concernent une entreprise fictive : remplacez-les par les vôtres avant de mettre le site en ligne.</p>

        <h2>Éditeur du site</h2>
        <p>Atelier Lumière SARL, au capital de 10 000 €, immatriculée au RCS de Lyon sous le numéro 000 000 000.<br>
        Siège social : <?= e(config('site.contact.address')) ?>.<br>
        Directrice de la publication : Inès Marchand.<br>
        Contact : <a href="mailto:<?= e(config('site.contact.email')) ?>"><?= e(config('site.contact.email')) ?></a></p>

        <h2>Hébergement</h2>
        <p>Le site est hébergé par [nom de l'hébergeur], [adresse de l'hébergeur].</p>

        <h2>Propriété intellectuelle</h2>
        <p>L'ensemble des contenus de ce site (textes, images, visuels) est protégé par le droit d'auteur. Toute reproduction sans autorisation écrite préalable est interdite.</p>

        <h2>Responsabilité</h2>
        <p>Nous nous efforçons de fournir des informations exactes et à jour, sans pouvoir garantir l'absence d'erreur. Les liens vers des sites tiers n'engagent pas notre responsabilité.</p>
    </div>
</section>
