<?php

namespace App\Models;

use Niang\Core\Database\Model;

class User extends Model
{
    /** `role` et `email_verified_at` n'en font volontairement pas partie : voir forceUpdate(). */
    protected static array $fillable = ['name', 'email', 'password'];

    /**
     * Rôle administrateur : colonne `role` ajoutée par le module d'administration des thèmes
     * boutique et blog. Sans cette colonne (squelette minimal), personne n'est administrateur.
     */
    public static function isAdmin(?array $user): bool
    {
        return ($user['role'] ?? null) === 'admin';
    }

    /** Page d'arrivée après connexion : le tableau de bord pour un administrateur, l'accueil sinon. */
    public static function homePath(?array $user): string
    {
        return self::isAdmin($user) ? '/admin' : '/';
    }
}
