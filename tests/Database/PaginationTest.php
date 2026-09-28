<?php

namespace Tests\Database;

use Niang\Core\Database\CursorPaginator;
use Niang\Core\Database\DB;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Http\JsonResource;
use Niang\Core\Testing\TestCase;

class PaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_test_items', function ($table) {
            $table->id();
            $table->string('name');
        });

        for ($i = 1; $i <= 7; $i++) {
            $this->items()->insert(['name' => "n$i"]);
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_test_items');
        parent::tearDown();
    }

    private function items(): QueryBuilder
    {
        return new QueryBuilder('np_test_items');
    }

    public function test_simple_paginate_without_count(): void
    {
        DB::resetQueryCount();
        $page = $this->items()->orderBy('id')->simplePaginate(3, 1);

        $this->assertSame(1, DB::queryCount(), 'une seule requête, pas de COUNT(*)');
        $this->assertSame(['n1', 'n2', 'n3'], array_column($page->items, 'name'));
        $this->assertTrue($page->hasMorePages());

        $last = $this->items()->orderBy('id')->simplePaginate(3, 3);
        $this->assertSame(['n7'], array_column($last->items, 'name'));
        $this->assertFalse($last->hasMorePages());
        $this->assertStringContainsString('?page=2', $last->links('/items'));
        $this->assertStringNotContainsString('?page=4', $last->links('/items'));

        $exact = $this->items()->orderBy('id')->simplePaginate(7, 1);
        $this->assertFalse($exact->hasMorePages(), 'page pleine sans suite');
    }

    public function test_cursor_paginate_walks_every_row_once(): void
    {
        $seen = [];
        $cursor = null;
        $pages = 0;

        do {
            $page = $this->items()->cursorPaginate(3, $cursor);
            array_push($seen, ...array_column($page->items, 'name'));
            $cursor = $page->nextCursor;
            $pages++;
        } while ($cursor !== null && $pages < 10);

        $this->assertSame(['n1', 'n2', 'n3', 'n4', 'n5', 'n6', 'n7'], $seen);
        $this->assertSame(3, $pages);
    }

    public function test_rows_inserted_between_pages_are_neither_skipped_nor_repeated(): void
    {
        $first = $this->items()->cursorPaginate(3, null, 'id', 'desc');
        $this->assertSame(['n7', 'n6', 'n5'], array_column($first->items, 'name'));

        // Un nouvel élément arrive en tête : avec OFFSET, n5 serait répété en page 2.
        $this->items()->insert(['name' => 'nouveau']);

        $second = $this->items()->cursorPaginate(3, $first->nextCursor, 'id', 'desc');
        $this->assertSame(['n4', 'n3', 'n2'], array_column($second->items, 'name'));
    }

    public function test_a_forged_or_tampered_cursor_starts_over(): void
    {
        $first = $this->items()->cursorPaginate(3);
        [$payload] = explode('.', (string) $first->nextCursor);

        $forged = [
            rtrim(strtr(base64_encode('"1) OR 1=1 --"'), '+/', '-_'), '='),   // ancien format, sans signature
            rtrim(strtr(base64_encode('"1) OR 1=1 --"'), '+/', '-_'), '=') . '.' . explode('.', (string) $first->nextCursor)[1],
            $payload . '.AAAAAAAAAAAAAAAAAAAAAA',
            'illisible!!',
        ];

        foreach ($forged as $cursor) {
            $page = $this->items()->cursorPaginate(3, $cursor);
            $this->assertSame(['n1', 'n2', 'n3'], array_column($page->items, 'name'), "curseur rejeté : on repart du début ($cursor)");
        }

        $this->assertSame(7, $this->items()->count(), 'table intacte');
    }

    public function test_a_cursor_value_is_always_bound_never_interpreted(): void
    {
        // Même signé (donc émis par l'application), un curseur n'est qu'une valeur liée : ici sur une
        // colonne texte, pour que les trois moteurs acceptent la comparaison.
        $page = $this->items()->cursorPaginate(3, CursorPaginator::encode("n1' OR '1'='1"), 'name');

        $this->assertLessThanOrEqual(3, count($page->items));
        $this->assertSame(7, $this->items()->count(), 'table intacte');
    }

    public function test_the_cursor_column_is_validated(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->items()->cursorPaginate(3, null, 'id; DROP TABLE np_test_items');
    }

    public function test_json_resources_expose_simple_and_cursor_meta(): void
    {
        $simple = PaginationTestResource::collection($this->items()->orderBy('id')->simplePaginate(3, 2))->toResponse();
        $body = json_decode($simple->getContent(), true);
        $this->assertSame(['current_page' => 2, 'per_page' => 3], $body['meta']);
        $this->assertSame(['prev' => '?page=1', 'next' => '?page=3'], $body['links']);

        $cursor = PaginationTestResource::collection($this->items()->cursorPaginate(5))->toResponse();
        $body = json_decode($cursor->getContent(), true);
        $this->assertCount(5, $body['data']);
        $this->assertNotNull($body['meta']['next_cursor']);
        $this->assertSame('?cursor=' . rawurlencode($body['meta']['next_cursor']), $body['links']['next']);
    }
}

class PaginationTestResource extends JsonResource
{
    public function toArray(): array
    {
        return ['name' => $this->resource['name']];
    }
}
