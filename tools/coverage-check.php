<?php

/**
 * Vérifie la couverture de code produite par PHPUnit (format Clover) : un seuil global, et un seuil
 * par composant critique — la roadmap (§60) le rappelle, le pourcentage global seul ne suffit pas,
 * un composant de sécurité peu testé peut se cacher derrière des vues bien couvertes.
 *
 * Les seuils sont fixés un peu sous la couverture mesurée : le but est d'empêcher une régression
 * (du code ajouté sans test), pas d'échouer pour une ligne. Relevez-les quand la couverture monte.
 *
 * Usage : vendor/bin/phpunit --coverage-clover build/clover.xml && php tools/coverage-check.php build/clover.xml
 */

const GLOBAL_MINIMUM = 78.0;

/** Composant => [seuil en %, fichiers ou dossiers (relatifs à la racine)] */
const COMPONENTS = [
    'Router' => [85.0, ['packages/http/src/Router.php', 'packages/http/src/RouteRegistration.php', 'packages/http/src/RouteCache.php']],
    'Container' => [90.0, ['packages/core/src/Container.php']],
    'Database' => [85.0, ['packages/database/src/Database/']],
    'Auth' => [90.0, [
        'packages/auth/src/Auth.php', 'packages/auth/src/ApiToken.php', 'packages/auth/src/Gate.php', 'packages/auth/src/Hash.php',
        'packages/auth/src/Totp.php', 'packages/auth/src/TwoFactor.php', 'packages/auth/src/OAuth.php', 'packages/auth/src/OAuth/',
    ]],
    'Validation' => [90.0, ['packages/http/src/Validation/']],
    'HTTP' => [80.0, ['packages/core/src/Http/', 'packages/http/src/Http/']],
    'Sécurité' => [90.0, [
        'packages/core/src/Crypt.php', 'packages/http/src/Csrf.php', 'packages/core/src/UrlSignature.php', 'packages/core/src/AppKey.php',
        'packages/http/src/Cors.php', 'packages/cache/src/RateLimiter.php', 'packages/http/src/MaintenanceMode.php', 'packages/core/src/Env.php',
    ]],
];

$file = $argv[1] ?? 'build/clover.xml';

if (!is_file($file)) {
    fwrite(STDERR, "Rapport Clover introuvable : $file\n");
    exit(2);
}

$xml = simplexml_load_file($file);

if ($xml === false) {
    fwrite(STDERR, "Rapport Clover illisible : $file\n");
    exit(2);
}

$root = str_replace('\\', '/', dirname(__DIR__)) . '/';
$files = [];

foreach ($xml->xpath('//file') ?: [] as $node) {
    $path = str_replace('\\', '/', (string) $node['name']);
    $relative = str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    $metrics = $node->metrics;
    $files[$relative] = [(int) $metrics['coveredstatements'], (int) $metrics['statements']];
}

$percent = static fn (int $covered, int $total): float => $total === 0 ? 100.0 : 100 * $covered / $total;
$failures = 0;

$line = static function (string $label, int $covered, int $total, float $minimum) use ($percent, &$failures): void {
    $value = $percent($covered, $total);
    $ok = $value >= $minimum;
    $failures += $ok ? 0 : 1;
    $padded = $label . str_repeat(' ', max(0, 10 - mb_strlen($label)));
    printf("%s %s %6.2f %%  (%d/%d lignes, minimum %.0f %%)\n", $ok ? '✓' : '✗', $padded, $value, $covered, $total, $minimum);
};

$line('Global', array_sum(array_column($files, 0)), array_sum(array_column($files, 1)), GLOBAL_MINIMUM);

foreach (COMPONENTS as $name => [$minimum, $patterns]) {
    $covered = $total = 0;

    foreach ($files as $path => [$c, $t]) {
        foreach ($patterns as $pattern) {
            if ($path === $pattern || (str_ends_with($pattern, '/') && str_starts_with($path, $pattern))) {
                $covered += $c;
                $total += $t;
                break;
            }
        }
    }

    if ($total === 0) {
        fwrite(STDERR, "Aucun fichier trouvé pour le composant $name : chemins à mettre à jour ?\n");
        exit(2);
    }

    $line($name, $covered, $total, $minimum);
}

echo $failures === 0 ? "\nCouverture suffisante.\n" : "\n$failures seuil(s) non atteint(s).\n";
exit($failures === 0 ? 0 : 1);
