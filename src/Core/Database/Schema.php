<?php

namespace Niang\Core\Database;

class Schema
{
    public static function create(string $table, \Closure $callback): void
    {
        $grammar = DB::grammar();
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        DB::statement($blueprint->toCreateSql($grammar));

        foreach ($blueprint->indexStatements($grammar) as $statement) {
            DB::statement($statement);
        }
    }

    /**
     * Ajoute des colonnes à une table existante, et/ou renomme ou supprime des colonnes déclarées
     * via ->renameColumn() / ->dropColumn(). Une instruction ALTER TABLE par colonne.
     */
    public static function table(string $table, \Closure $callback): void
    {
        $grammar = DB::grammar();
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        foreach ($blueprint->toAlterSql($grammar) as $statement) {
            DB::statement($statement);
        }

        foreach ($blueprint->renameStatements($grammar) as $statement) {
            DB::statement($statement);
        }

        foreach ($blueprint->dropStatements($grammar) as $statement) {
            DB::statement($statement);
        }

        foreach ($blueprint->indexStatements($grammar) as $statement) {
            DB::statement($statement);
        }
    }

    public static function rename(string $from, string $to): void
    {
        DB::statement(DB::grammar()->compileRenameTable($from, $to));
    }

    public static function drop(string $table): void
    {
        DB::statement('DROP TABLE IF EXISTS ' . DB::grammar()->wrap($table));
    }

    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }
}
