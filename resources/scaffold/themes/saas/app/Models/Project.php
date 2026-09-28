<?php

namespace App\Models;

use Niang\Core\Database\Model;

/** Ressource d'exemple : filtrée automatiquement sur l'organisation courante ($tenantScoped). */
class Project extends Model
{
    protected static array $fillable = ['name', 'description'];
    protected static bool $tenantScoped = true;
}
