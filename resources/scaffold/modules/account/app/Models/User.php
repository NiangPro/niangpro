<?php

namespace App\Models;

use Niang\Core\Database\Model;

class User extends Model
{
    /** `role` et `email_verified_at` n'en font volontairement pas partie : voir forceUpdate(). */
    protected static array $fillable = ['name', 'email', 'password'];

    /** Rôle administrateur (colonne `role`, absente par défaut) : personne n'est administrateur sans elle. */
    public static function isAdmin(?array $user): bool
    {
        return ($user['role'] ?? null) === 'admin';
    }

    /** Page d'arrivée après connexion et inscription. */
    public static function homePath(?array $user): string
    {
        return '/tableau-de-bord';
    }

    /** Ce que l'on peut montrer ou renvoyer d'un compte : jamais le hachage du mot de passe ni les secrets. */
    public static function publicFields(array $user): array
    {
        return array_intersect_key($user, array_flip(['id', 'name', 'email', 'email_verified_at', 'created_at']));
    }
}
