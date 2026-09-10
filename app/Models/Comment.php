<?php

namespace App\Models;

use Niang\Core\Database\Model;

class Comment extends Model
{
    public static function post(array $comment): ?array
    {
        return static::belongsTo($comment, Post::class, 'post_id');
    }
}
