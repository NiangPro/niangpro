<?php

return [
    'name' => env('APP_NAME', 'NiangPro'),
    'debug' => env('APP_DEBUG', 'true') === 'true',
    'url' => env('APP_URL', 'http://localhost:8000'),

    /*
     * Service Providers exécutés au démarrage de l'application (register() puis boot()).
     */
    'providers' => [
        App\Providers\AppServiceProvider::class,
    ],
];
