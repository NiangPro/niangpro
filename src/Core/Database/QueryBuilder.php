<?php

namespace Niang\Core\Database;

use Niang\Core\Exceptions\NotFoundException;

class QueryBuilder
{
    private string $columns = '*';
    private bool $distinct = false;
    private array $wheres = [];
    private array $bindings = [];
    private array $joins = [];
    private array $groupByColumns = [];
    private array $havings = [];
    private array $havingBindings = [];
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

    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        self::assertIdentifier($column);
        [$operator, $value] = func_num_args() === 2 ? ['=', $operator] : [$operator, $value];
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column $operator ?"];
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): static
    {
        self::assertIdentifier($column);
        [$operator, $value] = func_num_args() === 2 ? ['=', $operator] : [$operator, $value];
        $this->wheres[] = ['OR', "$column $operator ?"];
        $this->bindings[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        self::assertIdentifier($column);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column IN ($placeholders)"];
        array_push($this->bindings, ...$values);
        return $this;
    }

    public function whereNull(string $column): static
    {
        self::assertIdentifier($column);
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column IS NULL"];
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        self::assertIdentifier($column);
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column IS NOT NULL"];
        return $this;
    }

    /** @param array{0: mixed, 1: mixed} $range */
    public function whereBetween(string $column, array $range): static
    {
        self::assertIdentifier($column);
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column BETWEEN ? AND ?"];
        $this->bindings[] = $range[0];
        $this->bindings[] = $range[1];
        return $this;
    }

    /** @param array{0: mixed, 1: mixed} $range */
    public function whereNotBetween(string $column, array $range): static
    {
        self::assertIdentifier($column);
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column NOT BETWEEN ? AND ?"];
        $this->bindings[] = $range[0];
        $this->bindings[] = $range[1];
        return $this;
    }

    /** DATE(column) = 'YYYY-MM-DD' — fonction DATE() disponible sur SQLite, MySQL et PostgreSQL. */
    public function whereDate(string $column, string $date): static
    {
        self::assertIdentifier($column);
        $this->wheres[] = [$this->wheres ? 'AND' : '', "DATE($column) = ?"];
        $this->bindings[] = $date;
        return $this;
    }

    /** Compare deux colonnes entre elles, ex: whereColumn('updated_at', '>', 'created_at'). */
    public function whereColumn(string $first, string $operatorOrSecond, ?string $second = null): static
    {
        [$operator, $second] = func_num_args() === 2 ? ['=', $operatorOrSecond] : [$operatorOrSecond, $second];
        self::assertIdentifier($first);
        self::assertIdentifier($second);
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$first $operator $second"];
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): static
    {
        self::assertIdentifier($table);
        self::assertIdentifier($first);
        self::assertIdentifier($second);
        $this->joins[] = "JOIN $table ON $first $operator $second";
        return $this;
    }

    public function having(string $column, mixed $operator, mixed $value = null): static
    {
        self::assertIdentifier($column);
        [$operator, $value] = func_num_args() === 2 ? ['=', $operator] : [$operator, $value];
        $this->havings[] = "$column $operator ?";
        $this->havingBindings[] = $value;
        return $this;
    }

    /** Échappatoire volontaire pour un fragment SQL qui n'est pas un simple identifiant (ex: agrégat). */
    public function havingRaw(string $raw, array $bindings = []): static
    {
        $this->havings[] = $raw;
        array_push($this->havingBindings, ...$bindings);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        self::assertIdentifier($column);

        $direction = strtoupper($direction);

        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new \InvalidArgumentException("Sens de tri invalide : « $direction » (« asc » ou « desc » attendu).");
        }

        $this->orderByClause = "$column $direction";
        return $this;
    }

    public function groupBy(string ...$columns): static
    {
        foreach ($columns as $column) {
            self::assertIdentifier($column);
        }

        $this->groupByColumns = $columns;
        return $this;
    }

    /**
     * Un nom de colonne/table n'est jamais lié comme valeur (SQL ne le permet pas) : il est
     * interpolé directement dans la requête. Sans cette validation, `orderBy($_GET['tri'])` (un
     * schéma d'usage courant — trier une liste selon un critère choisi par l'utilisateur) serait
     * une injection SQL triviale. N'importe quel identifiant simple ou qualifié (`table.colonne`)
     * passe ; toute requête ayant réellement besoin d'un fragment SQL arbitraire (agrégat,
     * expression) dispose de havingRaw() ou de select(), pensés pour du SQL de confiance fourni
     * par le développeur — jamais par une entrée utilisateur.
     */
    private static function assertIdentifier(string $value): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $value)) {
            throw new \InvalidArgumentException("Identifiant de colonne ou de table invalide : « $value ».");
        }
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
        return DB::select($this->toSql(), $this->allBindings(), $this->connection ?? 'read');
    }

    public function first(): ?array
    {
        $this->limitValue = 1;
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function firstOrFail(): array
    {
        return $this->first() ?? throw new NotFoundException();
    }

    /** Existence seule, sans rapatrier de lignes (SELECT 1 ... LIMIT 1). */
    public function exists(): bool
    {
        $original = $this->columns;
        $this->columns = '1';
        $this->limitValue = 1;
        $result = DB::selectOne($this->toSql(), $this->allBindings(), $this->connection ?? 'read');
        $this->columns = $original;
        return $result !== null;
    }

    public function paginate(int $perPage = 15, int $page = 1): Paginator
    {
        $page = max(1, $page);
        $total = (clone $this)->count();
        $items = (clone $this)->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return new Paginator($items, $total, $perPage, $page);
    }

    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    public function sum(string $column): float
    {
        return (float) $this->aggregate('SUM', $column);
    }

    public function avg(string $column): float
    {
        return (float) $this->aggregate('AVG', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    private function aggregate(string $function, string $column): mixed
    {
        $original = $this->columns;
        $this->columns = "$function($column) as aggregate";
        $result = DB::selectOne($this->toSql(), $this->allBindings(), $this->connection ?? 'read');
        $this->columns = $original;
        return $result['aggregate'] ?? 0;
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
        $sql = 'SELECT ' . ($this->distinct ? 'DISTINCT ' : '') . $this->columns . " FROM {$this->table}";

        foreach ($this->joins as $join) {
            $sql .= " $join";
        }

        $sql .= $this->whereSql();

        if ($this->groupByColumns) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupByColumns);
        }

        if ($this->havings) {
            $sql .= ' HAVING ' . implode(' AND ', $this->havings);
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

    private function allBindings(): array
    {
        return [...$this->bindings, ...$this->havingBindings];
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
