<?php

namespace Niang\Core\Database\Grammar;

class SQLiteGrammar extends Grammar
{
    public function wrap(string $identifier): string
    {
        return '"' . $identifier . '"';
    }

    public function compileId(string $name): string
    {
        return $this->wrap($name) . ' INTEGER PRIMARY KEY AUTOINCREMENT';
    }

    public function typeKeyword(string $type, array $params): string
    {
        return match ($type) {
            'string' => 'VARCHAR(' . ($params['length'] ?? 255) . ')',
            'text' => 'TEXT',
            'integer', 'foreignId' => 'INTEGER',
            'boolean' => 'BOOLEAN',
            'decimal' => 'NUMERIC',
            'float' => 'FLOAT',
            'date' => 'DATE',
            'dateTime', 'timestamp' => 'DATETIME',
            'json' => 'TEXT', // SQLite n'a pas de type JSON natif ; stocké comme texte.
            default => throw new \InvalidArgumentException("Type de colonne inconnu : $type"),
        };
    }
}
