<?php

namespace Tests\Database;

use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Testing\TestCase;

/**
 * Exécute vraiment le SQL généré par QueryBuilder (contrairement à QueryBuilderTest, qui ne
 * vérifie que la chaîne produite) contre la base de test isolée, sur une table jetable.
 */
class QueryBuilderExecutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_test_articles', function ($table) {
            $table->id();
            $table->string('title');
            $table->integer('views');
            $table->timestamp('published_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_test_articles');
        parent::tearDown();
    }

    private function seed(): void
    {
        $rows = [
            ['title' => 'A', 'views' => 10, 'published_at' => '2026-01-01 10:00:00'],
            ['title' => 'B', 'views' => 50, 'published_at' => '2026-01-02 10:00:00'],
            ['title' => 'C', 'views' => 100, 'published_at' => null],
        ];

        foreach ($rows as $row) {
            (new QueryBuilder('np_test_articles'))->insert($row);
        }
    }

    public function test_where_null_and_not_null(): void
    {
        $this->seed();

        $withoutDate = (new QueryBuilder('np_test_articles'))->whereNull('published_at')->get();
        $this->assertCount(1, $withoutDate);
        $this->assertSame('C', $withoutDate[0]['title']);

        $withDate = (new QueryBuilder('np_test_articles'))->whereNotNull('published_at')->get();
        $this->assertCount(2, $withDate);
    }

    public function test_where_between(): void
    {
        $this->seed();

        $results = (new QueryBuilder('np_test_articles'))->whereBetween('views', [10, 50])->get();
        $this->assertCount(2, $results);

        $outside = (new QueryBuilder('np_test_articles'))->whereNotBetween('views', [10, 50])->get();
        $this->assertCount(1, $outside);
        $this->assertSame('C', $outside[0]['title']);
    }

    public function test_distinct(): void
    {
        (new QueryBuilder('np_test_articles'))->insert(['title' => 'X', 'views' => 1]);
        (new QueryBuilder('np_test_articles'))->insert(['title' => 'X', 'views' => 2]);

        $titles = (new QueryBuilder('np_test_articles'))->select('title')->distinct()->get();
        $this->assertCount(1, $titles);
    }

    public function test_having(): void
    {
        $this->seed();

        $results = (new QueryBuilder('np_test_articles'))
            ->select('title')
            ->groupBy('title')
            ->having('views', '>', 40)
            ->get();

        $this->assertCount(2, $results);
    }

    public function test_aggregates(): void
    {
        $this->seed();

        $query = new QueryBuilder('np_test_articles');
        $this->assertSame(160.0, (float) $query->sum('views'));

        $this->assertSame(3, (new QueryBuilder('np_test_articles'))->count());
        $this->assertSame(100, (int) (new QueryBuilder('np_test_articles'))->max('views'));
        $this->assertSame(10, (int) (new QueryBuilder('np_test_articles'))->min('views'));
    }

    public function test_exists(): void
    {
        $this->seed();

        $this->assertTrue((new QueryBuilder('np_test_articles'))->where('title', 'A')->exists());
        $this->assertFalse((new QueryBuilder('np_test_articles'))->where('title', 'Z')->exists());
    }

    public function test_first_or_fail_throws_when_no_match(): void
    {
        $this->expectException(NotFoundException::class);
        (new QueryBuilder('np_test_articles'))->where('title', 'inexistant')->firstOrFail();
    }

    public function test_first_or_fail_returns_record_when_found(): void
    {
        $this->seed();

        $article = (new QueryBuilder('np_test_articles'))->where('title', 'A')->firstOrFail();
        $this->assertSame('A', $article['title']);
    }
}
