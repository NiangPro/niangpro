<?php

/*
 * Identité de l'application. Remplacez ces valeurs par les vôtres ; le nom peut aussi se régler avec
 * SITE_NAME dans .env. Le tableau de bord (resources/views/pages/dashboard.php) est le point de départ
 * de votre produit.
 */

return [
    'name' => env('SITE_NAME', 'Mon application'),
    'tagline' => 'Votre produit, prêt à accueillir ses premiers utilisateurs',
    'description' => 'Inscription, connexion, double authentification, mot de passe oublié et espace membre, prêts à l\'emploi.',

    // Navigation des visiteurs ; un membre connecté voit « Tableau de bord » et « Mon compte ».
    'nav' => [
        ['label' => 'Accueil', 'href' => '/'],
    ],

    'features' => [
        ['icon' => 'lock', 'title' => 'Connexion sûre', 'text' => 'Mots de passe hachés, protection contre la force brute, « se souvenir de moi » révocable.'],
        ['icon' => 'shield', 'title' => 'Double authentification', 'text' => 'Codes TOTP (Google Authenticator, Aegis...) et codes de secours.'],
        ['icon' => 'mail', 'title' => 'Emails de compte', 'text' => 'Vérification d\'adresse et lien de réinitialisation signés, à durée limitée.'],
    ],

    'footer' => [],
    'legal' => [],
];
