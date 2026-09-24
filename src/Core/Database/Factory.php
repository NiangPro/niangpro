<?php

namespace Niang\Core\Database;

/**
 * Factory minimale : pas de génération de fausses données intégrée (pas de dépendance externe),
 * vous fournissez vous-même la closure qui définit un enregistrement.
 */
final class Factory
{
    private int $count = 1;

    private function __construct(private string $model, private \Closure $definition)
    {
    }

    public static function for(string $model, \Closure $definition): static
    {
        return new static($model, $definition);
    }

    public function count(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    public function make(array $overrides = []): array
    {
        return array_merge(($this->definition)(), $overrides);
    }

    /** @return string[] identifiants des enregistrements créés */
    public function create(array $overrides = []): array
    {
        $model = $this->model;
        $ids = [];

        for ($i = 0; $i < $this->count; $i++) {
            $ids[] = $model::forceCreate($this->make($overrides)); // données écrites par le développeur, pas par un visiteur
        }

        return $ids;
    }
}
