<?php

namespace App\Models;

use Niang\Core\Database\Model;

class User extends Model
{
    /** `role` et `email_verified_at` n'en font volontairement pas partie : voir forceUpdate(). */
    protected static array $fillable = ['name', 'email', 'password'];

    public static function isAdmin(?array $user): bool
    {
        return ($user['role'] ?? null) === 'admin';
    }

    /** Après connexion : la liste de ses organisations (ou la création de la première). */
    public static function homePath(?array $user): string
    {
        return '/organisations';
    }
}
