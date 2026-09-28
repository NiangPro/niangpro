<?php

return [
    /*
     * Disque de Storage : 'local' (défaut, storage/app/) ou 's3' (AWS S3, Cloudflare R2, MinIO, Wasabi,
     * Scaleway... tout service compatible S3).
     */
    'disk' => env('FILESYSTEM_DISK', 'local'),

    's3' => [
        'key' => env('AWS_ACCESS_KEY_ID', ''),
        'secret' => env('AWS_SECRET_ACCESS_KEY', ''),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET', ''),
        // Service compatible : https://<compte>.r2.cloudflarestorage.com, http://127.0.0.1:9000 (MinIO)...
        'endpoint' => env('AWS_ENDPOINT', ''),
        // true : https://endpoint/bucket/fichier ; false : https://bucket.endpoint/fichier. Non défini :
        // style chemin avec un AWS_ENDPOINT (MinIO, R2...), bucket.hôte pour AWS.
        'path_style' => env('AWS_USE_PATH_STYLE_ENDPOINT') === null ? null : env('AWS_USE_PATH_STYLE_ENDPOINT') === 'true',
        // URL publique de base (CDN, domaine du bucket) pour Storage::url() ; vide : URL S3 directe.
        'url' => env('AWS_URL', ''),
    ],
];
