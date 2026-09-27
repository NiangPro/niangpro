<?php

namespace App\Models;

use Niang\Core\Database\Model;

/** Appartenance d'un utilisateur à l'organisation courante, avec son rôle. */
class Membership extends Model
{
    public const ROLES = ['member' => 'Membre', 'admin' => 'Administrateur', 'owner' => 'Propriétaire'];

    protected static array $fillable = ['user_id', 'role'];
    protected static bool $tenantScoped = true;
}
