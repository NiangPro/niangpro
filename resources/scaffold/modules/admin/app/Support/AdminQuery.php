<?php

namespace App\Support;

use Niang\Core\Database\DB;
use Niang\Core\Database\Paginator;

/**
 * Listes filtrées de l'administration. Le Query Builder ne sait pas grouper des OR entre
 * parenthèses (« statut = ? AND (nom LIKE ? OR email LIKE ?) ») : ces listes s'écrivent donc en
 * SQL, toujours avec des paramètres liés, et sont paginées ici.
 */
class AdminQuery
{
    /**
     * @param string $from      ex. 'orders WHERE status = ?' — sans ORDER BY ni LIMIT
     * @param list<mixed> $bindings
     */
    public static function paginate(string $select, string $from, array $bindings, string $orderBy, int $perPage, int $page): Paginator
    {
        $page = max(1, $page);
        $total = (int) (DB::selectOne("SELECT COUNT(*) AS aggregate FROM $from", $bindings)['aggregate'] ?? 0);
        $offset = ($page - 1) * $perPage;
        $items = DB::select("SELECT $select FROM $from ORDER BY $orderBy LIMIT $perPage OFFSET $offset", $bindings);

        return new Paginator($items, $total, $perPage, $page);
    }

    /**
     * Motif d'une recherche saisie, à utiliser avec « LIKE ? ESCAPE '!' » : les jokers % et _ tapés
     * par l'utilisateur restent littéraux. « ! » plutôt que l'antislash : même écriture en SQLite et MySQL.
     */
    public static function like(string $search): string
    {
        return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($search)) . '%';
    }
}
