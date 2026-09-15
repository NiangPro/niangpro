<?php

namespace Niang\Core\Database;

use Niang\Core\Database\Grammar\Grammar;

class Blueprint
{
    /** @var ColumnDefinition[] */
    private array $columns = [];

    /** @var array<int, string[]> */
    private array $uniqueConstraints = [];

    /** @var array<int, string[]> */
    private array $indexes = [];

    /** @var ForeignKeyDefinition[] */
    private array $foreignKeys = [];

    /** @var array<int, array{0: string, 1: string}> */
    private array $renames = [];

    /** @var string[] */
    private array $drops = [];

    public function __construct(private string $table)
    {
    }

    public function id(string $name = 'id'): static
    {
        $this->columns[] = new ColumnDefinition($name, 'id');
        return $this;
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, 'string', ['length' => $length]);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'text');
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'integer');
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'boolean');
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, 'decimal', ['precision' => $precision, 'scale' => $scale]);
    }

    public function float(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'float');
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'date');
    }

    public function dateTime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'dateTime');
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'timestamp');
    }

    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'json');
    }

    /** Référence vers une autre table (colonne entière, ex: post_id). Chaînez ->constrained() pour la contrainte FK. */
    public function foreignId(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'foreignId');
    }

    public function timestamps(): static
    {
        $this->addColumn('created_at', 'timestamp')->default(new Expression('CURRENT_TIMESTAMP'));
        $this->addColumn('updated_at', 'timestamp')->default(new Expression('CURRENT_TIMESTAMP'));
        return $this;
    }

    /** Contrainte UNIQUE portant sur une ou plusieurs colonnes (au niveau table, pas colonne). */
    public function unique(string|array $columns): static
    {
        $this->uniqueConstraints[] = (array) $columns;
        return $this;
    }

    /** Index sur une ou plusieurs colonnes (instruction CREATE INDEX séparée). */
    public function index(string|array $columns): static
    {
        $this->indexes[] = (array) $columns;
        return $this;
    }

    /** Contrainte de clé étrangère explicite : ->foreign('user_id')->references('id')->on('users'). */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $fk = new ForeignKeyDefinition($column);
        $this->foreignKeys[] = $fk;
        return $fk;
    }

    public function renameColumn(string $from, string $to): static
    {
        $this->renames[] = [$from, $to];
        return $this;
    }

    public function dropColumn(string $name): static
    {
        $this->drops[] = $name;
        return $this;
    }

    private function addColumn(string $name, string $type, array $params = []): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type, $params);
        $this->columns[] = $column;
        return $column;
    }

    public function toCreateSql(Grammar $grammar): string
    {
        $parts = array_map(fn (ColumnDefinition $column) => $column->compile($grammar), $this->columns);

        foreach ($this->uniqueConstraints as $columns) {
            $parts[] = 'UNIQUE (' . implode(', ', array_map($grammar->wrap(...), $columns)) . ')';
        }

        $foreignKeys = $this->foreignKeys;
        foreach ($this->columns as $column) {
            if ($fk = $column->foreignKey()) {
                $foreignKeys[] = $fk;
            }
        }

        foreach ($foreignKeys as $fk) {
            $parts[] = $fk->compile($grammar);
        }

        return sprintf('CREATE TABLE IF NOT EXISTS %s (%s)', $grammar->wrap($this->table), implode(', ', $parts));
    }

    /** @return string[] une instruction ADD COLUMN par colonne (limite portable entre moteurs). */
    public function toAlterSql(Grammar $grammar): array
    {
        return array_map(
            fn (ColumnDefinition $column) => "ALTER TABLE {$grammar->wrap($this->table)} ADD COLUMN " . $column->compile($grammar),
            $this->columns
        );
    }

    /** @return string[] une instruction CREATE INDEX par index déclaré via ->index(). */
    public function indexStatements(Grammar $grammar): array
    {
        return array_map(fn (array $columns) => $grammar->compileIndex($this->table, $columns), $this->indexes);
    }

    /** @return string[] */
    public function renameStatements(Grammar $grammar): array
    {
        return array_map(
            fn (array $rename) => $grammar->compileRenameColumn($this->table, $rename[0], $rename[1]),
            $this->renames
        );
    }

    /** @return string[] */
    public function dropStatements(Grammar $grammar): array
    {
        return array_map(fn (string $column) => $grammar->compileDropColumn($this->table, $column), $this->drops);
    }
}
