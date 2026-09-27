<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Roadmap §46 (ADR 0012) : src/Core doit pouvoir être découpé en paquets sans dépendance circulaire.
 * Ce test mesure chaque référence d'une classe vers une classe d'un autre paquet que ceux que son
 * paquet a le droit d'utiliser (packages.php), et la compare à baseline.php : une dépendance
 * interdite de plus fait échouer le test ; une de moins aussi, tant que baseline.php n'est pas mis à
 * jour — le compte ne peut que descendre, jusqu'à zéro.
 */
class PackageBoundariesTest extends TestCase
{
    /** @var array<string, array{match: list<string>, requires: list<string>, suggests?: list<string>}> */
    private array $packages;

    protected function setUp(): void
    {
        $this->packages = require __DIR__ . '/packages.php';
    }

    public function test_forbidden_dependencies_only_go_down(): void
    {
        $current = $this->violations();
        $baseline = require __DIR__ . '/baseline.php';

        $new = array_diff_key($current, $baseline);
        $this->assertSame([], $new, "Nouvelle dépendance interdite entre paquets (voir tests/Unit/Architecture/packages.php) :\n" . implode("\n", array_keys($new)));

        $fixed = array_diff_key($baseline, $current);
        $this->assertSame([], $fixed, "Dépendance supprimée : retirez-la de tests/Unit/Architecture/baseline.php :\n" . implode("\n", array_keys($fixed)));
    }

    public function test_every_class_belongs_to_a_package(): void
    {
        foreach ($this->classes() as $class => $file) {
            $this->assertNotNull($this->packageOf($class), $class);
        }
    }

    /** @return array<string, true> « paquet: Classe → Autre (paquet) » */
    private function violations(): array
    {
        $classes = $this->classes();
        $violations = [];

        foreach ($classes as $class => $file) {
            $from = (string) $this->packageOf($class);
            $allowed = [$from, ...$this->packages[$from]['requires'], ...($this->packages[$from]['suggests'] ?? [])];

            if (in_array('*', $allowed, true)) {
                continue;
            }

            foreach ($this->references($class, $file, $classes) as $target) {
                $to = (string) $this->packageOf($target);

                if (!in_array($to, $allowed, true)) {
                    $violations["$from: $class → $target ($to)"] = true;
                }
            }
        }

        ksort($violations);

        return $violations;
    }

    private function packageOf(string $class): ?string
    {
        foreach ($this->packages as $name => $package) {
            foreach ($package['match'] as $pattern) {
                if (preg_match('#' . $pattern . '#', $class) === 1) {
                    return $name;
                }
            }
        }

        return null;
    }

    /** @return array<string, string> nom court (sans Niang\Core\) => fichier */
    private function classes(): array
    {
        $root = dirname(__DIR__, 3) . '/src/Core';
        $classes = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() === 'php') {
                $classes[str_replace('/', '\\', substr($file->getPathname(), strlen($root) + 1, -4))] = $file->getPathname();
            }
        }

        ksort($classes);

        return $classes;
    }

    /**
     * Classes de Niang\Core citées dans le code (use, noms qualifiés, noms du même espace de noms),
     * commentaires exclus.
     *
     * @param array<string, string> $classes
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
