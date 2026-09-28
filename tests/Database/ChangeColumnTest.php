<?php

namespace Tests\Database;

use Niang\Core\Database\DB;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Testing\TestCase;

/** ->change() contre une vraie base : SQLite en local (reconstruction de table), MySQL et PostgreSQL en CI. */
class ChangeColumnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_change_authors', function ($table) {
            $table->id();
            $table->string('name', 20);
            $table->integer('score')->default(1);
            $table->index('name');
        });
        Schema::create('np_change_books', function ($table) {
            $table->id();
            $table->foreignId('author_id')->constrained('np_change_authors');
        });

        (new QueryBuilder('np_change_authors'))->insert(['name' => 'Awa', 'score' => 3]);
        (new QueryBuilder('np_change_books'))->insert(['author_id' => 1]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_change_books');
        Schema::dropIfExists('np_change_authors');
        parent::tearDown();
    }

    private function authors(): QueryBuilder
    {
        return new QueryBuilder('np_change_authors');
    }

    public function test_nullable_length_and_default_change_while_data_is_kept(): void
    {
        Schema::table('np_change_authors', function ($table) {
            $table->string('name', 200)->nullable()->change();
            $table->integer('score')->default(10)->change();
        });

        $this->assertSame('Awa', $this->authors()->where('id', 1)->first()['name'], 'données conservées');

        $this->authors()->insert(['name' => null]);
        $inserted = $this->authors()->orderBy('id', 'desc')->first();
        $this->assertNull($inserted['name'], 'NULL désormais accepté');
        $this->assertSame(10, (int) $inserted['score'], 'nouvelle valeur par défaut');

        $long = str_repeat('x', 150);
        $this->authors()->insert(['name' => $long]);
        $this->assertSame($long, $this->authors()->where('name', $long)->first()['name'], 'nouvelle longueur');
    }

    public function test_a_column_can_become_not_null_again(): void
    {
        Schema::table('np_change_authors', fn ($table) => $table->string('name', 50)->nullable()->change());
        Schema::table('np_change_authors', fn ($table) => $table->string('name', 50)->change());

        $this->expectException(\PDOException::class);
        $this->authors()->insert(['name' => null]);
    }

    public function test_indexes_and_foreign_keys_survive(): void
    {
        Schema::table('np_change_authors', fn ($table) => $table->string('name', 100)->change());

        if (DB::grammar() instanceof \Niang\Core\Database\Grammar\SQLiteGrammar) {
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = 'np_change_authors' AND sql IS NOT NULL");
            $this->assertCount(1, $indexes, 'index recréé après reconstruction');
        }

        $this->assertSame(1, (new QueryBuilder('np_change_books'))->count(), 'ligne enfant intacte');

        $this->expectException(\PDOException::class);
        (new QueryBuilder('np_change_books'))->insert(['author_id' => 999]); // clé étrangère toujours appliquée
    }

    public function test_unknown_column_and_unique_change_are_rejected(): void
    {
        try {
            Schema::table('np_change_authors', fn ($table) => $table->string('absente')->change());
            $this->fail('colonne inconnue acceptée');
        } catch (\Throwable $e) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(\InvalidArgumentException::class);
        Schema::table('np_change_authors', fn ($table) => $table->string('name')->unique()->change());
    }
}
