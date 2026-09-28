<?php

namespace Tests\Feature;

use Niang\Core\Console\ProjectScaffolder;
use PHPUnit\Framework\TestCase;
use Tests\Support\StagedProject;

/**
 * Installe réellement chaque thème livré dans une copie du projet (voir StagedProject), puis
 * exécute la suite Feature de cette copie : les tests livrés avec le thème (chaque page visiteur
 * répond 200, formulaires protégés...) prouvent que ce qu'un utilisateur obtient à la création de
 * son projet fonctionne. Ajouter un dossier dans resources/scaffold/themes/ ajoute son cas ici.
 */
class ThemeInstallationTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function shippedThemes(): array
    {
        $themes = [];

        foreach (array_keys((new ProjectScaffolder())->catalog()) as $slug) {
            if ($slug !== ProjectScaffolder::DEFAULT_TYPE) {
                $themes[$slug] = [$slug];
            }
        }

        return $themes;
    }

    public function test_the_catalog_ships_the_documented_site_types_in_order_with_minimal_last(): void
    {
        $this->assertSame(
            ['vitrine', 'ecommerce', 'blog', 'portfolio', 'landing', 'minimal'],
            array_keys((new ProjectScaffolder())->catalog())
        );
    }

    /** @dataProvider shippedThemes */
    public function test_an_installed_theme_passes_its_own_feature_suite(string $slug): void
    {
        $project = StagedProject::withTheme($slug);

        try {
            [$code, $output] = $project->phpunit([]);

            $this->assertSame(0, $code, "La suite Feature du thème « $slug » échoue :\n$output");
            // « OK (N tests...) », ou « OK, but some tests were skipped! » quand une plateforme saute
            // des tests (Windows : pcntl absent, branches macOS/Linux...).
            $this->assertMatchesRegularExpression('/^OK( \(\d+ tests?|, but some tests were skipped!)/m', $output);

            // Les démos remplacées ne doivent pas rester : le thème est livré avec SES tests.
            $this->assertFileDoesNotExist($project->path . '/tests/Feature/HomeTest.php');
            $this->assertFileExists($project->path . '/config/site.php');
        } finally {
            $project->destroy();
        }
    }

    /** @dataProvider shippedThemes */
    public function test_an_installed_theme_declares_only_cacheable_routes(string $slug): void
    {
        $project = StagedProject::withTheme($slug);

        try {
            [$code, $output] = $project->php(['bin/niang', 'route:cache']);

            $this->assertSame(0, $code, $output);
            $this->assertStringNotContainsString('closure', $output, 'Route à closure : non compatible avec route:cache en production.');
        } finally {
            $project->destroy();
        }
    }
}
