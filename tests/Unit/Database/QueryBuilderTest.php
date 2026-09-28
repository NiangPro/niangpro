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

    public function test_join_builds_the_expected_clause(): void
    {
        $sql = (new QueryBuilder('posts'))->join('users', 'posts.author_id', '=', 'users.id')->toSql();
        $this->assertSame('SELECT * FROM posts JOIN users ON posts.author_id = users.id', $sql);
    }

    public function test_order_by_rejects_a_direction_other_than_asc_or_desc(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new QueryBuilder('posts'))->orderBy('id', 'id ASC; DROP TABLE posts; --');
    }

    /**
     * Un nom de colonne n'est jamais lié comme valeur (SQL ne le permet pas) — un schéma d'usage
     * courant ("trier une liste selon un critère choisi par l'utilisateur", ex:
     * orderBy($request->input('tri'))) serait une injection SQL triviale sans cette validation.
     *
     * @dataProvider maliciousIdentifiers
     */
    public function test_order_by_rejects_a_non_identifier_column(string $malicious): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new QueryBuilder('posts'))->orderBy($malicious);
    }

    /** @dataProvider maliciousIdentifiers */
    public function test_where_rejects_a_non_identifier_column(string $malicious): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new QueryBuilder('posts'))->where($malicious, 1);
    }

    /** @dataProvider maliciousIdentifiers */
    public function test_group_by_rejects_a_non_identifier_column(string $malicious): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new QueryBuilder('posts'))->groupBy('id', $malicious);
    }

    /** @dataProvider maliciousIdentifiers */
    public function test_where_column_rejects_a_non_identifier_on_either_side(string $malicious): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new QueryBuilder('posts'))->whereColumn($malicious, '=', 'created_at');
    }

    /** @dataProvider maliciousIdentifiers */
    public function test_join_rejects_a_non_identifier_table_or_column(string $malicious): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new QueryBuilder('posts'))->join($malicious, 'posts.id', '=', 'users.id');
    }

    /** @dataProvider maliciousOperators */
    public function test_where_rejects_an_unknown_operator(string $malicious): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new QueryBuilder('posts'))->where('id', $malicious, 1);
    }

    /** @dataProvider maliciousOperators */
    public function test_having_join_and_where_column_reject_an_unknown_operator(string $malicious): void
    {
        foreach ([
            fn () => (new QueryBuilder('posts'))->having('total', $malicious, 1),
            fn () => (new QueryBuilder('posts'))->whereColumn('a', $malicious, 'b'),
            fn () => (new QueryBuilder('posts'))->leftJoin('users', 'posts.user_id', $malicious, 'users.id'),
        ] as $build) {
            try {
                $build();
                $this->fail("Opérateur accepté : $malicious");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_known_operators_are_normalized(): void
    {
        $sql = (new QueryBuilder('posts'))->where('title', 'like', '%a%')->where('views', '>=', 3)->toSql();
        $this->assertSame('SELECT * FROM posts WHERE title LIKE ? AND views >= ?', $sql);
    }

    /** @dataProvider maliciousIdentifiers */
    public function test_insert_update_and_increment_reject_a_non_identifier_column(string $malicious): void
    {
        foreach ([
            fn () => (new QueryBuilder('posts'))->insert([$malicious => 1]),
            fn () => (new QueryBuilder('posts'))->where('id', 1)->update(['title' => 'x', $malicious => 1]),
            fn () => (new QueryBuilder('posts'))->where('id', 1)->increment($malicious),
            fn () => (new QueryBuilder('posts'))->where('id', 1)->increment('views', 1, [$malicious => 1]),
            fn () => (new QueryBuilder('posts'))->pluck($malicious),
        ] as $build) {
            try {
                $build();
                $this->fail("Colonne acceptée : $malicious");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_left_join_builds_the_expected_clause(): void
    {
        $sql = (new QueryBuilder('posts'))->leftJoin('users', 'posts.user_id', '=', 'users.id')->toSql();
        $this->assertSame('SELECT * FROM posts LEFT JOIN users ON posts.user_id = users.id', $sql);
    }

    /** @return array<string, array{0: string}> */
    public static function maliciousOperators(): array
    {
        return [
            'tautologie' => ['= 1 OR 1 = 1 OR id ='],
            'sous-requête' => ['IN (SELECT id FROM users) OR id ='],
            'point-virgule' => ['=; DROP TABLE posts; --'],
            'vide' => [''],
        ];
    }

    public function test_a_qualified_column_name_is_still_accepted(): void
    {
        $sql = (new QueryBuilder('posts'))->where('posts.id', 1)->orderBy('posts.created_at')->toSql();
        $this->assertSame('SELECT * FROM posts WHERE posts.id = ? ORDER BY posts.created_at ASC', $sql);
    }

    /** @return array<string, array{0: string}> */
    public static function maliciousIdentifiers(): array
    {
        return [
            'sous-requête' => ['id) UNION SELECT password FROM users --'],
            'commentaire SQL' => ['id -- '],
            'point-virgule' => ['id; DROP TABLE posts'],
            'espace' => ['id, name'],
            'guillemet' => ["id' OR '1'='1"],
            'vide' => [''],
        ];
    }
}
