<?php

namespace Tests\Unit;

use Niang\Core\Storage;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{
    protected function tearDown(): void
    {
        $dir = base_path('storage/app');

        if (is_dir($dir)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
        }

        parent::tearDown();
    }

    public function test_put_then_get_round_trips(): void
    {
        Storage::put('avatars/1.png', 'contenu-binaire');

        $this->assertTrue(Storage::exists('avatars/1.png'));
        $this->assertSame('contenu-binaire', Storage::get('avatars/1.png'));
        $this->assertSame(strlen('contenu-binaire'), Storage::size('avatars/1.png'));
    }

    public function test_put_creates_intermediate_directories(): void
    {
        Storage::put('a/b/c/file.txt', 'x');

        $this->assertTrue(Storage::exists('a/b/c/file.txt'));
    }

    public function test_get_and_size_return_null_for_a_missing_file(): void
    {
        $this->assertNull(Storage::get('inexistant.txt'));
        $this->assertNull(Storage::size('inexistant.txt'));
        $this->assertFalse(Storage::exists('inexistant.txt'));
    }

    public function test_delete_removes_the_file_and_reports_success(): void
    {
        Storage::put('to-delete.txt', 'x');

        $this->assertTrue(Storage::delete('to-delete.txt'));
        $this->assertFalse(Storage::exists('to-delete.txt'));
    }

    public function test_delete_on_a_missing_file_returns_false(): void
    {
        $this->assertFalse(Storage::delete('inexistant.txt'));
    }

    public function test_url_returns_the_conventional_public_path(): void
    {
        $this->assertSame('/storage/avatars/1.png', Storage::url('avatars/1.png'));
    }

    public function test_directory_traversal_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Storage::put('../../outside.txt', 'x');
    }
}
