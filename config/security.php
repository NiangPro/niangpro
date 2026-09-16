<?php

return [
    /*
     * Appliqués à toutes les réponses par Application::applySecurityHeaders() — sûr par défaut
     * plutôt que de compter sur chaque route pour y penser. Ajoutez, retirez ou ajustez librement.
     */
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:",
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    ],
];
