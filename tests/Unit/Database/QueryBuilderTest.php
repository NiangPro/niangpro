<?php

namespace Tests\Unit\Database;

use Niang\Core\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

/** Vérifie le SQL généré (pas d'exécution réelle) — voir tests/Database/ pour l'exécution contre une vraie base. */
class QueryBuilderTest extends TestCase
{
    public function test_where_null_and_not_null(): void
    {
        $sql = (new QueryBuilder('posts'))->whereNull('deleted_at')->toSql();
        $this->assertSame('SELECT * FROM posts WHERE deleted_at IS NULL', $sql);

        $sql = (new QueryBuilder('posts'))->whereNotNull('deleted_at')->toSql();
        $this->assertSame('SELECT * FROM posts WHERE deleted_at IS NOT NULL', $sql);
    }

    public function test_where_between(): void
    {
        $sql = (new QueryBuilder('posts'))->whereBetween('views', [10, 100])->toSql();
        $this->assertSame('SELECT * FROM posts WHERE views BETWEEN ? AND ?', $sql);
    }

    public function test_where_column_compares_two_columns(): void
    {
        $sql = (new QueryBuilder('posts'))->whereColumn('updated_at', '>', 'created_at')->toSql();
        $this->assertSame('SELECT * FROM posts WHERE updated_at > created_at', $sql);
    }

    public function test_distinct(): void
    {
        $sql = (new QueryBuilder('posts'))->select('author_id')->distinct()->toSql();
        $this->assertSame('SELECT DISTINCT author_id FROM posts', $sql);
    }

    public function test_having(): void
    {
        $sql = (new QueryBuilder('posts'))
            ->groupBy('author_id')
            ->having('id', '>', 1)
            ->toSql();

        $this->assertSame('SELECT * FROM posts GROUP BY author_id HAVING id > ?', $sql);
    }

    public function test_where_date(): void
    {
        $sql = (new QueryBuilder('posts'))->whereDate('created_at', '2026-01-01')->toSql();
        $this->assertSame('SELECT * FROM posts WHERE DATE(created_at) = ?', $sql);
    }
}
