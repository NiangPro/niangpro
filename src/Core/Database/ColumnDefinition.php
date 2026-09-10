<?php

namespace Niang\Core\Database;

class ColumnDefinition
{
    private bool $nullable = false;
    private bool $hasDefault = false;
    private mixed $defaultValue = null;
    private bool $unique = false;

    public function __construct(private string $definition)
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

    public function __toString(): string
    {
        $sql = $this->definition;

        if (!$this->nullable) {
            $sql .= ' NOT NULL';
        }

        if ($this->hasDefault) {
            $sql .= ' DEFAULT ' . match (true) {
                is_string($this->defaultValue) => "'{$this->defaultValue}'",
                is_bool($this->defaultValue) => $this->defaultValue ? '1' : '0',
                default => (string) $this->defaultValue,
            };
        }

        if ($this->unique) {
            $sql .= ' UNIQUE';
        }

        return $sql;
    }
}
