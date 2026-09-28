<?php

/*
 * Multi-locataire : une organisation est un locataire (table tenants). Ses pages sont sous
 * /o/{tenant}, et les modèles déclarés $tenantScoped (Project, Membership, Invitation) ne voient
 * que ses lignes. Voir Niang\Core\Tenancy.
 */
return [
    'enabled' => (bool) env('TENANCY_ENABLED', true),
    'identify_by' => env('TENANCY_IDENTIFY_BY', 'route'),
    'route_parameter' => 'tenant',
    'table' => 'tenants',
    'primary_key' => 'id',
    'slug_column' => 'slug',
    'column' => 'tenant_id',
];
