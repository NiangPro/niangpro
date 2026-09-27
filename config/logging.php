<?php

return [
    /*
     * Destination : 'daily' (défaut, storage/logs/AAAA-MM-JJ.log), 'single' (storage/logs/niangpro.log),
     * 'errorlog' (error_log de PHP : journal du serveur web ou de PHP-FPM), 'syslog' (journal système,
     * identifiant ci-dessous) ou 'stderr' (conteneurs Docker, lus par `docker compose logs`).
     */
    'channel' => env('LOG_CHANNEL', 'daily'),
    'syslog_ident' => env('LOG_SYSLOG_IDENT', 'niangpro'),

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
