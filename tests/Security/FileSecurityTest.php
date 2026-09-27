<?php

namespace Tests\Security;

use Niang\Core\Http\Response;
use Niang\Core\Http\UploadedFile;
use Niang\Core\Storage;
use Niang\Core\Testing\TestCase;
use Niang\Core\Validation\ValidationException;
use Niang\Core\Validation\Validator;

/** Roadmap §55 : traversée de chemin et envoi de fichiers. */
class FileSecurityTest extends TestCase
{
    /** @dataProvider traversals */
    public function test_storage_refuses_to_leave_storage_app(string $path): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Storage::put($path, 'x');
    }

    /** @return array<string, array{0: string}> */
    public static function traversals(): array
    {
        return [
            'unix' => ['../../.env'],
            'windows' => ['..\\..\\.env'],
            'au milieu' => ['avatars/../../../config/app.php'],
        ];
    }

    public function test_an_absolute_path_stays_inside_storage_app(): void
    {
        $this->assertTrue(str_starts_with(Storage::path('/etc/passwd'), base_path('storage/app/')));
    }

    public function test_a_php_file_disguised_as_an_image_is_refused(): void
    {
        $fake = UploadedFile::fake('photo.png', "<?php system(\$_GET['c']); ?>", 'image/png');

        try {
            Validator::make(['avatar' => $fake], ['avatar' => 'required|image'])->validate();
            $this->fail('un script déguisé en image a été accepté');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('avatar', $e->errors, 'le type réel (contenu) est vérifié, pas le type annoncé');
        }
    }

    public function test_extensions_are_checked_against_the_real_content(): void
    {
        $fake = UploadedFile::fake('facture.pdf.php', '<?php echo 1;', 'application/pdf');

        $this->expectException(ValidationException::class);
        Validator::make(['doc' => $fake], ['doc' => 'required|mimes:pdf'])->validate();
    }

    public function test_stored_files_get_a_random_name(): void
    {
        $file = UploadedFile::fakeImage('../../evil.php.png');
        $path = $file->store('tests-securite');

        try {
            $this->assertStringStartsWith('tests-securite/', $path);
            $this->assertStringNotContainsString('evil', $path);
            $this->assertStringNotContainsString('..', $path);
        } finally {
            Storage::delete($path);
        }
    }

    public function test_html_and_svg_uploads_are_never_displayed_inline(): void
    {
        foreach (['page.html' => '<script>alert(1)</script>', 'logo.svg' => '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'] as $name => $contents) {
            $path = tempnam(sys_get_temp_dir(), 'np-sec') . '-' . $name;
            file_put_contents($path, $contents);

            $response = Response::file($path);
            unlink($path);

            $this->assertStringStartsWith('attachment', (string) $response->getHeader('Content-Disposition'), "$name forcé en téléchargement");
            $this->assertSame('nosniff', $response->getHeader('X-Content-Type-Options'));
        }
    }
}
