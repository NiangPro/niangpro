<?php

return [
    /*
     * Niveau minimal écrit dans storage/logs/ : 'debug' (tout, le défaut) ... 'emergency'. En
     * production, 'info' ou 'warning' évitent de remplir le disque de messages de débogage.
     */
    'level' => env('LOG_LEVEL', 'debug'),

    /*
     * Nombre de jours de fichiers conservés (un fichier par jour) ; les plus anciens sont supprimés
     * au premier message de chaque journée. 0 : ne jamais supprimer.
     */
    'days' => (int) env('LOG_DAYS', 14),
];
