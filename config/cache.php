<?php

return [
    /*
     * Où Cache (et RateLimiter, les compteurs de limitation de débit) stockent leurs données :
     *  - 'file' (défaut) : storage/framework/, propre à chaque serveur ;
     *  - 'database' : tables cache_entries et rate_limits (./bin/niang migrate), partagées entre
     *    plusieurs serveurs web derrière un répartiteur de charge.
     */
    'driver' => env('CACHE_DRIVER', 'file'),
];
