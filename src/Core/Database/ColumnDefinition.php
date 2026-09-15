<?php

namespace Niang\Core\Database;

use Niang\Core\Database\Grammar\Grammar;

/**
 * Descripteur sémantique d'une colonne (nom + type abstrait + modificateurs) — indépendant du
 * moteur SQL. La traduction en SQL réel se fait au dernier moment via compile(Grammar).
 */
class ColumnDefinition
{
    private bool $nullable = false;
    private bool $hasDefault = false;
    private mixed $defaultValue = null;
    private bool $unique = false;
    private ?ForeignKeyDefinition $foreignKey = null;

    public function __construct(private string $name, private string $type, private array $params = [])
    {
    }

    public function nullable(): static
    {
        $this->nullable = true;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->hasDefault = true;
        $this->defaultValue = $value;
        return $this;
    }

    public function unique(): static
    {
        $this->unique = true;
        return $this;
    }

    /**
     * Ajoute une contrainte de clé étrangère sur cette colonne. Sans argument, devine la table
     * référencée à partir du nom de colonne (ex: post_id -> posts).
     */
    public function constrained(?string $table = null, string $column = 'id'): static
    {
        $table ??= $this->guessTableName();
        $this->foreignKey = (new ForeignKeyDefinition($this->name))->references($column)->on($table);
        return $this;
    }

    public function foreignKey(): ?ForeignKeyDefinition
    {
        return $this->foreignKey;
    }

    private function guessTableName(): string
    {
        $base = str_ends_with($this->name, '_id') ? substr($this->name, 0, -3) : $this->name;
        return $base . 's';
    }

    public function compile(Grammar $grammar): string
    {
        if ($this->type === 'id') {
            return $grammar->compileId($this->name);
        }

        return $grammar->compileColumn(
            $this->name,
            $this->type,
            $this->params,
            $this->nullable,
            $this->hasDefault,
            $this->defaultValue,
            $this->unique
        );
    }
}
