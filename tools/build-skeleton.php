<?php

/**
 * Construit le squelette d'application niangpro/niangpro (roadmap §46, ADR 0013) à partir de ce dépôt :
 * l'application de démonstration (app/, config/, routes/, resources/ dont les thèmes, public/...), ses
 * tests Feature et Security, et skeleton/composer.json, qui demande niangpro/framework au lieu d'en
 * embarquer le code. Ce dépôt reste le seul endroit où l'on modifie l'application de démonstration.
 *
 * Usage : php tools/build-skeleton.php <dossier de destination>
 * Utilisé par la CI (création d'un projet à partir du squelette) et par la publication (split.yml).
 */

$root = dirname(__DIR__);
$target = $argv[1] ?? null;

if ($target === null || $target === '') {
    fwrite(STDERR, "Usage : php tools/build-skeleton.php <dossier de destination>\n");
    exit(2);
}

if (file_exists($target) && (glob("$target/*") ?: []) !== []) {
    fwrite(STDERR, "$target existe et n'est pas vide.\n");
    exit(2);
}

$directories = ['app', 'bin', 'config', 'database', 'docker', 'lang', 'public', 'resources', 'routes'];
$files = ['.env.example', '.env.testing', '.gitignore', '.dockerignore', 'compose.yaml', 'preload.php', 'LICENSE'];
// Tests de l'application ; ceux du framework (Unit, Database, installation des thèmes) restent ici.
$tests = ['tests/bootstrap.php', 'tests/Feature', 'tests/Security', 'tests/Support/FakeHttpServer.php',
    'tests/Support/FakeSmtpServer.php', 'tests/Support/UsesTempDirectory.php', 'tests/Support/fake-http-server.php',
    'tests/Support/fake-smtp-server.php'];
$excluded = ['tests/Feature/ThemeInstallationTest.php'];

$copy = static function (string $from, string $to) use (&$copy, $excluded, $root): void {
    $relative = substr($from, strlen($root) + 1);

    if (in_array($relative, $excluded, true)) {
        return;
    }

    if (is_dir($from)) {
        @mkdir($to, 0755, true);

        foreach (scandir($from) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $copy("$from/$entry", "$to/$entry");
            }
        }

        return;
    }

    @mkdir(dirname($to), 0755, true);
    copy($from, $to);

    if (is_executable($from)) {
        chmod($to, 0755);
    }
};

foreach ([...$directories, ...$files, ...$tests] as $path) {
    if (file_exists("$root/$path")) {
        $copy("$root/$path", "$target/$path");
    }
}

// public/openapi.json, build/... : fichiers générés, jamais dans le squelette.
foreach (['public/openapi.json', 'public/hot'] as $generated) {
    @unlink("$target/$generated");
}

foreach (['storage/logs', 'storage/framework', 'storage/app'] as $directory) {
    @mkdir("$target/$directory", 0755, true);
    touch("$target/$directory/.gitkeep");
}

// .gitattributes propre au squelette : celui du framework exclut app/, routes/... de son archive.
copy("$root/skeleton/.gitattributes", "$target/.gitattributes");
copy("$root/skeleton/composer.json", "$target/composer.json");
copy("$root/skeleton/phpunit.xml", "$target/phpunit.xml");
copy("$root/skeleton/README.md", "$target/README.md");

echo "Squelette construit dans $target\n";
