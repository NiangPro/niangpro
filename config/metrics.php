<?php

/*
 * Métriques Prometheus : GET /metrics, avec l'en-tête « Authorization: Bearer <METRICS_TOKEN> ».
 * Sans jeton, la route répond 404 (jamais exposée par défaut). Les compteurs sont stockés dans le
 * cache (CACHE_DRIVER) : avec plusieurs serveurs, utilisez database ou redis pour les partager.
 */
return [
    'enabled' => (bool) env('METRICS_ENABLED', false),
    'token' => env('METRICS_TOKEN', ''),
    'path' => env('METRICS_PATH', '/metrics'),

    /*
     * Compteurs de l'application, incrémentés par Metrics::increment('nom') et exposés sous
     * « app_<nom> ». Nom => description. Exemple : 'orders_created_total' => 'Commandes créées'.
     */
    'counters' => [],
];
