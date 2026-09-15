<?php

require __DIR__ . '/../vendor/autoload.php';

/*
 * Isolation complète : le cache applicatif et le rate limiting vivent sur fichier
 * (storage/framework/), en dehors de la base de données — repartir d'un état propre à chaque
 * exécution de la suite évite qu'un test d'un run précédent (ex: rate limiting sur /login)
 * fasse échouer un run suivant.
 */
$frameworkDir = dirname(__DIR__) . '/storage/framework';

if (is_dir($frameworkDir)) {
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($frameworkDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
}
