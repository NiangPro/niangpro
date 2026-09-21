<?php layout('layouts.app', ['title' => 'Conditions générales de vente', 'active' => '/cgv']); ?>

<?= component('components/page-hero', ['title' => 'Conditions générales de vente', 'crumbs' => []]) ?>

<section class="section">
    <div class="container container--narrow prose">
        <p class="alert alert--info"><strong>Contenu d'exemple.</strong> Ces conditions concernent une boutique fictive : faites-les relire et adapter à votre activité avant toute vente réelle.</p>

        <h2>1. Objet</h2>
        <p>Les présentes conditions régissent les ventes conclues sur ce site entre Maison Nomade SAS et toute personne effectuant un achat (« le client »).</p>

        <h2>2. Prix</h2>
        <p>Les prix sont indiqués en euros, toutes taxes comprises, hors frais de livraison précisés avant la validation de la commande. Maison Nomade peut modifier ses prix à tout moment ; le prix applicable est celui affiché au moment de la commande.</p>

        <h2>3. Commande et paiement</h2>
        <p>La commande est ferme après validation du paiement. Le paiement est exigible immédiatement, par les moyens proposés sur le site.</p>

        <h2>4. Livraison</h2>
        <p>Les délais indiqués sont des estimations. Les articles sont livrés à l'adresse renseignée par le client. Le risque de perte ou de détérioration est transféré au client à la remise du colis.</p>

        <h2>5. Droit de rétractation</h2>
        <p>Le client dispose de 30 jours à compter de la réception pour retourner un article, sans avoir à justifier sa décision. Les frais de retour sont pris en charge par Maison Nomade. Le remboursement intervient sous 14 jours.</p>

        <h2>6. Garanties</h2>
        <p>Les produits bénéficient de la garantie légale de conformité et de la garantie contre les vices cachés, dans les conditions prévues par le Code de la consommation.</p>

        <h2>7. Données personnelles</h2>
        <p>Les données collectées lors de la commande servent uniquement à son traitement et à la relation client. Vous pouvez exercer vos droits d'accès, de rectification et d'effacement en écrivant à <a href="mailto:<?= e(config('site.contact.email')) ?>"><?= e(config('site.contact.email')) ?></a>.</p>
    </div>
</section>
