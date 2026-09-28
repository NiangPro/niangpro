<?php

namespace Tests\Unit\Http;

use Niang\Core\Http\Request;
use Niang\Core\Http\UploadedFile;
use Niang\Core\Storage;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{
    private const DIRECTORY = 'tests-uploads';

    protected function tearDown(): void
    {
        $dir = Storage::path(self::DIRECTORY);

        foreach (glob($dir . '/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($dir)) {
            rmdir($dir);
        }

        parent::tearDown();
    }

    public function test_the_real_mime_type_is_read_from_the_content_not_from_the_client_name(): void
    {
        $file = UploadedFile::fake('photo.png', "<?php system(\$_GET['cmd']);", 'image/png');

        $this->assertSame('photo.png', $file->clientName());
        $this->assertSame('image/png', $file->clientMimeType());
        $this->assertNotSame('image/png', $file->mimeType());
        $this->assertNotSame('png', $file->extension());
    }

    public function test_fake_image_is_a_real_png_with_the_requested_dimensions(): void
    {
        $file = UploadedFile::fakeImage('avatar.png', 120, 80);

        $this->assertSame('image/png', $file->mimeType());
        $this->assertSame('png', $file->extension());
        $this->assertSame([120, 80], array_slice(getimagesize($file->path()), 0, 2));
    }

    public function test_store_uses_a_random_name_and_the_real_extension(): void
    {
        $file = UploadedFile::fakeImage('../../mon avatar.PHP');

        $path = $file->store(self::DIRECTORY);

        $this->assertMatchesRegularExpression('#^tests-uploads/[0-9a-f]{40}\.png$#', $path);
        $this->assertTrue(Storage::exists($path));

        if (PHP_OS_FAMILY !== 'Windows') { // pas de droits Unix sous Windows : chmod() y est sans effet
            $this->assertSame('0644', substr(sprintf('%o', fileperms(Storage::path($path))), -4));
        }
    }

    public function test_a_file_without_a_recognised_type_is_stored_without_extension(): void
    {
        $path = UploadedFile::fake('script.php', "\x00\x01\x02 binaire inconnu")->store(self::DIRECTORY);

        $this->assertMatchesRegularExpression('#^tests-uploads/[0-9a-f]{40}$#', $path);
    }

    public function test_store_as_rejects_names_that_would_leave_the_directory(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UploadedFile::fakeImage()->storeAs(self::DIRECTORY, '../evil.png');
    }

    public function test_a_file_cannot_be_stored_twice(): void
    {
        $file = UploadedFile::fakeImage();
        $file->store(self::DIRECTORY);

        $this->assertFalse($file->isValid());
        $this->expectException(\RuntimeException::class);
        $file->store(self::DIRECTORY);
    }

    public function test_a_file_that_did_not_come_from_an_http_upload_is_not_valid(): void
    {
        // Un chemin arbitraire injecté dans $_FILES (ou construit par erreur) ne doit jamais être déplacé.
        $file = new UploadedFile(__FILE__, 'test.php', 'text/plain');

        $this->assertFalse($file->isValid());
        $this->expectException(\RuntimeException::class);
        $file->store(self::DIRECTORY);
    }

    public function test_upload_errors_are_explained(): void
    {
        $file = new UploadedFile('', 'enorme.mp4', 'video/mp4', UPLOAD_ERR_INI_SIZE);

        $this->assertFalse($file->isValid());
        $this->assertStringContainsString('taille maximale', $file->errorMessage());
    }

    public function test_capture_normalizes_php_files_array_including_multiple_fields(): void
    {
        $files = Request::normalizeFiles([
            'avatar' => ['name' => 'a.png', 'type' => 'image/png', 'tmp_name' => '/tmp/php1', 'error' => UPLOAD_ERR_OK, 'size' => 10],
            'empty' => ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0],
            'photos' => [
                'name' => ['un.jpg', '', 'trois.jpg'],
                'type' => ['image/jpeg', '', 'image/jpeg'],
                'tmp_name' => ['/tmp/php2', '', '/tmp/php3'],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_PARTIAL],
                'size' => [1, 0, 2],
            ],
        ]);

        $this->assertInstanceOf(UploadedFile::class, $files['avatar']);
        $this->assertSame('a.png', $files['avatar']->clientName());
        $this->assertArrayNotHasKey('empty', $files, 'Un champ fichier laissé vide doit être absent, pas une erreur.');
        $this->assertSame([0, 2], array_keys($files['photos']));
        $this->assertSame('trois.jpg', $files['photos'][2]->clientName());
        $this->assertSame(UPLOAD_ERR_PARTIAL, $files['photos'][2]->error());
    }

    public function test_request_create_moves_uploaded_files_out_of_the_body(): void
    {
        $avatar = UploadedFile::fakeImage();
        $photos = [UploadedFile::fakeImage(), UploadedFile::fakeImage()];

        $request = Request::create('POST', '/profil', ['name' => 'Awa', 'avatar' => $avatar, 'photos' => $photos]);

        $this->assertSame(['name' => 'Awa'], $request->all());
        $this->assertSame($avatar, $request->file('avatar'));
        $this->assertTrue($request->hasFile('avatar'));
        $this->assertFalse($request->hasFile('name'));
        $this->assertNull($request->file('photos'), 'file() ne retourne qu\'un fichier seul ; une liste se lit dans $request->files.');
        $this->assertSame($photos, $request->files['photos']);
        $this->assertSame($avatar, $request->allWithFiles()['avatar']);
    }
}
