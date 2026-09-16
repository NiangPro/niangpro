<?php

namespace Tests\Database;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use Niang\Core\Database\DB;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Prouve que Model::with() élimine le N+1 : le nombre de requêtes reste constant quel que soit
 * le nombre d'enregistrements, mesuré via DB::queryCount() plutôt que simplement supposé.
 */
class EagerLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_with_loads_has_many_in_a_constant_number_of_queries(): void
    {
        $postIds = [];
        for ($i = 0; $i < 5; $i++) {
            $postIds[] = Post::create(['title' => "Post $i", 'body' => 'Contenu']);
        }
        foreach ($postIds as $postId) {
            Comment::create(['post_id' => $postId, 'body' => 'Commentaire A']);
            Comment::create(['post_id' => $postId, 'body' => 'Commentaire B']);
        }

        DB::resetQueryCount();
        $posts = Post::with('comments')->get();

        // 1 requête pour les posts + 1 requête (whereIn) pour tous les commentaires = 2,
        // peu importe le nombre de posts (sans with(), ce serait 1 + N).
        $this->assertSame(2, DB::queryCount());
        $this->assertCount(5, $posts);
        foreach ($posts as $post) {
            $this->assertCount(2, $post['comments']);
        }
    }

    public function test_with_loads_many_to_many_in_a_constant_number_of_queries(): void
    {
        $postIds = [];
        for ($i = 0; $i < 4; $i++) {
            $postIds[] = Post::create(['title' => "Post $i", 'body' => 'Contenu']);
        }

        $tagId = Tag::create(['name' => 'php']);
        foreach ($postIds as $postId) {
            DB::insert('INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)', [$postId, $tagId]);
        }

        DB::resetQueryCount();
        $posts = Post::with('tags')->get();

        $this->assertSame(2, DB::queryCount());
        foreach ($posts as $post) {
            $this->assertCount(1, $post['tags']);
            $this->assertSame('php', $post['tags'][0]['name']);
        }
    }

    public function test_with_multiple_relations_stays_constant(): void
    {
        $postIds = [];
        for ($i = 0; $i < 3; $i++) {
            $postId = Post::create(['title' => "Post $i", 'body' => 'Contenu']);
            $postIds[] = $postId;
            Comment::create(['post_id' => $postId, 'body' => 'Commentaire']);
        }

        DB::resetQueryCount();
        $posts = Post::with(['comments', 'tags'])->get();

        // 1 (posts) + 1 (whereIn commentaires) + 1 (jointure tags) = 3, constant quel que soit N.
        $this->assertSame(3, DB::queryCount());
        $this->assertCount(3, $posts);
    }

    public function test_with_belongs_to_in_a_constant_number_of_queries(): void
    {
        $postId = Post::create(['title' => 'Post', 'body' => 'Contenu']);
        Comment::create(['post_id' => $postId, 'body' => 'A']);
        Comment::create(['post_id' => $postId, 'body' => 'B']);
        Comment::create(['post_id' => $postId, 'body' => 'C']);

        DB::resetQueryCount();
        $comments = Comment::with('post')->get();

        $this->assertSame(2, DB::queryCount());
        foreach ($comments as $comment) {
            $this->assertSame('Post', $comment['post']['title']);
        }
    }

    public function test_with_first_returns_single_record_with_relation(): void
    {
        $postId = Post::create(['title' => 'Post unique', 'body' => 'Contenu']);
        Comment::create(['post_id' => $postId, 'body' => 'Commentaire']);

        $post = Post::with('comments')->first();

        $this->assertNotNull($post);
        $this->assertCount(1, $post['comments']);
    }

    public function test_with_paginate_loads_relations_for_current_page_only(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $postId = Post::create(['title' => "Post $i", 'body' => 'Contenu']);
            Comment::create(['post_id' => $postId, 'body' => 'Commentaire']);
        }

        $paginator = Post::with('comments')->paginate(2, 1);

        $this->assertCount(2, $paginator->items);
        $this->assertSame(3, $paginator->total);
        foreach ($paginator->items as $post) {
            $this->assertCount(1, $post['comments']);
        }
    }

    public function test_with_on_empty_result_does_not_query_relations(): void
    {
        DB::resetQueryCount();
        $posts = Post::with('comments')->where('title', 'inexistant')->get();

        $this->assertSame([], $posts);
        // 1 seule requête (posts) : aucune requête whereIn sur un tableau d'ids vide.
        $this->assertSame(1, DB::queryCount());
    }
}
