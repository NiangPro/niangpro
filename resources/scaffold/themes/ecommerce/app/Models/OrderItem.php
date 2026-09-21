<?php

namespace App\Models;

use Niang\Core\Database\Model;

/**
 * Ligne d'une commande. Le nom et le prix unitaire sont copiés au moment de l'achat : modifier
 * ou supprimer un produit plus tard ne doit jamais changer une commande passée.
 */
class OrderItem extends Model
{
    protected static string $table = 'order_items';
}
