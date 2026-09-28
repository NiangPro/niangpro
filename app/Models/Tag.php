<?php

namespace App\Models;

use Niang\Core\Database\Model;

class Tag extends Model
{
    protected static array $fillable = ['name'];

    /** La table tags n'a ni created_at ni updated_at. */
    protected static bool $timestamps = false;
}
