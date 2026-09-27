<?php

/*
 * Paquets du framework (roadmap §46, ADR 0012) : un dossier packages/<nom>/ par paquet, namespace
 * Niang\Core\ pour tous. Une classe appartient au paquet de son dossier.
 *  - description : reprise dans le composer.json du paquet ;
 *  - requires    : paquets NiangPro obligatoires (« require ») ;
 *  - suggests    : paquets NiangPro optionnels, pour un pilote ou un pont (« suggest ») ;
 *  - psr         : interfaces PSR utilisées (« require »).
 * PackageBoundariesTest vérifie qu'aucune classe n'utilise un paquet non déclaré ici, et que chaque
 * composer.json correspond exactement à cette table.
 */
return [
    'core' => [
        'description' => 'Conteneur, configuration, environnement, événements, logs, langues, chiffrement, client HTTP sortant.',
        'requires' => [],
        'psr' => ['psr/container', 'psr/event-dispatcher', 'psr/log'],
    ],
    'database' => ['description' => 'Query Builder, modèles, migrations, schéma, pagination (SQLite, MySQL, PostgreSQL).', 'requires' => ['core']],
    'redis' => [
        'description' => 'Client Redis sans extension, pilotes de session et de file d\'attente.',
        'requires' => ['core'],
        'suggests' => ['http', 'queue'],
    ],
    'http' => [
        'description' => 'Routeur, requêtes et réponses, sessions, cookies, CSRF, CORS, vues, validation.',
        'requires' => ['core'],
        'suggests' => ['database', 'redis', 'storage'],
        'psr' => ['psr/http-message', 'psr/http-server-middleware'],
    ],
    'cache' => ['description' => 'Cache et limitation de débit (fichier, mémoire, base, Redis).', 'requires' => ['core'], 'suggests' => ['database', 'redis']],
    'queue' => ['description' => 'File d\'attente, jobs et tâches planifiées.', 'requires' => ['core'], 'suggests' => ['database', 'redis']],
    'mail' => ['description' => 'Emails (SMTP) et notifications.', 'requires' => ['core', 'queue'], 'suggests' => ['database', 'auth']],
    'storage' => ['description' => 'Stockage de fichiers : disque local et S3.', 'requires' => ['core'], 'suggests' => ['http']],
    'auth' => ['description' => 'Authentification, double authentification, OAuth, jetons d\'API, autorisations.', 'requires' => ['core', 'http', 'database']],
    'tenancy' => ['description' => 'Multi-locataire en base partagée.', 'requires' => ['core', 'database', 'http']],
    'observability' => ['description' => 'Identifiant de requête, traces W3C, métriques Prometheus.', 'requires' => ['core', 'http', 'cache']],
    'openapi' => ['description' => 'Description OpenAPI générée à partir des routes.', 'requires' => ['core', 'http']],
    'debug' => ['description' => 'Barre de debug en développement.', 'requires' => ['core', 'http'], 'suggests' => ['database']],
    'foundation' => [
        'description' => 'Application, gestion des erreurs, CLI et outils de test : assemble tous les paquets.',
        'requires' => ['core', 'database', 'redis', 'http', 'cache', 'queue', 'mail', 'storage', 'auth', 'tenancy', 'observability', 'openapi', 'debug'],
        'psr' => ['psr/container', 'psr/event-dispatcher', 'psr/log'],
    ],
];
