<?php

/*
 * Identité du produit. Le nom peut aussi se régler avec SITE_NAME dans .env.
 */

return [
    'name' => env('SITE_NAME', 'Mon SaaS'),
    'tagline' => 'Un espace de travail pour chaque équipe',
    'description' => 'Organisations, membres et rôles, invitations, abonnements : la base d\'un logiciel en ligne.',

    'nav' => [
        ['label' => 'Accueil', 'href' => '/'],
        ['label' => 'Tarifs', 'href' => '/#tarifs'],
    ],

    'features' => [
        ['icon' => 'users', 'title' => 'Organisations', 'text' => 'Chaque équipe a son espace ; ses données ne sont jamais visibles des autres.'],
        ['icon' => 'shield', 'title' => 'Rôles', 'text' => 'Propriétaire, administrateur, membre : chacun voit et fait ce qui lui revient.'],
        ['icon' => 'credit-card', 'title' => 'Abonnements', 'text' => 'Plans et limites prêts ; branchez votre prestataire de paiement.'],
    ],

    // Suppression d'un compte (module account) : refusée pour le dernier propriétaire d'une
    // organisation qui a d'autres membres ; sinon, ses appartenances sont retirées.
    'before_account_deletion' => 'App\\Support\\Team::beforeAccountDeletion',

    'footer' => [],
    'legal' => [],
];
