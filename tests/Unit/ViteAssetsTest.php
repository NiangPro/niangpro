<?php

namespace Tests\Unit;

use Niang\Core\ViteAssets;
use PHPUnit\Framework\TestCase;
use Tests\Support\UsesTempDirectory;

class ViteAssetsTest extends TestCase
{
    use UsesTempDirectory;

    private string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicPath = $this->makeTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->removeTempDirectories();
        parent::tearDown();
    }

    public function test_resolves_the_hashed_url_from_the_manifest(): void
    {
        $this->writeFile($this->publicPath, 'build/manifest.json', json_encode([
            'resources/js/app.js' => [
                'file' => 'assets/app-4ed993c7.js',
                'src' => 'resources/js/app.js',
                'isEntry' => true,
            ],
        ]));

        $url = (new ViteAssets($this->publicPath))->asset('resources/js/app.js');

        $this->assertSame('/build/assets/app-4ed993c7.js', $url);
    }

    public function test_switches_to_the_dev_server_when_the_hot_file_is_present(): void
    {
        $this->writeFile($this->publicPath, 'hot', 'http://localhost:5173');

        $url = (new ViteAssets($this->publicPath))->asset('resources/js/app.js');

        $this->assertSame('http://localhost:5173/resources/js/app.js', $url);
    }

    public function test_the_hot_file_takes_priority_over_the_manifest(): void
    {
        $this->writeFile($this->publicPath, 'hot', 'http://localhost:5173');
        $this->writeFile($this->publicPath, 'build/manifest.json', json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-4ed993c7.js'],
        ]));

        $url = (new ViteAssets($this->publicPath))->asset('resources/js/app.js');

        $this->assertSame('http://localhost:5173/resources/js/app.js', $url);
    }

    public function test_an_empty_hot_file_falls_back_to_the_default_dev_server_address(): void
    {
        $this->writeFile($this->publicPath, 'hot', '');

        $url = (new ViteAssets($this->publicPath))->asset('resources/js/app.js');

        $this->assertSame('http://localhost:5173/resources/js/app.js', $url);
    }

    public function test_is_inert_and_raises_a_clear_error_when_neither_file_exists(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/npm run build/');

        (new ViteAssets($this->publicPath))->asset('resources/js/app.js');
    }

    public function test_raises_a_clear_error_when_the_requested_entry_is_missing_from_the_manifest(): void
    {
        $this->writeFile($this->publicPath, 'build/manifest.json', json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-4ed993c7.js'],
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/resources\/js\/absent\.js/');

        (new ViteAssets($this->publicPath))->asset('resources/js/absent.js');
    }
}
