<?php

namespace Tests\Database;

use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Testing\TestCase;

/** union(), whereExists(), whereNotIn() exécutés pour de vrai (SQLite, MySQL et PostgreSQL en CI). */
class UnionExistsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
        Schema::create('np_test_comments', function ($table) {
            $table->id();
            $table->integer('post_id');
            $table->string('body');
        });
        Schema::create('np_test_archives', function ($table) {
            $table->id();
            $table->string('title');
            $table->string('status');
        });

        foreach ([['A', 'published'], ['B', 'draft'], ['C', 'published']] as [$title, $status]) {
            $this->q('np_test_posts')->insert(['title' => $title, 'status' => $status]);
        }
        foreach ([['Ancien', 'archived'], ['A', 'published']] as [$title, $status]) {
            $this->q('np_test_archives')->insert(['title' => $title, 'status' => $status]);
        }
        $this->q('np_test_comments')->insert(['post_id' => 1, 'body' => 'bien']);
        $this->q('np_test_comments')->insert(['post_id' => 3, 'body' => 'spam']);
    }

    protected function tearDown(): void
    {
        foreach (['np_test_comments', 'np_test_posts', 'np_test_archives'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function q(string $table): QueryBuilder
    {
        return new QueryBuilder($table);
    }

    public function test_where_exists_and_not_exists_with_a_correlated_subquery(): void
    {
        $commented = $this->q('np_test_posts')
            ->whereExists($this->q('np_test_comments')->select('id')->whereColumn('np_test_comments.post_id', 'np_test_posts.id'))
            ->orderBy('id')->pluck('title');
        $this->assertSame(['A', 'C'], $commented);

        $withoutSpam = $this->q('np_test_posts')
            ->where('status', 'published')
            ->whereNotExists($this->q('np_test_comments')->select('id')->whereColumn('np_test_comments.post_id', 'np_test_posts.id')->where('body', 'spam'))
            ->pluck('title');
        $this->assertSame(['A'], $withoutSpam, 'les valeurs liées de la sous-requête arrivent dans le bon ordre');
    }

    public function test_where_not_in(): void
    {
        $this->assertSame(['B'], $this->q('np_test_posts')->whereNotIn('title', ['A', 'C'])->pluck('title'));
        $this->assertCount(3, $this->q('np_test_posts')->whereNotIn('title', [])->get(), 'liste vide : aucun filtre');
    }

    public function test_union_removes_duplicates_and_union_all_keeps_them(): void
    {
        $posts = fn () => $this->q('np_test_posts')->select('title', 'status')->where('status', 'published');
        $archives = fn () => $this->q('np_test_archives')->select('title', 'status');

        $this->assertSame(['A', 'Ancien', 'C'], $posts()->union($archives())->orderBy('title')->pluck('title'));
        $this->assertSame(['A', 'A', 'Ancien', 'C'], $posts()->unionAll($archives())->orderBy('title')->pluck('title'));
    }

    public function test_union_results_can_be_ordered_limited_counted_and_paginated(): void
    {
        $union = fn () => $this->q('np_test_posts')->select('title', 'status')->unionAll($this->q('np_test_archives')->select('title', 'status')->where('status', 'archived'));

        $this->assertSame(['C', 'B'], $union()->orderBy('title', 'desc')->limit(2)->pluck('title'));
        $this->assertSame(4, $union()->count());
        $this->assertTrue($union()->exists());

        $page = $union()->orderBy('title')->paginate(3, 2);
        $this->assertSame(4, $page->total);
        $this->assertSame(['C'], array_column($page->items, 'title'));
    }

    public function test_the_added_query_cannot_be_ordered_or_limited(): void
    {
        $this->expectException(\LogicException::class);
        $this->q('np_test_posts')->select('title')->union($this->q('np_test_archives')->select('title')->limit(1))->get();
    }
}
