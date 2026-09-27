<?php

namespace App\Models;

use Niang\Core\Database\Model;

/** Exemple de ressource de l'API : remplacez-la par la vôtre (./bin/niang make:model). */
class Note extends Model
{
    protected static array $fillable = ['title', 'body', 'done'];
    protected static array $casts = ['done' => 'bool', 'user_id' => 'int'];
}
