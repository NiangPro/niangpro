<?php

namespace App\Models;

use Niang\Core\Database\Model;

class PasswordResetToken extends Model
{
    protected static string $table = 'password_reset_tokens';

    /** Supprime tout jeton existant pour cet email — un seul jeton valide à la fois par compte. */
    public static function deleteForEmail(string $email): void
    {
        static::query()->where('email', $email)->delete();
    }
}
