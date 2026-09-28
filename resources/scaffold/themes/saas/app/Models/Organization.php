<?php

namespace App\Models;

use Niang\Core\Database\Model;

/** Une organisation : une ligne de la table des locataires. */
class Organization extends Model
{
    protected static string $table = 'tenants';
    protected static array $fillable = ['name', 'slug'];
}
