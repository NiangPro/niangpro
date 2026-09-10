<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use Niang\Core\Database\DB;
use Niang\Core\Database\Seeder;

return new class extends Seeder {
    public function run(): void
    {
        $postIds = Post::factory(fn () => [
            'title' => 'Article de démonstration',
            'body' => 'Contenu généré par le seeder NiangPro.',
        ])->count(3)->create();

        $tagIds = Tag::factory(fn () => [
            'name' => 'tag-' . random_int(1000, 9999),
        ])->count(2)->create();

        foreach ($postIds as $postId) {
            Comment::factory(fn () => [
                'post_id' => $postId,
                'body' => 'Commentaire de test.',
            ])->count(2)->create();

            foreach ($tagIds as $tagId) {
                DB::statement('INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)', [$postId, $tagId]);
            }
        }
    }
};
