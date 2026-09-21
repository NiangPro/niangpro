<?php

require __DIR__ . '/../vendor/autoload.php';

/*
 * Charge .env.testing avant tout le reste : Env::load() ne s'exécute qu'une fois par process, et
 * les tests de Commander (qui l'appellent avec le .env réel dans leur constructeur) passaient
 * avant Application, ce qui faisait taper le reste de la suite dans la base de développement locale.
 */
\Niang\Core\Env::load(dirname(__DIR__) . '/.env.testing');

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
