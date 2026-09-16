<?php

namespace Niang\Core\Database;

/**
 * Retourné par Model::with(...). Délègue les méthodes de requête (where, orderBy...) au
 * QueryBuilder ; après get()/first()/paginate(), charge chaque relation demandée en une seule
 * requête pour toute la collection (au lieu d'une requête par enregistrement — le fameux N+1).
 *
 * @method $this where(string $column, mixed $operator, mixed $value = null)
 * @method $this orWhere(string $column, mixed $operator, mixed $value = null)
 * @method $this whereIn(string $column, array $values)
 * @method $this orderBy(string $column, string $direction = 'asc')
 * @method $this limit(int $limit)
 * @method $this offset(int $offset)
 */
class EagerLoadBuilder
{
    private QueryBuilder $query;

    /** @param class-string<Model> $model */
    public function __construct(private string $model, private array $relations)
    {
        $this->query = $model::query();
    }

    public function __call(string $method, array $arguments): static
    {
        $this->query->$method(...$arguments);
        return $this;
    }

    public function get(): array
    {
        return $this->loadRelations($this->query->get());
    }

    public function first(): ?array
    {
        $record = $this->query->first();
        return $record ? $this->loadRelations([$record])[0] : null;
    }

    public function paginate(int $perPage = 15, int $page = 1): Paginator
    {
        $paginator = $this->query->paginate($perPage, $page);

        return new Paginator(
            $this->loadRelations($paginator->items),
            $paginator->total,
            $paginator->perPage,
            $paginator->currentPage
        );
    }

    private function loadRelations(array $records): array
    {
        if (!$records) {
            return $records;
        }

        $model = $this->model;
        $available = $model::eagerLoadable();

        foreach ($this->relations as $relation) {
            if (isset($available[$relation])) {
                $records = $available[$relation]($records);
            }
        }

        return $records;
    }
}
