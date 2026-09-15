<?php

namespace Niang\Core\Database\Grammar;

use Niang\Core\Database\Expression;

/**
 * Traduit les types de colonnes abstraits (Blueprint) vers le SQL réel d'un moteur donné.
 * C'est la seule couche qui doit connaître les différences entre SQLite, MySQL et PostgreSQL :
 * le reste du framework (Blueprint, Schema, migrations applicatives) reste identique quel que
 * soit le moteur configuré dans DB_CONNECTION.
 */
abstract class Grammar
{
    /** Entoure un identifiant (table/colonne) des guillemets propres au moteur. */
    abstract public function wrap(string $identifier): string;

    /** Colonne id() complète (clé primaire auto-incrémentée) — trop différente d'un moteur à l'autre pour factoriser. */
    abstract public function compileId(string $name): string;

    /** Mot-clé de type SQL pour un type abstrait donné (string, text, integer, ...). */
    abstract public function typeKeyword(string $type, array $params): string;

    /** Assemble une colonne "normale" : nom + type + NOT NULL/DEFAULT/UNIQUE, communs aux trois moteurs. */
    public function compileColumn(
        string $name,
        string $type,
        array $params,
        bool $nullable,
        bool $hasDefault,
        mixed $default,
        bool $unique
    ): string {
        $sql = $this->wrap($name) . ' ' . $this->typeKeyword($type, $params);

        if (!$nullable) {
            $sql .= ' NOT NULL';
        }

        if ($hasDefault) {
            $sql .= ' DEFAULT ' . $this->compileDefault($default);
        }

        if ($unique) {
            $sql .= ' UNIQUE';
        }

        return $sql;
    }

    protected function compileDefault(mixed $value): string
    {
        return match (true) {
            $value instanceof Expression => $value->value,
            is_string($value) => "'" . str_replace("'", "''", $value) . "'",
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    public function compileRenameColumn(string $table, string $from, string $to): string
    {
        return sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->wrap($table),
            $this->wrap($from),
            $this->wrap($to)
        );
    }

    public function compileDropColumn(string $table, string $column): string
    {
        return sprintf('ALTER TABLE %s DROP COLUMN %s', $this->wrap($table), $this->wrap($column));
    }

    public function compileRenameTable(string $from, string $to): string
    {
        return sprintf('ALTER TABLE %s RENAME TO %s', $this->wrap($from), $this->wrap($to));
    }

    public function compileIndex(string $table, array $columns): string
    {
        $indexName = $table . '_' . implode('_', $columns) . '_index';

        return sprintf(
            'CREATE INDEX %s ON %s (%s)',
            $this->wrap($indexName),
            $this->wrap($table),
            implode(', ', array_map($this->wrap(...), $columns))
        );
    }
}
