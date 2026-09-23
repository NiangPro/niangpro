<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\ProjectScaffolder;
use PHPUnit\Framework\TestCase;
use Tests\Support\UsesTempDirectory;

/** Isolé dans un dossier temporaire : aucun test ne touche resources/scaffold ni le projet réel. */
class ProjectScaffolderTest extends TestCase
{
    use UsesTempDirectory;

    private string $scaffold;
    private string $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scaffold = $this->makeTempDirectory();
        $this->target = $this->makeTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->removeTempDirectories();
        parent::tearDown();
    }

    private function scaffolder(): ProjectScaffolder
    {
        return new ProjectScaffolder($this->scaffold);
    }

    private function theme(string $slug, ?array $manifest = null): void
    {
        $this->writeFile($this->scaffold, "themes/$slug/.gitkeep");

        if ($manifest !== null) {
            $this->writeFile($this->scaffold, "themes/$slug/theme.json", json_encode($manifest));
        }
    }

    public function test_catalog_contains_only_minimal_when_no_theme_exists(): void
    {
        $this->assertSame(['minimal' => 'Minimal — squelette de démonstration'], $this->scaffolder()->catalog());
    }

    public function test_catalog_lists_themes_by_order_then_slug_with_minimal_last(): void
    {
        $this->theme('blog', ['label' => 'Blog / magazine', 'order' => 30]);
        $this->theme('vitrine', ['label' => 'Site vitrine', 'order' => 10]);
        $this->theme('zeta', ['label' => 'Zeta']);
        $this->theme('alpha', ['label' => 'Alpha']);

        $this->assertSame(['vitrine', 'blog', 'alpha', 'zeta', 'minimal'], array_keys($this->scaffolder()->catalog()));
        $this->assertSame('Blog / magazine', $this->scaffolder()->catalog()['blog']);
    }

    public function test_a_theme_without_manifest_appears_with_its_slug_as_label(): void
    {
        $this->theme('agence');

        $this->assertSame('Agence', $this->scaffolder()->catalog()['agence']);
    }

    public function test_catalog_ignores_loose_files_invalid_slugs_and_a_theme_named_minimal(): void
    {
        $this->writeFile($this->scaffold, 'themes/README.md', 'pas un thème');
        $this->theme('Mauvais Nom');
        $this->theme('minimal', ['label' => 'Ne doit pas écraser le défaut']);

        $this->assertSame(['minimal'], array_keys($this->scaffolder()->catalog()));
        $this->assertStringStartsWith('Minimal', $this->scaffolder()->catalog()['minimal']);
    }

    public function test_has_reports_known_slugs_only(): void
    {
        $this->theme('blog');

        $this->assertTrue($this->scaffolder()->has('blog'));
        $this->assertTrue($this->scaffolder()->has('minimal'));
        $this->assertFalse($this->scaffolder()->has('inconnu'));
    }

    public function test_install_rejects_an_unknown_type_and_lists_the_valid_ones(): void
    {
        $this->theme('blog');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('blog, minimal');

        $this->scaffolder()->install('inconnu', $this->target);
    }

    public function test_install_rejects_a_missing_target_directory(): void
    {
        $this->theme('blog');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dossier cible introuvable');

        $this->scaffolder()->install('blog', $this->target . '/n-existe-pas');
    }

    public function test_installing_minimal_leaves_the_project_untouched(): void
    {
        $this->theme('blog');
        $this->writeFile($this->target, 'routes/web.php', 'original');

        $this->scaffolder()->install('minimal', $this->target);

        $this->assertSame('original', file_get_contents($this->target . '/routes/web.php'));
        $this->assertSame(['routes'], array_values(array_diff(scandir($this->target), ['.', '..'])));
    }

    public function test_install_copies_the_theme_tree_creating_nested_directories(): void
    {
        $this->theme('blog');
        $this->writeFile($this->scaffold, 'themes/blog/routes/web.php', 'routes du blog');
        $this->writeFile($this->scaffold, 'themes/blog/resources/views/pages/deep/home.php', 'accueil');
        $this->writeFile($this->scaffold, 'themes/blog/public/css/theme.css', 'body{}');

        $this->scaffolder()->install('blog', $this->target);

        $this->assertSame('routes du blog', file_get_contents($this->target . '/routes/web.php'));
        $this->assertSame('accueil', file_get_contents($this->target . '/resources/views/pages/deep/home.php'));
        $this->assertSame('body{}', file_get_contents($this->target . '/public/css/theme.css'));
    }

    public function test_install_replaces_existing_files_instead_of_merging(): void
    {
        $this->theme('blog');
        $this->writeFile($this->scaffold, 'themes/blog/routes/web.php', "nouveau\n");
        $this->writeFile($this->target, 'routes/web.php', "ancien 1\nancien 2\nancien 3\n");

        $this->scaffolder()->install('blog', $this->target);

        $this->assertSame("nouveau\n", file_get_contents($this->target . '/routes/web.php'));
    }

    public function test_the_theme_wins_over_shared_files_and_shared_files_reach_every_theme(): void
    {
        $this->theme('blog');
        $this->writeFile($this->scaffold, 'shared/public/css/niang.css', 'design system');
        $this->writeFile($this->scaffold, 'shared/resources/views/components/icon.php', 'icône partagée');
        $this->writeFile($this->scaffold, 'shared/resources/views/components/header.php', 'en-tête partagé');
        $this->writeFile($this->scaffold, 'themes/blog/resources/views/components/header.php', 'en-tête du blog');

        $this->scaffolder()->install('blog', $this->target);

        $this->assertSame('design system', file_get_contents($this->target . '/public/css/niang.css'));
        $this->assertSame('icône partagée', file_get_contents($this->target . '/resources/views/components/icon.php'));
        $this->assertSame('en-tête du blog', file_get_contents($this->target . '/resources/views/components/header.php'));
    }

    public function test_manifests_are_never_copied_into_the_project(): void
    {
        $this->theme('blog', ['label' => 'Blog']);
        $this->writeFile($this->scaffold, 'shared/theme.json', '{}');
        $this->writeFile($this->scaffold, 'themes/blog/routes/web.php', 'x');

        $this->scaffolder()->install('blog', $this->target);

        $this->assertFileDoesNotExist($this->target . '/theme.json');
        $this->assertFileExists($this->target . '/routes/web.php');
    }

    public function test_remove_paths_from_shared_and_theme_manifests_are_deleted_before_the_copy(): void
    {
        $this->theme('blog', ['remove' => ['resources/views/posts']]);
        $this->writeFile($this->scaffold, 'shared/theme.json', json_encode(['remove' => ['resources/views/home.php', 'tests/Feature/HomeTest.php']]));
        // Le thème remet une vue dans un dossier qu'il a lui-même demandé de vider.
        $this->writeFile($this->scaffold, 'themes/blog/resources/views/posts/index.php', 'liste du thème');

        $this->writeFile($this->target, 'resources/views/home.php', 'démo');
        $this->writeFile($this->target, 'resources/views/posts/index.php', 'liste de démo');
        $this->writeFile($this->target, 'resources/views/posts/old.php', 'obsolète');
        $this->writeFile($this->target, 'tests/Feature/HomeTest.php', 'test de démo');
        $this->writeFile($this->target, 'tests/Feature/HealthTest.php', 'à conserver');

        $this->scaffolder()->install('blog', $this->target);

        $this->assertFileDoesNotExist($this->target . '/resources/views/home.php');
        $this->assertFileDoesNotExist($this->target . '/tests/Feature/HomeTest.php');
        $this->assertFileDoesNotExist($this->target . '/resources/views/posts/old.php');
        $this->assertSame('liste du thème', file_get_contents($this->target . '/resources/views/posts/index.php'));
        $this->assertFileExists($this->target . '/tests/Feature/HealthTest.php');
    }

    public function test_remove_ignores_paths_that_do_not_exist(): void
    {
        $this->theme('blog', ['remove' => ['resources/views/absent.php']]);

        $this->scaffolder()->install('blog', $this->target);

        $this->addToAssertionCount(1);
    }

    /** @return array<string, array{0: string}> */
    public static function unsafeRemovePaths(): array
    {
        return [
            'remontée de dossier' => ['../autre-projet'],
            'remontée au milieu' => ['app/../../autre'],
            'chemin absolu' => ['/etc'],
            'racine du projet' => ['.'],
            'vide' => [''],
        ];
    }

    /** @dataProvider unsafeRemovePaths */
    public function test_remove_refuses_paths_that_could_escape_the_project(string $path): void
    {
        $this->theme('blog', ['remove' => [$path]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chemin à supprimer invalide');

        $this->scaffolder()->install('blog', $this->target);
    }

    public function test_next_steps_come_from_the_manifest(): void
    {
        $this->theme('blog', ['next_steps' => ['./bin/niang migrate', './bin/niang db:seed']]);
        $this->theme('vitrine');

        $this->assertSame(['./bin/niang migrate', './bin/niang db:seed'], $this->scaffolder()->nextSteps('blog'));
        $this->assertSame([], $this->scaffolder()->nextSteps('vitrine'));
        $this->assertSame([], $this->scaffolder()->nextSteps('minimal'));
        $this->assertSame([], $this->scaffolder()->nextSteps('inconnu'));
    }

    public function test_notes_come_from_the_manifest(): void
    {
        $this->theme('blog', ['notes' => ['Compte admin : admin@example.com']]);
        $this->theme('vitrine');

        $this->assertSame(['Compte admin : admin@example.com'], $this->scaffolder()->notes('blog'));
        $this->assertSame([], $this->scaffolder()->notes('vitrine'));
        $this->assertSame([], $this->scaffolder()->notes('minimal'));
    }

    public function test_setup_commands_come_from_the_manifest_and_leave_the_remaining_steps(): void
    {
        $this->theme('blog', [
            'setup' => ['migrate', 'db:seed'],
            'next_steps' => ['./bin/niang migrate', './bin/niang db:seed', './bin/niang serve'],
        ]);

        $this->assertSame(['migrate', 'db:seed'], $this->scaffolder()->setup('blog'));
        $this->assertSame(['./bin/niang serve'], $this->scaffolder()->remainingSteps('blog', ['migrate', 'db:seed']));
        $this->assertSame(['./bin/niang db:seed', './bin/niang serve'], $this->scaffolder()->remainingSteps('blog', ['migrate']));
        $this->assertSame([], $this->scaffolder()->setup('minimal'));
    }

    public function test_a_setup_command_that_is_not_a_plain_niang_command_is_refused(): void
    {
        $this->theme('blog', ['setup' => ['migrate; rm -rf /']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Commande de setup invalide');

        $this->scaffolder()->setup('blog');
    }

    public function test_declared_modules_are_installed_between_shared_and_the_theme(): void
    {
        $this->theme('blog', ['modules' => ['admin']]);
        $this->theme('vitrine');
        $this->writeFile($this->scaffold, 'shared/resources/views/admin/layout.php', 'layout partagé');
        $this->writeFile($this->scaffold, 'modules/admin/resources/views/admin/layout.php', 'layout du module');
        $this->writeFile($this->scaffold, 'modules/admin/public/css/admin.css', 'css du module');
        $this->writeFile($this->scaffold, 'modules/admin/app/Support/AdminMenu.php', 'menu du module');
        $this->writeFile($this->scaffold, 'themes/blog/app/Support/AdminMenu.php', 'menu du blog');

        $this->scaffolder()->install('blog', $this->target);

        $this->assertSame('layout du module', file_get_contents($this->target . '/resources/views/admin/layout.php'));
        $this->assertSame('css du module', file_get_contents($this->target . '/public/css/admin.css'));
        $this->assertSame('menu du blog', file_get_contents($this->target . '/app/Support/AdminMenu.php'));
        $this->assertArrayNotHasKey('admin', $this->scaffolder()->catalog(), 'Un module n\'est pas un type de site.');

        $other = $this->makeTempDirectory();
        $this->scaffolder()->install('vitrine', $other);
        $this->assertFileDoesNotExist($other . '/public/css/admin.css');
    }

    public function test_an_unknown_module_is_reported(): void
    {
        $this->theme('blog', ['modules' => ['inexistant']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('inexistant');

        $this->scaffolder()->install('blog', $this->target);
    }

    public function test_an_invalid_manifest_is_reported_with_the_file_name(): void
    {
        $this->writeFile($this->scaffold, 'themes/casse/theme.json', '{ pas du json');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('theme.json est invalide');

        $this->scaffolder()->catalog();
    }

    public function test_installing_twice_gives_the_same_result(): void
    {
        $this->theme('blog');
        $this->writeFile($this->scaffold, 'themes/blog/routes/web.php', 'routes');

        $this->scaffolder()->install('blog', $this->target);
        $this->scaffolder()->install('blog', $this->target);

        $this->assertSame('routes', file_get_contents($this->target . '/routes/web.php'));
    }
}
