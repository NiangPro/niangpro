<?php

/*
 * Identité de l'API, renvoyée par GET / et reprise dans la description OpenAPI (./bin/niang openapi).
 */

return [
    'name' => env('SITE_NAME', 'Mon API'),
    'version' => '1.0.0',
    'description' => 'API REST JSON : comptes, jetons d\'accès et notes personnelles.',

    // Nombre d'éléments par page sur les listes (?page=2).
    'per_page' => 20,
];
