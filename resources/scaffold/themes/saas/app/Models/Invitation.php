<?php

namespace App\Models;

use Niang\Core\Database\Model;

class Invitation extends Model
{
    protected static array $fillable = ['email', 'role', 'token_hash', 'expires_at'];
    protected static bool $tenantScoped = true;
}
