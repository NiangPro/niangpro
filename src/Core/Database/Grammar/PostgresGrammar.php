<?php

declare(strict_types=1);

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
            'foreignId', 'bigInteger' => 'BIGINT',
            'uuid' => 'UUID',
            'enum' => 'VARCHAR(255)',
            'boolean' => 'BOOLEAN',
            'decimal' => 'NUMERIC(' . ($params['precision'] ?? 10) . ',' . ($params['scale'] ?? 2) . ')',
            'float' => 'DOUBLE PRECISION',
            'date' => 'DATE',
            'dateTime', 'timestamp' => 'TIMESTAMP',
            'json' => 'JSONB',
            default => throw new \InvalidArgumentException("Type de colonne inconnu : $type"),
        };
    }

    /**
     * PostgreSQL modifie une colonne par étapes : type (avec conversion USING), NULL, défaut. Une
     * colonne enum() reçoit sa contrainte CHECK ; une ancienne contrainte CHECK n'est pas retirée.
     */
    public function compileChange(string $table, \Niang\Core\Database\ColumnDefinition $column, ?string $createSql = null, array $indexSql = []): array
    {
        $alter = 'ALTER TABLE ' . $this->wrap($table) . ' ALTER COLUMN ' . $this->wrap($column->name());
        $type = $this->typeKeyword($column->type(), $column->params());
        $default = $column->defaultValue();

        if ($column->type() === 'boolean' && ($default === 0 || $default === 1)) {
            $default = (bool) $default;
        }

        $statements = [
            "$alter TYPE $type USING " . $this->wrap($column->name()) . "::$type",
            $alter . ($column->isNullable() ? ' DROP NOT NULL' : ' SET NOT NULL'),
            $alter . ($column->hasDefault() ? ' SET DEFAULT ' . $this->compileDefault($default) : ' DROP DEFAULT'),
        ];

        if ($column->type() === 'enum') {
            $statements[] = 'ALTER TABLE ' . $this->wrap($table) . ' ADD' . $this->compileEnumCheck($column->name(), $column->params()['values'] ?? []);
        }

        return $statements;
    }

    /** PostgreSQL : TRUE/FALSE (un BOOLEAN n'accepte pas 1/0 comme valeur par défaut). */
    protected function compileDefault(mixed $value): string
    {
        return is_bool($value) ? ($value ? 'TRUE' : 'FALSE') : parent::compileDefault($value);
    }
}
