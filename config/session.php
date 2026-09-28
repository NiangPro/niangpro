<?php

return [
    /*
     * Où sont stockées les sessions :
     *  - 'file' (défaut) : le stockage natif de PHP (session.save_path), propre à chaque serveur ;
     *  - 'database' : table sessions (./bin/niang migrate), partagée entre plusieurs serveurs web.
     */
    'driver' => env('SESSION_DRIVER', 'file'),

    // Minutes avant expiration du cookie ; 0 = jusqu'à la fermeture du navigateur.
    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'same_site' => env('SESSION_SAME_SITE', 'Lax'),

    /*
     * Cookie "Secure" (envoyé uniquement en HTTPS). null = détecté automatiquement depuis la
     * requête ; forcez explicitement "true" ou "false" en .env si besoin (ex: derrière un proxy
     * qui termine le HTTPS avant d'atteindre l'app).
     */
    'secure' => match (env('SESSION_SECURE_COOKIE')) {
        'true' => true,
        'false' => false,
        default => null,
    },
];
