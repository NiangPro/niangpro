<?php

namespace Niang\Core\Database;

use Niang\Core\Database\Grammar\Grammar;

class ForeignKeyDefinition
{
    private string $referencesColumn = 'id';
    private ?string $onTable = null;
    private ?string $onDeleteAction = null;

    public function __construct(private string $column)
    {
    }

    public function references(string $column): static
    {
        $this->referencesColumn = $column;
        return $this;
    }

    public function on(string $table): static
    {
        $this->onTable = $table;
        return $this;
    }

    public function cascadeOnDelete(): static
    {
        $this->onDeleteAction = 'CASCADE';
        return $this;
    }

    public function nullOnDelete(): static
    {
        $this->onDeleteAction = 'SET NULL';
        return $this;
    }

    public function restrictOnDelete(): static
    {
        $this->onDeleteAction = 'RESTRICT';
        return $this;
    }

    public function compile(Grammar $grammar): string
    {
        $sql = sprintf(
            'FOREIGN KEY (%s) REFERENCES %s (%s)',
            $grammar->wrap($this->column),
            $grammar->wrap((string) $this->onTable),
            $grammar->wrap($this->referencesColumn)
        );

        if ($this->onDeleteAction) {
            $sql .= " ON DELETE {$this->onDeleteAction}";
        }

        return $sql;
    }
}
