<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Roadmap §46 (ADR 0012) : src/Core doit pouvoir être découpé en paquets sans dépendance circulaire.
 * Aucune classe ne cite une classe d'un paquet que le sien n'a pas le droit d'utiliser (packages.php).
 */
class PackageBoundariesTest extends TestCase
{
    /** @var array<string, array{description: string, requires: list<string>, suggests?: list<string>, psr?: list<string>}> */
    private array $packages;

    protected function setUp(): void
    {
        $this->packages = require __DIR__ . '/packages.php';
    }

    public function test_no_package_depends_on_a_package_it_may_not_use(): void
    {
        $violations = array_keys($this->violations());

        $this->assertSame([], $violations, "Dépendance interdite entre paquets (voir tests/Unit/Architecture/packages.php et l'ADR 0012) :\n"
            . implode("\n", $violations)
            . "\nRemplacez-la par un point d'extension du paquet de bas niveau, branché dans Application::wire().");
    }

    public function test_every_package_folder_is_declared(): void
    {
        $folders = array_map('basename', glob($this->root() . '/*', GLOB_ONLYDIR) ?: []);
        sort($folders);
        $declared = array_keys($this->packages);
        sort($declared);

        $this->assertSame($declared, $folders);
    }

    public function test_each_composer_json_matches_the_declared_dependencies(): void
    {
        $version = json_decode((string) file_get_contents(dirname($this->root()) . '/composer.json'), true);

        foreach ($this->packages as $name => $package) {
            $file = $this->root() . "/$name/composer.json";
            $this->assertFileExists($file);
            $composer = json_decode((string) file_get_contents($file), true);

            $this->assertSame("niangpro/$name", $composer['name'] ?? null, $file);
            $this->assertSame($package['description'], $composer['description'] ?? null, $file);
            $this->assertSame(['Niang\\Core\\' => 'src/'], $composer['autoload']['psr-4'] ?? null, $file);

            $requires = array_keys($composer['require'] ?? []);
            $expected = ['php', ...array_map(fn ($p) => "niangpro/$p", $package['requires']), ...($package['psr'] ?? [])];
            sort($requires);
            sort($expected);
            $this->assertSame($expected, $requires, "require de $file");

            foreach ($package['psr'] ?? [] as $psr) {
                $this->assertSame($version['require'][$psr], $composer['require'][$psr], "$psr dans $file : même contrainte que le composer.json racine");
            }

            // Seules les suggestions NiangPro sont contrôlées (foundation suggère aussi phpunit/phpunit).
            $suggests = array_values(array_filter(array_keys($composer['suggest'] ?? []), fn ($p) => str_starts_with((string) $p, 'niangpro/')));
            $expected = array_map(fn ($p) => "niangpro/$p", $package['suggests'] ?? []);
            sort($suggests);
            sort($expected);
            $this->assertSame($expected, $suggests, "suggest de $file");
        }
    }

    /** @return array<string, true> « paquet: Classe → Autre (paquet) » */
    private function violations(): array
    {
        $classes = $this->classes();
        $violations = [];

        $functions = $this->helperFunctions();

        foreach ($classes as $class => [$from, $file]) {
            $allowed = [$from, ...$this->packages[$from]['requires'], ...($this->packages[$from]['suggests'] ?? [])];

            foreach ($this->references($class, $file, $classes) as $target) {
                $to = $classes[$target][0];

                if (!in_array($to, $allowed, true)) {
                    $violations["$from: $class → $target ($to)"] = true;
                }
            }

            foreach ($this->functionCalls($file, $functions) as $function) {
                $to = $functions[$function];

                if (!in_array($to, $allowed, true)) {
                    $violations["$from: $class → $function() ($to)"] = true;
                }
            }
        }

        ksort($violations);

        return $violations;
    }

    private function root(): string
    {
        return dirname(__DIR__, 3) . '/packages';
    }

    /** @return array<string, array{0: string, 1: string}> nom court (sans Niang\Core\) => [paquet, fichier] */
    private function classes(): array
    {
        $classes = [];

        foreach (glob($this->root() . '/*/src', GLOB_ONLYDIR) ?: [] as $src) {
            $package = basename(dirname($src));

            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $relative = substr($file->getPathname(), strlen($src) + 1, -4);

                if ($file->getExtension() === 'php') {
                    // helpers.php : vérifié comme une classe, sous le nom « <paquet>/helpers ».
                    $classes[$relative === 'helpers' ? "$package/helpers" : str_replace('/', '\\', $relative)] = [$package, $file->getPathname()];
                }
            }
        }

        ksort($classes);

        return $classes;
    }

    /** @return array<string, string> fonction globale => paquet qui la définit (son helpers.php) */
    private function helperFunctions(): array
    {
        $functions = [];

        foreach (glob($this->root() . '/*/src/helpers.php') ?: [] as $file) {
            preg_match_all("/function_exists\\('([A-Za-z_]+)'\\)/", (string) file_get_contents($file), $m);

            foreach ($m[1] as $name) {
                $functions[$name] = basename(dirname($file, 2));
            }
        }

        return $functions;
    }

    /**
     * Fonctions globales des helpers appelées dans $file (pas les méthodes du même nom, ni les
     * définitions, ni les commentaires).
     *
     * @param array<string, string> $functions
     * @return list<string>
     */
    private function functionCalls(string $file, array $functions): array
    {
        $tokens = array_values(array_filter(
            token_get_all((string) file_get_contents($file)),
            fn ($t) => !is_array($t) || !in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
        ));
        $calls = [];

        foreach ($tokens as $i => $token) {
            if (!is_array($token) || !in_array($token[0], [T_STRING, T_NAME_FULLY_QUALIFIED], true)) {
                continue;
            }

            $name = ltrim($token[1], '\\');
            $next = $tokens[$i + 1] ?? null;
            $previous = $tokens[$i - 1] ?? null;
            $isMember = is_array($previous) && in_array($previous[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_CONST], true);

            if ($next === '(' && !$isMember && isset($functions[$name])) {
                $calls[$name] = true;
            }
        }

        return array_keys($calls);
    }

    /**
     * Classes de Niang\Core citées dans le code (use, noms qualifiés, noms du même espace de noms),
     * commentaires exclus.
     *
     * @param array<string, array{0: string, 1: string}> $classes
     * @return list<string>
     */
    private function references(string $class, string $file, array $classes): array
    {
        $namespace = str_contains($class, '\\') ? substr($class, 0, (int) strrpos($class, '\\')) . '\\' : '';
        $found = [];

        foreach (token_get_all((string) file_get_contents($file)) as $token) {
            if (!is_array($token) || !in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                continue;
            }

            $name = ltrim($token[1], '\\');
            $name = str_starts_with($name, 'Niang\\Core\\') ? substr($name, 11) : $name;

            foreach ([$namespace . $name, $name] as $candidate) {
                if ($candidate !== $class && isset($classes[$candidate])) {
                    $found[$candidate] = true;
                }
            }
        }

        return array_keys($found);
    }
}
