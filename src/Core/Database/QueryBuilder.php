<?php

declare(strict_types=1);

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

    /** @var list<array{query: QueryBuilder, all: bool}> */
    private array $unions = [];
    private ?string $connection = null;

    /** @var class-string<Model>|null Model dont les $casts s'appliquent aux lignes lues (voir Model::query()). */
    private ?string $model = null;

    public function __construct(private string $table)
    {
    }

    /**
     * @internal appelé par Model::query() : les lignes lues passent par Model::castRow().
     *
     * @param class-string<Model> $model
     */
    public function forModel(string $model): static
    {
        $this->model = $model;
        return $this;
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
        $operator = self::assertOperator($operator);
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column $operator ?"];
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): static
    {
        self::assertIdentifier($column);
        [$operator, $value] = func_num_args() === 2 ? ['=', $operator] : [$operator, $value];
        $operator = self::assertOperator($operator);
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

    public function whereNotIn(string $column, array $values): static
    {
        self::assertIdentifier($column);

        if ($values === []) {
            return $this; // NOT IN () est invalide en SQL ; « aucune exclusion » ne filtre rien.
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$column NOT IN ($placeholders)"];
        array_push($this->bindings, ...$values);
        return $this;
    }

    /**
     * EXISTS (sous-requête), ex. les articles qui ont au moins un commentaire :
     *   Post::query()->whereExists(
     *       (new QueryBuilder('comments'))->select('id')->whereColumn('comments.post_id', 'posts.id')
     *   )
     */
    public function whereExists(QueryBuilder $subquery): static
    {
        return $this->addExists('EXISTS', $subquery);
    }

    public function whereNotExists(QueryBuilder $subquery): static
    {
        return $this->addExists('NOT EXISTS', $subquery);
    }

    private function addExists(string $operator, QueryBuilder $subquery): static
    {
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$operator (" . $subquery->toSql() . ')'];
        array_push($this->bindings, ...$subquery->allBindings());
        return $this;
    }

    /**
     * Ajoute les lignes d'une autre requête (mêmes colonnes, dans le même ordre). UNION retire les
     * doublons, unionAll() les garde. orderBy(), limit(), count() et paginate() s'appliquent au
     * résultat combiné : triez par le nom de colonne tel qu'il apparaît dans le résultat (sans table).
     */
    public function union(QueryBuilder $query): static
    {
        $this->unions[] = ['query' => $query, 'all' => false];
        return $this;
    }

    public function unionAll(QueryBuilder $query): static
    {
        $this->unions[] = ['query' => $query, 'all' => true];
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
        $operator = self::assertOperator($operator);
        self::assertIdentifier($first);
        self::assertIdentifier($second);
        $this->wheres[] = [$this->wheres ? 'AND' : '', "$first $operator $second"];
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): static
    {
        return $this->addJoin('JOIN', $table, $first, $operator, $second);
    }

    /** Garde les lignes sans correspondance dans $table (leurs colonnes valent alors NULL). */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->addJoin('LEFT JOIN', $table, $first, $operator, $second);
    }

    private function addJoin(string $type, string $table, string $first, string $operator, string $second): static
    {
        self::assertIdentifier($table);
        self::assertIdentifier($first);
        self::assertIdentifier($second);
        $operator = self::assertOperator($operator);
        $this->joins[] = "$type $table ON $first $operator $second";
        return $this;
    }

    public function having(string $column, mixed $operator, mixed $value = null): static
    {
        self::assertIdentifier($column);
        [$operator, $value] = func_num_args() === 2 ? ['=', $operator] : [$operator, $value];
        $operator = self::assertOperator($operator);
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

    private const OPERATORS = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE'];

    /**
     * L'opérateur est lui aussi interpolé dans la requête : `where('prix', $_GET['op'], 10)` serait
     * sinon une injection. Seuls les opérateurs de comparaison usuels passent.
     */
    private static function assertOperator(mixed $operator): string
    {
        $normalized = is_string($operator) ? strtoupper(trim($operator)) : '';

        if (!in_array($normalized, self::OPERATORS, true)) {
            throw new \InvalidArgumentException('Opérateur SQL invalide : « ' . (is_scalar($operator) ? $operator : get_debug_type($operator)) . ' ».');
        }

        return $normalized;
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

    /** @return list<array<string, mixed>> */
    public function get(): array
    {
        $rows = DB::select($this->toSql(), $this->allBindings(), $this->connection ?? 'read');

        return $this->model !== null ? array_map([$this->model, 'castRow'], $rows) : $rows;
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

    /**
     * Les valeurs d'une seule colonne : pluck('email') => ['a@x.sn', ...] ; avec une clé,
     * pluck('name', 'id') => [1 => 'Awa', ...]. Les $casts du modèle s'appliquent.
     *
     * @return array<int|string, mixed>
     */
    public function pluck(string $column, ?string $key = null): array
    {
        self::assertIdentifier($column);

        if ($key !== null) {
            self::assertIdentifier($key);
        }

        $original = $this->columns;

        // Sur une union, réduire les colonnes d'un seul membre déséquilibrerait l'union.
        if ($this->unions === []) {
            $this->columns = $key !== null && $key !== $column ? "$column, $key" : $column;
        }

        try {
            $rows = $this->get();
        } finally {
            $this->columns = $original;
        }

        $valueName = self::unqualified($column);
        $values = array_column($rows, $valueName, $key !== null ? self::unqualified($key) : null);

        return $values;
    }

    /**
     * Parcourt le résultat par paquets de $size lignes, sans tout charger en mémoire. Trié par id
     * si aucun orderBy() n'est donné (un ordre stable est indispensable entre deux paquets).
     * Retourner false depuis $callback arrête le parcours. Ne modifiez pas, dans $callback, la
     * colonne sur laquelle la requête filtre : les paquets suivants seraient décalés.
     *
     * @param \Closure(array<int, array>, int): mixed $callback reçoit les lignes et le numéro du paquet (1, 2...)
     */
    public function chunk(int $size, \Closure $callback): bool
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('La taille d\'un paquet doit être au moins 1.');
        }

        $query = clone $this;
        $query->orderByClause ??= 'id ASC';

        for ($page = 1; ; $page++) {
            $rows = (clone $query)->limit($size)->offset(($page - 1) * $size)->get();

            if ($rows === []) {
                return true;
            }

            if ($callback($rows, $page) === false) {
                return false;
            }

            if (count($rows) < $size) {
                return true;
            }
        }
    }

    private static function unqualified(string $column): string
    {
        $position = strrpos($column, '.');

        return $position === false ? $column : substr($column, $position + 1);
    }

    /** Existence seule, sans rapatrier de lignes (SELECT 1 ... LIMIT 1). */
    public function exists(): bool
    {
        if ($this->unions !== []) {
            return $this->count() > 0;
        }

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

    /** Page $page sans COUNT(*) : une ligne de plus est lue pour savoir s'il reste une page. */
    public function simplePaginate(int $perPage = 15, int $page = 1): SimplePaginator
    {
        $page = max(1, $page);
        $rows = (clone $this)->limit($perPage + 1)->offset(($page - 1) * $perPage)->get();

        return new SimplePaginator(array_slice($rows, 0, $perPage), $perPage, $page, count($rows) > $perPage);
    }

    /**
     * Page suivant $cursor (valeur de $request->input('cursor')), triée par $column — une colonne
     * UNIQUE (id par défaut), sinon des lignes de même valeur pourraient être sautées. Le tri
     * existant est remplacé par celui de $column. La colonne vient du code, jamais du curseur.
     */
    public function cursorPaginate(int $perPage = 15, ?string $cursor = null, string $column = 'id', string $direction = 'asc'): CursorPaginator
    {
        self::assertIdentifier($column);
        $query = (clone $this)->orderBy($column, $direction);
        $after = CursorPaginator::decode($cursor);

        if ($after !== null) {
            $query->where($column, strtolower($direction) === 'desc' ? '<' : '>', $after);
        }

        $rows = $query->limit($perPage + 1)->get();
        $items = array_slice($rows, 0, $perPage);
        $key = self::unqualified($column);
        $next = count($rows) > $perPage && $items !== [] ? CursorPaginator::encode($items[count($items) - 1][$key] ?? null) : null;

        return new CursorPaginator($items, $perPage, $next);
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
        if ($this->unions !== []) {
            $sql = "SELECT $function($column) as aggregate FROM (" . $this->unionSql() . ') AS np_union';
            $result = DB::selectOne($sql, $this->allBindings(), $this->connection ?? 'read');

            return $result['aggregate'] ?? 0;
        }

        $original = $this->columns;
        $this->columns = "$function($column) as aggregate";
        $result = DB::selectOne($this->toSql(), $this->allBindings(), $this->connection ?? 'read');
        $this->columns = $original;
        return $result['aggregate'] ?? 0;
    }

    public function insert(array $data): string
    {
        $columns = array_keys($data);
        array_walk($columns, fn ($column) => self::assertIdentifier((string) $column));
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
        foreach (array_keys($data) as $column) {
            self::assertIdentifier((string) $column);
        }

        $assignments = implode(', ', array_map(fn ($column) => "$column = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $assignments" . $this->whereSql();

        return DB::statement($sql, [...array_values($data), ...$this->bindings], $this->connection ?? 'write');
    }

    /**
     * Incrément atomique côté base (`stock = stock + 1`), sans lecture préalable : deux requêtes
     * simultanées ne peuvent pas se perdre une mise à jour. $extra met d'autres colonnes à jour
     * dans la même requête.
     */
    public function increment(string $column, int|float $amount = 1, array $extra = []): bool
    {
        return $this->adjust($column, $amount, $extra);
    }

    public function decrement(string $column, int|float $amount = 1, array $extra = []): bool
    {
        return $this->adjust($column, -$amount, $extra);
    }

    private function adjust(string $column, int|float $amount, array $extra): bool
    {
        self::assertIdentifier($column);

        foreach (array_keys($extra) as $name) {
            self::assertIdentifier((string) $name);
        }

        $assignments = ["$column = $column + ?", ...array_map(fn ($name) => "$name = ?", array_keys($extra))];
        $sql = "UPDATE {$this->table} SET " . implode(', ', $assignments) . $this->whereSql();

        return DB::statement($sql, [$amount, ...array_values($extra), ...$this->bindings], $this->connection ?? 'write');
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}" . $this->whereSql();
        return DB::statement($sql, $this->bindings, $this->connection ?? 'write');
    }

    public function toSql(): string
    {
        if ($this->unions !== []) {
            return 'SELECT * FROM (' . $this->unionSql() . ') AS np_union' . $this->orderLimitSql();
        }

        return $this->selectSql() . $this->orderLimitSql() . ($this->lock ? ' FOR UPDATE' : '');
    }

    /** Membres de l'union sans tri ni limite (SQLite les refuse à l'intérieur d'une union). */
    private function unionSql(): string
    {
        $sql = $this->selectSql();

        foreach ($this->unions as ['query' => $query, 'all' => $all]) {
            if ($query->orderByClause !== null || $query->limitValue !== null || $query->offsetValue !== null || $query->unions !== []) {
                throw new \LogicException('union() : triez et limitez la requête principale, pas les requêtes ajoutées.');
            }

            $sql .= ($all ? ' UNION ALL ' : ' UNION ') . $query->selectSql();
        }

        return $sql;
    }

    private function orderLimitSql(): string
    {
        $sql = '';

        if ($this->orderByClause) {
            $sql .= " ORDER BY {$this->orderByClause}";
        }

        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }

        if ($this->offsetValue !== null) {
            $sql .= " OFFSET {$this->offsetValue}";
        }

        return $sql;
    }

    private function selectSql(): string
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

        return $sql;
    }

    /** @internal aussi lue par whereExists() et union() de la requête englobante */
    public function allBindings(): array
    {
        $bindings = [...$this->bindings, ...$this->havingBindings];

        foreach ($this->unions as ['query' => $query]) {
            array_push($bindings, ...$query->allBindings());
        }

        return $bindings;
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
