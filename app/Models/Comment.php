<?php

namespace App\Models;

use Niang\Core\Database\Model;

class Comment extends Model
{
    public static function post(array $comment): ?array
    {
        return static::belongsTo($comment, Post::class, 'post_id');
    }

    /** Utilisable avec Comment::with('post')->get() — une requête pour toute la collection. */
    public static function eagerLoadable(): array
    {
        return [
            'post' => fn (array $comments) => static::loadOne($comments, 'post', Post::class, 'post_id'),
        ];
    }
}
