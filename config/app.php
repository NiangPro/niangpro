<?php

return [
    'name' => env('APP_NAME', 'NiangPro'),
    'debug' => env('APP_DEBUG', 'true') === 'true',
    'url' => env('APP_URL', 'http://localhost:8000'),

    /*
     * Langue des textes vus par les visiteurs (lang/<langue>/*.php) : messages de validation,
     * erreurs d'upload, pages d'erreur, pagination. Changeable pour une requête avec
     * Niang\Core\Lang::setLocale(). Une clé absente est cherchée dans fallback_locale.
     */
    'locale' => env('APP_LOCALE', 'fr'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fr'),

    /*
     * Service Providers exécutés au démarrage de l'application (register() puis boot()).
     */
    'providers' => [
        App\Providers\AppServiceProvider::class,
    ],
];
