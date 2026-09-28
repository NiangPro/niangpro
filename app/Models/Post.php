<?php

namespace App\Models;

use Niang\Core\Database\Model;

class Post extends Model
{
    protected static array $fillable = ['title', 'body'];

    public static function comments(int|string $postId): array
    {
        return static::hasMany($postId, Comment::class, 'post_id');
    }

    public static function tags(int|string $postId): array
    {
        return static::belongsToMany($postId, Tag::class, 'post_tag', 'post_id', 'tag_id');
    }

    /** Utilisables avec Post::with(['comments', 'tags'])->get() — une requête chacune, pas de N+1. */
    public static function eagerLoadable(): array
    {
        return [
            'comments' => fn (array $posts) => static::loadMany($posts, 'comments', Comment::class, 'post_id'),
            'tags' => fn (array $posts) => static::loadManyToMany($posts, 'tags', Tag::class, 'post_tag', 'post_id', 'tag_id'),
        ];
    }
}
