<?php

/*
 * Découpage cible de src/Core en paquets (roadmap §46, ADR 0012). Chaque classe appartient au premier
 * paquet dont une expression régulière correspond à son nom (sans le préfixe Niang\Core\).
 *  - requires : dépendances obligatoires (« require » de son composer.json) ;
 *  - suggests : dépendances optionnelles, pour un pilote ou un pont (« suggest ») : autorisées ;
 * Tout le reste est une dépendance interdite, comptée par PackageBoundariesTest.
 */
return [
    'framework' => [
        'match' => ['^Application$', '^Exceptions\\\\Handler$', '^Console\\\\', '^Testing\\\\', '^HealthCheck$'],
        'requires' => ['*'],
    ],
    'http' => [
        'match' => [
            '^(Router|RouteRegistration|RouteCache|Middleware|Controller|Cookie|Session|ArraySessionHandler|Csrf|Cors|MaintenanceMode|View|ViteAssets)$',
            '^Http\\\\(?!Client$)', '^Validation\\\\', '^Events\\\\(RequestReceived|RouteMatched|ResponsePrepared|RequestTerminated)$',
            '^Exceptions\\\\(HttpException|NotFoundException)$',
        ],
        'requires' => ['core'],
        'suggests' => ['database', 'redis'],
    ],
    'database' => [
        'match' => ['^Database\\\\', '^DatabaseSessionHandler$', '^Exceptions\\\\(DatabaseException|MassAssignmentException)$'],
        'requires' => ['core'],
    ],
    'redis' => [
        'match' => ['^Redis$', '^Redis\\\\', '^RedisSessionHandler$', '^Queue\\\\RedisQueue$', '^Exceptions\\\\RedisException$'],
        'requires' => ['core'],
        'suggests' => ['http', 'queue'],
    ],
    'cache' => [
        'match' => ['^(Cache|RateLimiter)$'],
        'requires' => ['core'],
        'suggests' => ['database', 'redis'],
    ],
    'queue' => [
        'match' => ['^(Queue|Job)$', '^Jobs\\\\CallQueuedListener$', '^Queue\\\\', '^Scheduling\\\\', '^Contracts\\\\ShouldQueue$'],
        'requires' => ['core'],
        'suggests' => ['database', 'redis'],
    ],
    'mail' => [
        'match' => ['^(Mail|Mailable|PendingMail|MailAttachment|SmtpTransport|Notification)$', '^Notifications\\\\', '^Jobs\\\\Send', '^Contracts\\\\NotificationChannel$', '^Exceptions\\\\(MailException|NotificationException)$'],
        'requires' => ['core', 'queue'],
        'suggests' => ['database', 'auth'],
    ],
    'storage' => [
        'match' => ['^Storage'],
        'requires' => ['core'],
        'suggests' => ['http'],
    ],
    'auth' => [
        'match' => ['^(Auth|Gate|Permission|ApiToken|TwoFactor|Totp|OAuth|AuthorizationException|Hash)$', '^OAuth\\\\', '^Exceptions\\\\(AuthenticationException|OAuthException)$'],
        'requires' => ['core', 'http', 'database'],
    ],
    'tenancy' => [
        'match' => ['^Tenancy$', '^Exceptions\\\\TenancyException$'],
        'requires' => ['core', 'database', 'http'],
    ],
    'observability' => [
        'match' => ['^(Metrics|Trace)$'],
        'requires' => ['core', 'http', 'cache'],
    ],
    'openapi' => ['match' => ['^OpenApi$'], 'requires' => ['core', 'http']],
    'debug' => ['match' => ['^DebugToolbar$'], 'requires' => ['core', 'http'], 'suggests' => ['database']],
    // Tout le reste : conteneur, configuration, événements, logs, langues, chiffrement, client HTTP sortant.
    'core' => ['match' => ['.*'], 'requires' => []],
];
