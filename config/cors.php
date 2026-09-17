<?php

return [
    /*
     * '*' autorise toute origine (pratique en développement) ; en production, listez les
     * origines exactes autorisées, ex: ['https://app.example.com'].
     */
    'allowed_origins' => ['*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],

    /* En-têtes de la réponse exposés au JS du navigateur (au-delà de la liste "safe" par défaut). */
    'exposed_headers' => [],

    /* true seulement avec des origines explicites : le navigateur rejette Access-Control-Allow-Origin: '*' + credentials. */
    'supports_credentials' => false,

    /* Durée en secondes pendant laquelle le navigateur peut mettre en cache la réponse du préflight OPTIONS. */
    'max_age' => 0,
];
