<?php

namespace Niang\Core\Database\Grammar;

class MySqlGrammar extends Grammar
{
    public function wrap(string $identifier): string
    {
        return '`' . $identifier . '`';
    }

    public function compileId(string $name): string
    {
        return $this->wrap($name) . ' BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
    }

    public function typeKeyword(string $type, array $params): string
    {
        return match ($type) {
            'string' => 'VARCHAR(' . ($params['length'] ?? 255) . ')',
            'text' => 'TEXT',
            'integer' => 'INT',
            'foreignId' => 'BIGINT UNSIGNED',
            'boolean' => 'TINYINT(1)',
            'decimal' => 'DECIMAL(' . ($params['precision'] ?? 10) . ',' . ($params['scale'] ?? 2) . ')',
            'float' => 'FLOAT',
            'date' => 'DATE',
            'dateTime', 'timestamp' => 'DATETIME',
            'json' => 'JSON',
            default => throw new \InvalidArgumentException("Type de colonne inconnu : $type"),
        };
    }
}
