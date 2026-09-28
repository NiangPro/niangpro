<?php

/*
 * Connexion avec Google ou GitHub (Niang\Core\OAuth). Un fournisseur sans client_id n'est pas proposé.
 * URL de retour à déclarer chez le fournisseur : APP_URL/auth/google/callback (ou github), sauf si
 * GOOGLE_REDIRECT_URI / GITHUB_REDIRECT_URI la remplacent.
 */
return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect' => env('GOOGLE_REDIRECT_URI', ''),
    ],
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID', ''),
        'client_secret' => env('GITHUB_CLIENT_SECRET', ''),
        'redirect' => env('GITHUB_REDIRECT_URI', ''),
    ],
];
