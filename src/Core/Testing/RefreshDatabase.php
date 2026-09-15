<?php

namespace Niang\Core\Testing;

use Niang\Core\Database\DB;

/**
 * À utiliser (`use RefreshDatabase;`) dans un test qui écrit en base : enrobe le test dans une
 * transaction annulée à la fin, pour qu'aucune donnée ne fuite vers les tests suivants — la base
 * de test (:memory:) survit pour tout le run PHPUnit, contrairement à un vrai processus web.
 */
trait RefreshDatabase
{
    protected function refreshDatabase(): void
    {
        DB::beginTransaction();
    }

    protected function rollbackRefreshedDatabase(): void
    {
        DB::rollBack();
    }
}
