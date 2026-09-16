<?php

namespace Tests\Unit;

use Niang\Core\ConfigCache;
use PHPUnit\Framework\TestCase;

/**
 * ConfigCache::store() fige la configuration dans storage/framework/config.php, lu en priorité
 * par Config::load() — un fichier resté en place par erreur casserait silencieusement tous les
 * autres tests du run (Config::load() n'a pas de garde anti-doublon). tearDown() nettoie donc
 * systématiquement, même si le test échoue.
 */
class ConfigCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ConfigCache::clear();
    }

    protected function tearDown(): void
    {
        ConfigCache::clear();
        parent::tearDown();
    }

    public function test_store_and_load_round_trip(): void
    {
        ConfigCache::store(['app' => ['name' => 'Test']]);

        $this->assertTrue(ConfigCache::exists());
        $this->assertSame(['app' => ['name' => 'Test']], ConfigCache::load());
    }

    public function test_clear_removes_the_cache_file(): void
    {
        ConfigCache::store(['app' => ['name' => 'Test']]);
        ConfigCache::clear();

        $this->assertFalse(ConfigCache::exists());
        $this->assertNull(ConfigCache::load());
    }

    public function test_load_returns_null_when_never_cached(): void
    {
        $this->assertNull(ConfigCache::load());
    }
}
