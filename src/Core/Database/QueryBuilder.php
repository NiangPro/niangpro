<?php

namespace Niang\Core\Database;

class QueryBuilder
{
    private string $columns = '*';
    private array $wheres = [];
    private array $bindings = [];
    private array $joins = [];
    private array $groupByColumns = [];
    private ?string $orderByClause = null;
    private ?int $limitValue = null;
    private ?int $offsetValue = null;
    private bool $lock = false;
    private ?string $connection = null;

    public function __construct(private string $table)
    {
    }

    public function select(string ...$columns): static
    {
        $this->columns = implode(', ', $columns);
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        [$operator, $value] = func_num_args() === 2 ? ['=', $operator] : [$operator, $value];
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column $operator ?"];
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): static
    {
        [$operator, $value] = func_num_args() === 2 ? ['=', $operator] : [$operator, $value];
        $this->wheres[] = ['OR', "$column $operator ?"];
        $this->bindings[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column IN ($placeholders)"];
        array_push($this->bindings, ...$values);
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = "JOIN $table ON $first $operator $second";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orderByClause = $column . ' ' . strtoupper($direction);
        return $this;
    }

    public function groupBy(string ...$columns): static
    {
        $this->groupByColumns = $columns;
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limitValue = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offsetValue = $offset;
        return $this;
    }

    public function lockForUpdate(): static
    {
        $this->lock = true;
        return $this;
    }

    /** Force la requête sur une connexion nommée ('read' ou 'write'), au lieu du choix par défaut. */
    public function onConnection(string $name): static
    {
        $this->connection = $name;
        return $this;
    }

    public function get(): array
    {
        return DB::select($this->toSql(), $this->bindings, $this->connection ?? 'read');
    }

    public function first(): ?array
    {
        $this->limitValue = 1;
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function paginate(int $perPage = 15, int $page = 1): Paginator
    {
        $page = max(1, $page);
        $total = (clone $this)->count();
        $items = (clone $this)->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return new Paginator($items, $total, $perPage, $page);
    }

    public function count(): int
    {
        $original = $this->columns;
        $this->columns = 'COUNT(*) as aggregate';
        $result = DB::selectOne($this->toSql(), $this->bindings, $this->connection ?? 'read');
        $this->columns = $original;
        return (int) ($result['aggregate'] ?? 0);
    }

    public function insert(array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return DB::insert($sql, array_values($data), $this->connection ?? 'write');
    }

    public function update(array $data): bool
    {
        $assignments = implode(', ', array_map(fn ($column) => "$column = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $assignments" . $this->whereSql();

        return DB::statement($sql, [...array_values($data), ...$this->bindings], $this->connection ?? 'write');
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}" . $this->whereSql();
        return DB::statement($sql, $this->bindings, $this->connection ?? 'write');
    }

    public function toSql(): string
    {
        $sql = "SELECT {$this->columns} FROM {$this->table}";

        foreach ($this->joins as $join) {
            $sql .= " $join";
        }

        $sql .= $this->whereSql();

        if ($this->groupByColumns) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupByColumns);
        }

        if ($this->orderByClause) {
            $sql .= " ORDER BY {$this->orderByClause}";
        }

        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }

        if ($this->offsetValue !== null) {
            $sql .= " OFFSET {$this->offsetValue}";
        }

        if ($this->lock) {
            $sql .= ' FOR UPDATE';
        }

        return $sql;
    }

    private function whereSql(): string
    {
        if (!$this->wheres) {
            return '';
        }

        $sql = '';

        foreach ($this->wheres as [$boolean, $clause]) {
            $sql .= ($sql === '' ? '' : " $boolean ") . $clause;
        }

        return " WHERE $sql";
    }
}
