<?php

declare(strict_types=1);

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
    private bool $change = false;
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

    /**
     * Modifie une colonne existante au lieu d'en ajouter une (dans Schema::table()) :
     *   $table->string('title', 500)->nullable()->change();
     * La nouvelle définition remplace entièrement l'ancienne (type, NULL, défaut).
     */
    public function change(): static
    {
        $this->change = true;
        return $this;
    }

    public function isChange(): bool
    {
        return $this->change;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    /** @return array<string, mixed> */
    public function params(): array
    {
        return $this->params;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function defaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function isUnique(): bool
    {
        return $this->unique;
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
