<?php

// Dépendances interdites restantes (voir PackageBoundariesTest). Objectif : zéro.
return [
    'cache: Cache → Tenancy (tenancy)' => true,
    'core: Container → Http\Request (http)' => true,
    'core: Container → Validation\FormRequest (http)' => true,
    'core: Event → Contracts\ShouldQueue (queue)' => true,
    'core: Event → Jobs\CallQueuedListener (queue)' => true,
    'core: Event → Queue (queue)' => true,
    'core: Events\ApplicationBooted → Application (framework)' => true,
    'core: Http\Client → Trace (observability)' => true,
    'core: Log → Trace (observability)' => true,
    'core: ServiceProvider → Application (framework)' => true,
    'database: Database\Model → Tenancy (tenancy)' => true,
    'database: Database\QueryBuilder → Exceptions\NotFoundException (http)' => true,
    'debug: DebugToolbar → Application (framework)' => true,
    'http: Controller → AuthorizationException (auth)' => true,
    'http: Controller → Gate (auth)' => true,
    'http: Http\UploadedFile → Storage (storage)' => true,
    'http: Validation\FormRequest → AuthorizationException (auth)' => true,
    'observability: Metrics → Tenancy (tenancy)' => true,
    'queue: Queue → Metrics (observability)' => true,
    'queue: Queue → Tenancy (tenancy)' => true,
    'queue: Queue → Trace (observability)' => true,
    'redis: Queue\RedisQueue → Metrics (observability)' => true,
];
