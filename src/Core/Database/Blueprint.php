<?php

namespace Niang\Core\Database;

class Blueprint
{
    /** @var array<string|ColumnDefinition> */
    private array $columns = [];

    public function __construct(private string $table)
    {
    }

    public function id(string $name = 'id'): static
    {
        $this->columns[] = "$name INTEGER PRIMARY KEY AUTOINCREMENT";
        return $this;
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn("$name VARCHAR($length)");
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn("$name TEXT");
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn("$name INTEGER");
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn("$name BOOLEAN");
    }

    public function float(string $name): ColumnDefinition
    {
        return $this->addColumn("$name FLOAT");
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn("$name DATE");
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn("$name TIMESTAMP");
    }

    /** Référence vers une autre table (colonne entière, ex: post_id). */
    public function foreignId(string $name): ColumnDefinition
    {
        return $this->addColumn("$name INTEGER");
    }

    public function timestamps(): static
    {
        $this->columns[] = 'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        $this->columns[] = 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        return $this;
    }

    private function addColumn(string $definition): ColumnDefinition
    {
        $column = new ColumnDefinition($definition);
        $this->columns[] = $column;
        return $column;
    }

    public function toCreateSql(): string
    {
        $definitions = array_map(fn ($column) => (string) $column, $this->columns);
        return sprintf('CREATE TABLE IF NOT EXISTS %s (%s)', $this->table, implode(', ', $definitions));
    }

    /** @return string[] une instruction ALTER TABLE par colonne (limite de SQLite). */
    public function toAlterSql(): array
    {
        return array_map(
            fn ($column) => "ALTER TABLE {$this->table} ADD COLUMN " . (string) $column,
            $this->columns
        );
    }
}
