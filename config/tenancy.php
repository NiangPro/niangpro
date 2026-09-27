<?php

/*
 * Multi-locataire, base partagée (voir Niang\Core\Tenancy). Désactivé par défaut. Une fois activé :
 * table des locataires (./bin/niang tenancy:install), middleware IdentifyTenant sur les routes des
 * locataires, et `protected static bool $tenantScoped = true;` dans chaque modèle concerné (sa table
 * a une colonne tenant_id).
 */
return [
    'enabled' => (bool) env('TENANCY_ENABLED', false),

    /*
     * Identification du locataire :
     *  - 'route'  : paramètre de route {tenant} (sous-domaine : $router->domain('{tenant}.exemple.sn', ...),
     *               préfixe : $router->group(['prefix' => '/{tenant}'], ...)), cherché dans la colonne slug ;
     *  - 'domain' : hôte complet de la requête, cherché dans la colonne domain (domaines personnalisés) ;
     *  - 'header' : en-tête X-Tenant (API), colonne slug.
     */
    'identify_by' => env('TENANCY_IDENTIFY_BY', 'route'),
    'route_parameter' => 'tenant',

    'table' => 'tenants',
    'primary_key' => 'id',
    'slug_column' => 'slug',

    // Colonne qui désigne le locataire dans les tables des modèles par locataire.
    'column' => 'tenant_id',
];
