<?php

namespace Niang\Core\Database;

class Schema
{
    public static function create(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        DB::statement($blueprint->toCreateSql());
    }

    /**
     * Ajoute des colonnes à une table existante (une instruction par colonne : limite de SQLite).
     */
    public static function table(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        foreach ($blueprint->toAlterSql() as $statement) {
            DB::statement($statement);
        }
    }

    public static function drop(string $table): void
    {
        DB::statement("DROP TABLE IF EXISTS $table");
    }

    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }
}
