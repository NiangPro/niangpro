<?php

namespace Tests\Support;

use Niang\Core\Console\ProjectScaffolder;

/**
 * Une copie jetable du projet dans laquelle un thème est réellement installé, puis exercée dans un
 * process PHPUnit à part — exactement ce que vivra quelqu'un qui vient de créer un projet.
 *
 * Un test en process ne peut pas le faire : base_path() (donc le dossier des vues) est figé sur le
 * vrai projet au chargement de src/helpers.php. La copie a donc son propre src/, et son vendor/ est
 * reconstitué à moindre coût : seul vendor/composer/ est copié (ses fichiers d'autoload se repèrent
 * via leur propre dossier, donc pointent vers le src/ et l'app/ de la copie), tous les autres
 * paquets sont des liens vers ceux du vrai vendor/.
 */
class StagedProject
{
    use UsesTempDirectory;

    private const COPIED = ['src', 'app', 'config', 'database', 'resources', 'routes', 'public', 'tests', 'bin'];
    private const COPIED_FILES = ['composer.json', 'phpunit.xml', '.env.testing', '.env.example'];

    public readonly string $path;

    private function __construct()
    {
        $this->path = $this->makeTempDirectory();
    }

    /** @param string $type slug d'un thème de resources/scaffold/themes/ ; « minimal » laisse la copie intacte */
    public static function withTheme(string $type): self
    {
        $root = dirname(__DIR__, 2);
        $project = new self();

        foreach (self::COPIED as $directory) {
            self::copyDirectory("$root/$directory", "{$project->path}/$directory");
        }

        foreach (self::COPIED_FILES as $file) {
            copy("$root/$file", "{$project->path}/$file");
        }

        foreach (['storage/logs', 'storage/framework'] as $directory) {
            mkdir("{$project->path}/$directory", 0755, true);
        }

        $project->linkVendor("$root/vendor");

        // Le script vendor/phpunit/phpunit/phpunit chercherait son autoloader à côté de son vrai
        // emplacement (via le lien), donc celui du vrai projet : ce lanceur impose celui de la copie.
        file_put_contents("{$project->path}/phpunit-runner.php", <<<'PHP'
        <?php

        define('PHPUNIT_COMPOSER_INSTALL', __DIR__ . '/vendor/autoload.php');
        require PHPUNIT_COMPOSER_INSTALL;

        exit((new PHPUnit\TextUI\Application())->run($_SERVER['argv']));

        PHP);

        (new ProjectScaffolder("{$project->path}/resources/scaffold"))->install($type, $project->path);

        return $project;
    }

    /**
     * @param list<string> $arguments arguments de PHPUnit (ex : ['--testsuite', 'Feature'])
     * @return array{0: int, 1: string} code de sortie, sortie standard et d'erreur mêlées
     */
    public function phpunit(array $arguments): array
    {
        return $this->php(['phpunit-runner.php', ...$arguments]);
    }

    /**
     * @param list<string> $arguments arguments de la commande PHP (ex : ['bin/niang', 'route:list'])
     * @return array{0: int, 1: string} code de sortie, sortie standard et d'erreur mêlées
     */
    public function php(array $arguments): array
    {
        $command = array_merge([PHP_BINARY], $arguments);

        // Environnement minimal et explicite : celui du process courant contient déjà les valeurs de
        // .env.testing (Env::load fait des putenv), qui masqueraient ce que la copie doit charger seule.
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->path, [
            'PATH' => (string) getenv('PATH'),
            'HOME' => (string) getenv('HOME'),
            'APP_ENV' => 'testing',
        ]);

        if (!is_resource($process)) {
            throw new \RuntimeException('Impossible de lancer ' . PHP_BINARY);
        }

        $output = (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);

        return [proc_close($process), $output];
    }

    public function destroy(): void
    {
        $this->removeTempDirectories();
    }

    private function linkVendor(string $realVendor): void
    {
        $vendor = $this->path . '/vendor';
        mkdir($vendor, 0755, true);
        copy("$realVendor/autoload.php", "$vendor/autoload.php");
        self::copyDirectory("$realVendor/composer", "$vendor/composer");

        foreach (scandir($realVendor) ?: [] as $entry) {
            if (in_array($entry, ['.', '..', 'composer', 'autoload.php'], true)) {
                continue;
            }

            symlink("$realVendor/$entry", "$vendor/$entry");
        }
    }

    private static function copyDirectory(string $source, string $target): void
    {
        mkdir($target, 0755, true);

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $destination = $target . '/' . substr($item->getPathname(), strlen($source) + 1);

            $item->isDir() ? @mkdir($destination, 0755, true) : copy($item->getPathname(), $destination);
        }
    }
}
