<?php

namespace App\Models;

use Niang\Core\Database\Model;

class Post extends Model
{
    public static function comments(int|string $postId): array
    {
        return static::hasMany($postId, Comment::class, 'post_id');
    }

    public static function tags(int|string $postId): array
    {
        return static::belongsToMany($postId, Tag::class, 'post_tag', 'post_id', 'tag_id');
    }
}
