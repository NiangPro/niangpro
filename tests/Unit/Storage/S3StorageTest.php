<?php

namespace Tests\Unit\Storage;

use Niang\Core\Config;
use Niang\Core\Http\UploadedFile;
use Niang\Core\Storage;
use PHPUnit\Framework\TestCase;

/**
 * FILESYSTEM_DISK=s3 contre un vrai serveur compatible S3 (moto en CI, MinIO en local...) :
 *   NP_S3_ENDPOINT=http://127.0.0.1:5055 NP_S3_BUCKET=niangpro-test vendor/bin/phpunit tests/Unit/Storage
 * Ignoré sans NP_S3_ENDPOINT. La signature elle-même est vérifiée par SigV4Test (exemples d'AWS) :
 * moto accepte n'importe quelle signature.
 */
class S3StorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $endpoint = getenv('NP_S3_ENDPOINT');

        if ($endpoint === false || $endpoint === '') {
            $this->markTestSkipped('Pas de serveur S3 (NP_S3_ENDPOINT).');
        }

        Config::load(base_path());
        Config::set('filesystems.disk', 's3');
        Config::set('filesystems.s3', [
            'key' => getenv('NP_S3_KEY') ?: 'test',
            'secret' => getenv('NP_S3_SECRET') ?: 'test',
            'region' => 'us-east-1',
            'bucket' => getenv('NP_S3_BUCKET') ?: 'niangpro-test',
            'endpoint' => $endpoint,
            'path_style' => true,
            'url' => '',
        ]);
        Storage::reset();
    }

    protected function tearDown(): void
    {
        Config::load(base_path());
        Storage::reset();
        parent::tearDown();
    }

    public function test_put_get_exists_size_delete(): void
    {
        $bytes = random_bytes(2048) . 'fin';
        $path = 'tests/' . uniqid() . '/facture été 2026.pdf';

        $this->assertTrue(Storage::put($path, $bytes, 'application/pdf'));
        $this->assertTrue(Storage::exists($path));
        $this->assertSame($bytes, Storage::get($path));
        $this->assertSame(strlen($bytes), Storage::size($path));

        $this->assertTrue(Storage::delete($path));
        $this->assertFalse(Storage::exists($path));
        $this->assertNull(Storage::get($path));
        $this->assertNull(Storage::size($path));
    }

    public function test_a_presigned_url_is_readable_without_credentials(): void
    {
        $path = 'tests/' . uniqid() . '.txt';
        Storage::put($path, 'contenu privé');

        try {
            $this->assertSame('contenu privé', file_get_contents(Storage::temporaryUrl($path, 60)));
        } finally {
            Storage::delete($path);
        }
    }

    public function test_uploads_are_sent_to_s3(): void
    {
        $path = UploadedFile::fakeImage('avatar.png', 16, 16)->store('tests-avatars');

        try {
            $this->assertStringStartsWith("\x89PNG", (string) Storage::get($path));
        } finally {
            Storage::delete($path);
        }
    }

    public function test_path_is_not_available_on_s3(): void
    {
        $this->expectException(\LogicException::class);
        Storage::path('x');
    }
}
