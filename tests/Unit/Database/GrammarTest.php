<?php

namespace Tests\Unit\Database;

use Niang\Core\Database\Blueprint;
use Niang\Core\Database\Grammar\MySqlGrammar;
use Niang\Core\Database\Grammar\PostgresGrammar;
use Niang\Core\Database\Grammar\SQLiteGrammar;
use PHPUnit\Framework\TestCase;

class GrammarTest extends TestCase
{
    private function blueprint(): Blueprint
    {
        $blueprint = new Blueprint('posts');
        $blueprint->id();
        $blueprint->string('title');
        $blueprint->foreignId('author_id')->constrained();
        $blueprint->timestamps();

        return $blueprint;
    }

    public function test_sqlite_generates_autoincrement_primary_key(): void
    {
        $sql = $this->blueprint()->toCreateSql(new SQLiteGrammar());

        $this->assertStringContainsString('"id" INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $this->assertStringContainsString('"title" VARCHAR(255) NOT NULL', $sql);
        $this->assertStringContainsString('"author_id" INTEGER NOT NULL', $sql);
        $this->assertStringContainsString('FOREIGN KEY ("author_id") REFERENCES "authors" ("id")', $sql);
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', $sql);
    }

    public function test_mysql_generates_auto_increment_and_backtick_identifiers(): void
    {
        $sql = $this->blueprint()->toCreateSql(new MySqlGrammar());

        $this->assertStringContainsString('`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY', $sql);
        $this->assertStringContainsString('`title` VARCHAR(255) NOT NULL', $sql);
        $this->assertStringContainsString('`author_id` BIGINT UNSIGNED NOT NULL', $sql);
        $this->assertStringContainsString('FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`)', $sql);
    }

    public function test_postgres_generates_bigserial_and_double_quote_identifiers(): void
    {
        $sql = $this->blueprint()->toCreateSql(new PostgresGrammar());

        $this->assertStringContainsString('"id" BIGSERIAL PRIMARY KEY', $sql);
        $this->assertStringContainsString('"author_id" BIGINT NOT NULL', $sql);
        $this->assertStringContainsString('FOREIGN KEY ("author_id") REFERENCES "authors" ("id")', $sql);
    }

    public function test_column_modifiers_are_portable_across_grammars(): void
    {
        foreach ([new SQLiteGrammar(), new MySqlGrammar(), new PostgresGrammar()] as $grammar) {
            $blueprint = new Blueprint('tags');
            $blueprint->id();
            $blueprint->string('name')->unique();
            $blueprint->integer('weight')->nullable()->default(0);

            $sql = $blueprint->toCreateSql($grammar);

            $this->assertStringContainsString('UNIQUE', $sql);
            $this->assertStringContainsString('DEFAULT 0', $sql);
            $this->assertStringNotContainsString('weight' . ($grammar instanceof MySqlGrammar ? '`' : '"') . ' NOT NULL DEFAULT 0', $sql);
        }
    }

    public function test_table_level_unique_and_index_statements(): void
    {
        $blueprint = new Blueprint('post_tag');
        $blueprint->foreignId('post_id');
        $blueprint->foreignId('tag_id');
        $blueprint->unique(['post_id', 'tag_id']);
        $blueprint->index('post_id');

        $grammar = new SQLiteGrammar();

        $this->assertStringContainsString('UNIQUE ("post_id", "tag_id")', $blueprint->toCreateSql($grammar));
        $this->assertSame(
            ['CREATE INDEX "post_tag_post_id_index" ON "post_tag" ("post_id")'],
            $blueprint->indexStatements($grammar)
        );
    }

    public function test_rename_and_drop_column_statements(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->renameColumn('body', 'content');
        $blueprint->dropColumn('legacy_field');

        $grammar = new PostgresGrammar();

        $this->assertSame(
            ['ALTER TABLE "posts" RENAME COLUMN "body" TO "content"'],
            $blueprint->renameStatements($grammar)
        );
        $this->assertSame(
            ['ALTER TABLE "posts" DROP COLUMN "legacy_field"'],
            $blueprint->dropStatements($grammar)
        );
    }
}
