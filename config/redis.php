<?php

/*
 * Serveur Redis (ou Valkey, KeyDB, Upstash...) utilisé par CACHE_DRIVER, SESSION_DRIVER et QUEUE_DRIVER
 * valant 'redis'. Hôte en « tls://hote » pour une connexion chiffrée (services gérés).
 */
return [
    'host' => env('REDIS_HOST', '127.0.0.1'),
    'port' => (int) env('REDIS_PORT', 6379),
    'username' => env('REDIS_USERNAME', ''),
    'password' => env('REDIS_PASSWORD', ''),
    'database' => (int) env('REDIS_DB', 0),
    // Préfixe de toutes les clés : plusieurs applications peuvent partager un même serveur.
    'prefix' => env('REDIS_PREFIX', 'niangpro:'),
    'timeout' => (float) env('REDIS_TIMEOUT', 2),
];
