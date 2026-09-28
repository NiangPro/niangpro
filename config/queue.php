<?php

return [
    /*
     * Où Queue garde les jobs différés (traités par `niang queue:work`) :
     *  - 'file' (défaut) : storage/framework/queue/, propre à chaque serveur ;
     *  - 'database' : tables jobs et failed_jobs (./bin/niang migrate), partagées entre plusieurs
     *    serveurs — plusieurs workers, sur plusieurs machines, se répartissent les jobs ;
     *  - 'sync' : exécution immédiate, sans file (développement, tests).
     */
    'driver' => env('QUEUE_DRIVER', 'file'),

    /*
     * Pilote database : un job réservé par un worker qui n'a pas terminé au bout de ce délai
     * (worker arrêté, serveur redémarré) est rendu à la file. Doit dépasser la durée du plus long
     * job, sinon il serait exécuté deux fois.
     */
    'retry_after' => (int) env('QUEUE_RETRY_AFTER', 600),
];
