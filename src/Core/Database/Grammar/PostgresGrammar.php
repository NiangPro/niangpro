<?php

namespace Niang\Core\Database\Grammar;

class PostgresGrammar extends Grammar
{
    public function wrap(string $identifier): string
    {
        return '"' . $identifier . '"';
    }

    public function compileId(string $name): string
    {
        return $this->wrap($name) . ' BIGSERIAL PRIMARY KEY';
    }

    public function typeKeyword(string $type, array $params): string
    {
        return match ($type) {
            'string' => 'VARCHAR(' . ($params['length'] ?? 255) . ')',
            'text' => 'TEXT',
            'integer' => 'INTEGER',
            'foreignId' => 'BIGINT',
            'boolean' => 'BOOLEAN',
            'decimal' => 'NUMERIC(' . ($params['precision'] ?? 10) . ',' . ($params['scale'] ?? 2) . ')',
            'float' => 'DOUBLE PRECISION',
            'date' => 'DATE',
            'dateTime', 'timestamp' => 'TIMESTAMP',
            'json' => 'JSONB',
            default => throw new \InvalidArgumentException("Type de colonne inconnu : $type"),
        };
    }

    /** PostgreSQL : TRUE/FALSE (un BOOLEAN n'accepte pas 1/0 comme valeur par défaut). */
    protected function compileDefault(mixed $value): string
    {
        return is_bool($value) ? ($value ? 'TRUE' : 'FALSE') : parent::compileDefault($value);
    }
}
