<?php

namespace Tests\Unit\Storage;

use Niang\Core\Config;
use Niang\Core\Exceptions\ConfigurationException;
use Niang\Core\Storage;
use Niang\Core\Storage\S3Client;
use Niang\Core\UrlSignature;
use PHPUnit\Framework\TestCase;

/** Choix du disque et URL, sans serveur S3. */
class StorageDiskTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::load(base_path());
        Storage::reset();
        parent::tearDown();
    }

    public function test_an_unknown_disk_is_rejected(): void
    {
        Config::load(base_path());
        Config::set('filesystems.disk', 'ftp');

        $this->expectException(ConfigurationException::class);
        Storage::exists('x');
    }

    public function test_missing_s3_credentials_are_reported(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('bucket');
        new S3Client(['key' => 'k', 'secret' => 's', 'region' => 'eu-west-3', 'bucket' => '']);
    }

    public function test_s3_urls_for_aws_and_compatible_services(): void
    {
        $base = ['key' => 'k', 'secret' => 's', 'region' => 'eu-west-3', 'bucket' => 'photos'];

        $this->assertSame('https://photos.s3.eu-west-3.amazonaws.com/a/b%20c.png', (new S3Client($base))->url('a/b c.png'));
        $this->assertSame('https://s3.eu-west-3.amazonaws.com/photos/a.png', (new S3Client($base + ['path_style' => true]))->url('a.png'));
        $this->assertSame('https://compte.r2.cloudflarestorage.com/photos/a.png', (new S3Client($base + ['endpoint' => 'https://compte.r2.cloudflarestorage.com/']))->url('a.png'));
        $this->assertSame('https://photos.minio.test/a.png', (new S3Client($base + ['endpoint' => 'https://minio.test', 'path_style' => false]))->url('a.png'));
    }

    public function test_public_url_uses_the_configured_cdn(): void
    {
        Config::load(base_path());
        Config::set('filesystems.disk', 's3');
        Config::set('filesystems.s3', ['key' => 'k', 'secret' => 's', 'region' => 'eu-west-3', 'bucket' => 'photos', 'url' => 'https://cdn.exemple.sn/']);
        Storage::reset();

        $this->assertSame('https://cdn.exemple.sn/avatars/1.png', Storage::url('avatars/1.png'));
    }

    public function test_local_temporary_urls_are_signed(): void
    {
        Config::load(base_path());
        $previous = getenv('APP_KEY');
        putenv('APP_KEY=' . str_repeat('k', 64));

        try {
            $url = Storage::temporaryUrl('factures/1.pdf', 60);
            $this->assertStringStartsWith('/storage/factures/1.pdf?expires=', $url);
            $this->assertTrue(UrlSignature::validate($url));
        } finally {
            $previous === false ? putenv('APP_KEY') : putenv("APP_KEY=$previous");
        }
    }
}
