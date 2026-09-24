<?php

namespace App\Models;

use Niang\Core\Database\Model;

class PasswordResetToken extends Model
{
    protected static string $table = 'password_reset_tokens';

    /** Seulement created_at (valeur par défaut de la base) : pas d'updated_at à maintenir. */
    protected static bool $timestamps = false;

    /** Supprime tout jeton existant pour cet email — un seul jeton valide à la fois par compte. */
    public static function deleteForEmail(string $email): void
    {
        static::query()->where('email', $email)->delete();
    }
}
