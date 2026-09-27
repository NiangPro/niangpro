<?php

// Dépendances interdites restantes (voir PackageBoundariesTest). Objectif : zéro.
return [
    'cache: Cache → Tenancy (tenancy)' => true,
    'core: Container → Http\Request (http)' => true,
    'core: Container → Validation\FormRequest (http)' => true,
    'core: Event → Jobs\CallQueuedListener (queue)' => true,
    'core: Event → Queue (queue)' => true,
    'core: Http\Client → Trace (observability)' => true,
    'core: Log → Trace (observability)' => true,
    'database: Database\Model → Tenancy (tenancy)' => true,
    'observability: Metrics → Tenancy (tenancy)' => true,
    'queue: Queue → Metrics (observability)' => true,
    'queue: Queue → Tenancy (tenancy)' => true,
    'queue: Queue → Trace (observability)' => true,
    'redis: Queue\RedisQueue → Metrics (observability)' => true,
];
