<?php

namespace Tests\Database;

use Niang\Core\Database\DB;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Testing\TestCase;

/**
 * leftJoin, pluck, chunk, increment/decrement et les types bigInteger/uuid/enum, exécutés pour de
 * vrai contre la base de test (SQLite en local, MySQL et PostgreSQL en CI).
 */
class QueryBuilderAdditionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_test_authors', function ($table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('np_test_books', function ($table) {
            $table->id();
            $table->string('title');
            $table->integer('author_id')->nullable();
            $table->integer('stock')->default(0);
            $table->bigInteger('views')->default(0);
            $table->uuid('public_id')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
        });

        $awa = (new QueryBuilder('np_test_authors'))->insert(['name' => 'Awa']);
        (new QueryBuilder('np_test_authors'))->insert(['name' => 'Modou']);

        foreach (['Un', 'Deux', 'Trois', 'Quatre', 'Cinq'] as $i => $title) {
            (new QueryBuilder('np_test_books'))->insert([
                'title' => $title,
                'author_id' => $i < 2 ? (int) $awa : null,
                'stock' => 10,
            ]);
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_test_books');
        Schema::dropIfExists('np_test_authors');
        parent::tearDown();
    }

    private function books(): QueryBuilder
    {
        return new QueryBuilder('np_test_books');
    }

    public function test_left_join_keeps_rows_without_a_match(): void
    {
        $rows = $this->books()
            ->select('np_test_books.title', 'np_test_authors.name')
            ->leftJoin('np_test_authors', 'np_test_books.author_id', '=', 'np_test_authors.id')
            ->orderBy('np_test_books.id')
            ->get();

        $this->assertCount(5, $rows);
        $this->assertSame('Awa', $rows[0]['name']);
        $this->assertNull($rows[4]['name']);

        $inner = $this->books()->join('np_test_authors', 'np_test_books.author_id', '=', 'np_test_authors.id')->get();
        $this->assertCount(2, $inner);
    }

    public function test_pluck_returns_one_column_optionally_keyed(): void
    {
        $this->assertSame(['Un', 'Deux', 'Trois', 'Quatre', 'Cinq'], $this->books()->orderBy('id')->pluck('title'));

        $byId = $this->books()->orderBy('id')->pluck('title', 'id');
        $this->assertSame('Un', $byId[array_key_first($byId)]);
        $this->assertCount(5, $byId);

        $qualified = $this->books()
            ->leftJoin('np_test_authors', 'np_test_books.author_id', '=', 'np_test_authors.id')
            ->whereNotNull('np_test_authors.id')
            ->orderBy('np_test_books.id')
            ->pluck('np_test_books.title');
        $this->assertSame(['Un', 'Deux'], $qualified);
    }

    public function test_pluck_does_not_alter_the_builder(): void
    {
        $query = $this->books()->where('title', 'Un');
        $query->pluck('id');

        $this->assertArrayHasKey('stock', $query->first());
    }

    public function test_chunk_walks_every_row_in_stable_batches(): void
    {
        $seen = [];
        $batches = [];

        $completed = $this->books()->chunk(2, function (array $rows, int $page) use (&$seen, &$batches) {
            $batches[$page] = count($rows);
            array_push($seen, ...array_column($rows, 'title'));
        });

        $this->assertTrue($completed);
        $this->assertSame([1 => 2, 2 => 2, 3 => 1], $batches);
        $this->assertSame(['Un', 'Deux', 'Trois', 'Quatre', 'Cinq'], $seen);
    }

    public function test_chunk_stops_when_the_callback_returns_false(): void
    {
        $calls = 0;

        $completed = $this->books()->chunk(2, function () use (&$calls) {
            $calls++;
            return false;
        });

        $this->assertFalse($completed);
        $this->assertSame(1, $calls);
    }

    public function test_chunk_on_an_empty_result_never_calls_back(): void
    {
        $this->assertTrue($this->books()->where('title', 'inexistant')->chunk(10, fn () => $this->fail('rappel inattendu')));
    }

    public function test_increment_and_decrement_are_computed_by_the_database(): void
    {
        $this->books()->where('title', 'Un')->increment('stock');
        $this->books()->where('title', 'Un')->increment('stock', 5, ['title' => 'Un bis']);
        $this->books()->where('title', 'Deux')->decrement('stock', 3);

        $this->assertSame(16, (int) $this->books()->where('title', 'Un bis')->first()['stock']);
        $this->assertSame(7, (int) $this->books()->where('title', 'Deux')->first()['stock']);
        $this->assertSame(10, (int) $this->books()->where('title', 'Trois')->first()['stock'], 'les autres lignes ne bougent pas');
    }

    public function test_big_integer_holds_values_beyond_32_bits(): void
    {
        $this->books()->where('title', 'Un')->update(['views' => 5_000_000_000]);

        $this->assertSame(5_000_000_000, (int) $this->books()->where('title', 'Un')->first()['views']);
    }

    public function test_uuid_column_stores_generated_uuids(): void
    {
        $uuid = uuid();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid);
        $this->assertNotSame($uuid, uuid());

        $this->books()->where('title', 'Un')->update(['public_id' => $uuid]);
        $this->assertSame('Un', $this->books()->where('public_id', $uuid)->first()['title']);
    }

    public function test_enum_accepts_listed_values_and_the_database_refuses_others(): void
    {
        $this->assertSame('draft', $this->books()->where('title', 'Un')->first()['status']);

        $this->books()->where('title', 'Un')->update(['status' => 'published']);
        $this->assertSame('published', $this->books()->where('title', 'Un')->first()['status']);

        $this->expectException(\PDOException::class);
        DB::statement("UPDATE np_test_books SET status = 'archived' WHERE title = 'Deux'");
    }
}
