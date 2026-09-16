<?php

namespace Tests\Database;

use Niang\Core\Database\DB;
use Niang\Core\Database\Schema;
use Niang\Core\Testing\TestCase;

/**
 * Exécute vraiment le SQL généré (contrairement à GrammarTest, qui ne vérifie que la chaîne produite),
 * contre la base de test isolée (:memory:, voir .env.testing) — jamais storage/database.sqlite.
 * Utilise des tables jetables, nettoyées dans tearDown par sécurité.
 */
class SchemaTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('np_test_comments');
        Schema::dropIfExists('np_test_authors');

        parent::tearDown();
    }

    public function test_create_table_with_constraints_and_index(): void
    {
        Schema::create('np_test_authors', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('np_test_comments', function ($table) {
            $table->id();
            $table->foreignId('np_test_author_id')->constrained('np_test_authors');
            $table->string('body');
            $table->index('body');
        });

        $authorId = DB::insert('INSERT INTO np_test_authors (name) VALUES (?)', ['Awa']);
        DB::insert(
            'INSERT INTO np_test_comments (np_test_author_id, body) VALUES (?, ?)',
            [$authorId, 'Premier commentaire']
        );

        $comments = DB::select('SELECT * FROM np_test_comments');
        $this->assertCount(1, $comments);
        $this->assertSame('Premier commentaire', $comments[0]['body']);
    }

    public function test_foreign_key_constraint_is_enforced(): void
    {
        Schema::create('np_test_authors', function ($table) {
            $table->id();
        });

        Schema::create('np_test_comments', function ($table) {
            $table->id();
            $table->foreignId('np_test_author_id')->constrained('np_test_authors');
        });

        $this->expectException(\PDOException::class);
        DB::insert('INSERT INTO np_test_comments (np_test_author_id) VALUES (?)', [999]);
    }

    public function test_rename_and_drop_column(): void
    {
        Schema::create('np_test_authors', function ($table) {
            $table->id();
            $table->string('old_name');
        });

        Schema::table('np_test_authors', function ($table) {
            $table->renameColumn('old_name', 'name');
        });

        DB::insert('INSERT INTO np_test_authors (name) VALUES (?)', ['Fatou']);
        $author = DB::selectOne('SELECT * FROM np_test_authors');
        $this->assertSame('Fatou', $author['name']);

        Schema::table('np_test_authors', function ($table) {
            $table->dropColumn('name');
        });

        // Portable entre moteurs (PRAGMA table_info est spécifique à SQLite) : après suppression,
        // une ligne fraîchement lue n'a simplement plus la clé 'name'.
        $row = DB::selectOne('SELECT * FROM np_test_authors');
        $this->assertIsArray($row);
        $this->assertArrayNotHasKey('name', $row);
    }
}
