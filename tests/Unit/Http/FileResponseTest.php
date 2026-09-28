<?php

namespace Tests\Unit\Http;

use Niang\Core\DebugToolbar;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Http\Response;
use Niang\Core\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use Tests\Support\UsesTempDirectory;

class FileResponseTest extends TestCase
{
    use UsesTempDirectory;

    protected function tearDown(): void
    {
        $this->removeTempDirectories();
        parent::tearDown();
    }

    private function file(string $name, string $contents): string
    {
        $dir = $this->makeTempDirectory();
        $this->writeFile($dir, $name, $contents);

        return "$dir/$name";
    }

    public function test_download_sets_attachment_headers_and_reads_the_file(): void
    {
        $path = $this->file('rapport.csv', "nom;total\nAwa;12\n");

        $response = Response::download($path, 'Rapport été.csv');

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('attachment; filename="Rapport _t_.csv"; filename*=UTF-8\'\'Rapport%20%C3%A9t%C3%A9.csv', $response->getHeader('Content-Disposition'));
        $this->assertSame((string) strlen("nom;total\nAwa;12\n"), $response->getHeader('Content-Length'));
        $this->assertSame('nosniff', $response->getHeader('X-Content-Type-Options'));
        $this->assertSame("nom;total\nAwa;12\n", $response->getContent());
        $this->assertTrue($response->isStreamed());
    }

    public function test_file_shows_safe_types_inline(): void
    {
        $png = UploadedFile::fakeImage('photo.png');

        $response = Response::file($png->path(), 'photo.png');

        $this->assertSame('image/png', $response->getHeader('Content-Type'));
        $this->assertStringStartsWith('inline;', (string) $response->getHeader('Content-Disposition'));
    }

    public function test_file_never_shows_html_or_svg_inline(): void
    {
        foreach (['page.html' => '<html><script>alert(1)</script></html>', 'logo.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'] as $name => $contents) {
            $response = Response::file($this->file($name, $contents));

            $this->assertStringStartsWith('attachment;', (string) $response->getHeader('Content-Disposition'), $name);
        }
    }

    public function test_a_missing_file_is_a_404(): void
    {
        $this->expectException(NotFoundException::class);

        Response::download('/chemin/qui/n/existe/pas.pdf');
    }

    public function test_a_header_injection_through_the_file_name_is_neutralised(): void
    {
        $response = Response::download($this->file('a.txt', 'x'), "a.txt\"\r\nSet-Cookie: pirate=1");

        $this->assertStringNotContainsString("\r", (string) $response->getHeader('Content-Disposition'));
        $this->assertStringNotContainsString("\n", (string) $response->getHeader('Content-Disposition'));
    }

    public function test_stream_runs_its_callback(): void
    {
        $response = Response::stream(function (): void {
            echo "ligne 1\n";
            echo "ligne 2\n";
        }, 200, ['Content-Type' => 'text/csv']);

        $this->assertSame("ligne 1\nligne 2\n", $response->getContent());
        $this->assertSame('text/csv', $response->getHeader('Content-Type'));
        $this->assertTrue($response->isStreamed());
    }

    public function test_emptying_the_content_also_drops_the_file(): void
    {
        // Ce que fait le Router pour une requête HEAD.
        $response = Response::download($this->file('a.txt', 'contenu'))->content('');

        $this->assertSame('', $response->getContent());
        $this->assertFalse($response->isStreamed());
    }

    public function test_the_debug_toolbar_never_touches_a_downloaded_html_file(): void
    {
        $path = $this->file('export.html', '<html><body>export</body></html>');
        $previous = getenv('APP_DEBUG');
        putenv('APP_DEBUG=true');

        try {
            $response = DebugToolbar::inject(Response::download($path), hrtime(true));
        } finally {
            $previous === false ? putenv('APP_DEBUG') : putenv("APP_DEBUG=$previous");
        }

        $this->assertSame('<html><body>export</body></html>', $response->getContent());
    }
}
