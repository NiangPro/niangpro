<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\ProjectScaffolder;
use Niang\Core\Console\ThemePackageInstaller;
use PHPUnit\Framework\TestCase;
use Tests\Support\UsesTempDirectory;

/**
 * Fixture locale (pas un vrai téléchargement Packagist) : un « paquet » n'est ici rien de plus
 * qu'un vendor/<paquet>/composer.json avec la clé extra.niangpro-theme, exactement ce
 * qu'obtiendrait `composer require --dev` — install() ne lance lui-même aucun process composer
 * (voir Commander::themeAdd), donc ce test n'en a pas besoin non plus.
 */
class ThemePackageInstallerTest extends TestCase
{
    use UsesTempDirectory;

    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = $this->makeTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->removeTempDirectories();
        parent::tearDown();
    }

    private function fakePackage(string $package, ?string $themePath): void
    {
        $this->writeFile($this->projectRoot, "vendor/$package/composer.json", json_encode([
            'name' => $package,
            'extra' => $themePath !== null ? ['niangpro-theme' => $themePath] : [],
        ]));

        if ($themePath !== null) {
            $this->writeFile($this->projectRoot, "vendor/$package/$themePath/theme.json", json_encode(['label' => 'Sunset']));
            $this->writeFile($this->projectRoot, "vendor/$package/$themePath/routes/web.php", "<?php\n// route du thème\n");
        }
    }

    public function test_installing_a_theme_package_makes_it_appear_in_the_scaffolder_catalog(): void
    {
        $this->fakePackage('acme/theme-sunset', 'resources/theme/sunset');

        $slug = (new ThemePackageInstaller($this->projectRoot))->install('acme/theme-sunset');

        $this->assertSame('sunset', $slug);

        $catalog = (new ProjectScaffolder("$this->projectRoot/resources/scaffold"))->catalog();
        $this->assertArrayHasKey('sunset', $catalog);
        $this->assertSame('Sunset', $catalog['sunset']);
    }

    public function test_the_theme_directory_is_copied_as_is(): void
    {
        $this->fakePackage('acme/theme-sunset', 'resources/theme/sunset');

        (new ThemePackageInstaller($this->projectRoot))->install('acme/theme-sunset');

        $this->assertFileExists("$this->projectRoot/resources/scaffold/themes/sunset/theme.json");
        $this->assertFileExists("$this->projectRoot/resources/scaffold/themes/sunset/routes/web.php");
        $this->assertStringContainsString(
            '// route du thème',
            (string) file_get_contents("$this->projectRoot/resources/scaffold/themes/sunset/routes/web.php")
        );
    }

    public function test_it_rejects_a_package_not_installed_in_vendor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/introuvable/');

        (new ThemePackageInstaller($this->projectRoot))->install('acme/jamais-installe');
    }

    public function test_it_rejects_a_package_without_the_extra_key(): void
    {
        $this->fakePackage('acme/pas-un-theme', null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/niangpro-theme/');

        (new ThemePackageInstaller($this->projectRoot))->install('acme/pas-un-theme');
    }

    public function test_it_rejects_a_package_whose_declared_theme_directory_is_missing(): void
    {
        $this->writeFile($this->projectRoot, 'vendor/acme/casse/composer.json', json_encode([
            'name' => 'acme/casse',
            'extra' => ['niangpro-theme' => 'theme/absent'],
        ]));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/introuvable/');

        (new ThemePackageInstaller($this->projectRoot))->install('acme/casse');
    }

    public function test_it_rejects_the_reserved_minimal_slug(): void
    {
        $this->fakePackage('acme/theme-minimal', 'theme/minimal');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/invalide/');

        (new ThemePackageInstaller($this->projectRoot))->install('acme/theme-minimal');
    }
}
