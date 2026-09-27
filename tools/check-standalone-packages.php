<?php

/**
 * Roadmap §46 : chaque paquet de packages/ s'installe seul. Pour chacun, crée un projet Composer vierge
 * qui ne demande que ce paquet (dépôt « path » vers packages/*), installe, puis charge toutes ses
 * classes : une classe qui hérite ou implémente une classe d'un paquet absent échoue au chargement.
 *
 * Usage : php tools/check-standalone-packages.php [paquet...]   (tous par défaut)
 * Exécuté en CI (job « Paquets installés seuls »). Nécessite composer dans le PATH.
 */

$root = dirname(__DIR__);
$all = array_map('basename', glob("$root/packages/*", GLOB_ONLYDIR) ?: []);
$packages = array_slice($argv, 1) ?: $all;
$versions = [];

foreach ($all as $package) {
    $versions["niangpro/$package"] = 'dev-main';
}

// Dépendances de développement nécessaires pour charger certaines classes (outils de test).
$extra = ['foundation' => ['phpunit/phpunit' => '^10.0']];
$failures = 0;

foreach ($packages as $package) {
    $dir = sys_get_temp_dir() . '/niangpro-standalone-' . $package . '-' . bin2hex(random_bytes(4));
    mkdir($dir, 0755, true);

    file_put_contents("$dir/composer.json", json_encode([
        'name' => 'essai/standalone',
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
        'repositories' => [['type' => 'path', 'url' => "$root/packages/*", 'options' => ['symlink' => false, 'versions' => $versions]]],
        'require' => ["niangpro/$package" => 'dev-main', ...($extra[$package] ?? [])],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    exec('cd ' . escapeshellarg($dir) . ' && composer install --no-interaction --no-progress --quiet 2>&1', $output, $code);

    if ($code !== 0) {
        echo "✗ $package : installation impossible\n  " . implode("\n  ", $output) . "\n";
        $failures++;
        continue;
    }

    $script = <<<'PHP'
        <?php
        require $argv[1] . '/vendor/autoload.php';
        $src = $argv[1] . '/vendor/niangpro/' . $argv[2] . '/src';
        $count = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)) as $file) {
            $relative = substr($file->getPathname(), strlen($src) + 1, -4);
            if ($file->getExtension() !== 'php' || $relative === 'helpers') {
                continue;
            }
            $class = 'Niang\\Core\\' . str_replace(['/', '\\'], '\\', $relative);
            if (!class_exists($class) && !interface_exists($class) && !trait_exists($class) && !enum_exists($class)) {
                fwrite(STDERR, "introuvable : $class\n");
                exit(1);
            }
            $count++;
        }
        echo $count;
        PHP;
    file_put_contents("$dir/check.php", $script);
    $installed = implode(', ', array_map('basename', glob("$dir/vendor/niangpro/*") ?: []));
    $result = [];
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$dir/check.php") . ' ' . escapeshellarg($dir) . ' ' . escapeshellarg($package) . ' 2>&1', $result, $code);

    if ($code !== 0) {
        echo "✗ $package : " . implode("\n  ", $result) . "\n";
        $failures++;
    } else {
        echo "✓ $package : " . end($result) . " classes chargées (installés : $installed)\n";
    }
}

exit($failures > 0 ? 1 : 0);
