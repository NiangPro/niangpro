<?php

/**
 * Préchargement OPcache des classes du framework (packages/<nom>/src/, récursivement) : à brancher via
 * `opcache.preload=/chemin/vers/preload.php` dans le php.ini du serveur — un réglage de
 * déploiement, jamais quelque chose que `niang optimize` (une requête CLI ponctuelle) peut
 * activer lui-même. Voir docs/ROADMAP_TECHNIQUE.md, section OPcache / production, pour le
 * détail de la configuration serveur.
 *
 * La liste est construite par glob() plutôt que codée en dur : chaque fichier qu'elle contient
 * existe par construction, il n'y a rien à vérifier à la main de ce côté-là. Seule la syntaxe de
 * ce fichier est vérifiée automatiquement (php -l, en CI) — le comportement réel d'OPcache ne se
 * prête pas à un test PHPUnit et n'est donc pas couvert automatiquement.
 */

if (!function_exists('opcache_compile_file')) {
    return;
}

// packages/ : dépôt du framework ; vendor/niangpro/framework/packages/ : application (framework installé
// avec Composer).
foreach ([__DIR__ . '/packages', __DIR__ . '/vendor/niangpro/framework/packages'] as $packages) {
    if (!is_dir($packages)) {
        continue;
    }

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($packages, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        if ($file->getExtension() === 'php') {
            opcache_compile_file($file->getPathname());
        }
    }
}
