<?php

namespace Niang\Core\Database;

use Niang\Core\Exceptions\MassAssignmentException;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    /**
     * Colonnes qu'un formulaire peut légitimement remplir via create()/update(). Les autres clés
     * sont ignorées : Post::create($request->all()) ne peut pas écrire `role`, `user_id` ou
     * `is_admin` même si un visiteur les ajoute à la requête. Laissé vide, create()/update() lèvent
     * une MassAssignmentException plutôt que de tout accepter (ou de tout jeter) en silence.
     * Pour du code de confiance qui écrit des colonnes sensibles (seeder, rôle attribué par un
     * admin, date de vérification d'email...) : forceCreate()/forceUpdate().
     *
     * @var list<string>
     */
    protected static array $fillable = [];

    public static function table(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }

        $class = (new \ReflectionClass(static::class))->getShortName();
        return strtolower($class) . 's';
    }

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::table());
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find(int|string $id): ?array
    {
        return static::query()->where(static::$primaryKey, $id)->first();
    }

    public static function findOrFail(int|string $id): array
    {
        return static::query()->where(static::$primaryKey, $id)->firstOrFail();
    }

    public static function where(string $column, mixed $value): array
    {
        return static::query()->where($column, $value)->get();
    }

    /** Insère les colonnes de $fillable présentes dans $data (les autres sont ignorées) et retourne l'id. */
    public static function create(array $data): string
    {
        return static::forceCreate(static::onlyFillable($data));
    }

    /** Met à jour les colonnes de $fillable présentes dans $data (les autres sont ignorées). */
    public static function update(int|string $id, array $data): bool
    {
        return static::forceUpdate($id, static::onlyFillable($data));
    }

    /** Comme create(), sans filtre $fillable — jamais avec des données venues directement de la requête. */
    public static function forceCreate(array $data): string
    {
        return static::query()->insert($data);
    }

    /** Comme update(), sans filtre $fillable — jamais avec des données venues directement de la requête. */
    public static function forceUpdate(int|string $id, array $data): bool
    {
        return static::query()->where(static::$primaryKey, $id)->update($data);
    }

    /** @return list<string> */
    public static function fillable(): array
    {
        return static::$fillable;
    }

    protected static function onlyFillable(array $data): array
    {
        if (static::$fillable === []) {
            throw new MassAssignmentException(sprintf(
                '%s::create()/update() : déclarez les colonnes modifiables dans `protected static array $fillable = [...]`, '
                . 'ou utilisez forceCreate()/forceUpdate() pour du code de confiance.',
                static::class
            ));
        }

        $filtered = array_intersect_key($data, array_flip(static::$fillable));

        if ($filtered === [] && $data !== []) {
            // Rien d'écrivable : un INSERT/UPDATE vide échouerait plus loin avec une erreur SQL obscure.
            throw new MassAssignmentException(sprintf(
                '%s : aucune des colonnes fournies (%s) ne figure dans $fillable (%s).',
                static::class,
                implode(', ', array_keys($data)),
                implode(', ', static::$fillable)
            ));
        }

        return $filtered;
    }

    public static function destroy(int|string $id): bool
    {
        return static::query()->where(static::$primaryKey, $id)->delete();
    }

    public static function paginate(int $perPage = 15, int $page = 1): Paginator
    {
        return static::query()->paginate($perPage, $page);
    }

    /**
     * Charge une ou plusieurs relations en une seule requête chacune, pour éviter le N+1 :
     * Post::with(['comments', 'tags'])->get(). Les clés utilisables sont celles déclarées par
     * eagerLoadable() dans la classe fille — voir loadMany()/loadOne()/loadManyToMany().
     */
    public static function with(string|array $relations): EagerLoadBuilder
    {
        return new EagerLoadBuilder(static::class, (array) $relations);
    }

    /**
     * Décrit les relations disponibles via with(). Exemple dans une classe fille :
     * public static function eagerLoadable(): array
     * {
     *     return ['comments' => fn (array $posts) => static::loadMany($posts, 'comments', Comment::class, 'post_id')];
     * }
     *
     * @return array<string, \Closure(array): array>
     */
    public static function eagerLoadable(): array
    {
        return [];
    }

    /** hasMany chargée pour toute une collection en une requête (whereIn), plutôt qu'une par ligne. */
    protected static function loadMany(array $records, string $key, string $related, string $foreignKey, string $localKey = 'id'): array
    {
        $ids = array_values(array_unique(array_column($records, $localKey)));

        if (!$ids) {
            return $records;
        }

        $grouped = [];
        foreach ($related::query()->whereIn($foreignKey, $ids)->get() as $item) {
            $grouped[$item[$foreignKey]][] = $item;
        }

        foreach ($records as &$record) {
            $record[$key] = $grouped[$record[$localKey]] ?? [];
        }

        return $records;
    }

    /** belongsTo chargée pour toute une collection en une requête, plutôt qu'une par ligne. */
    protected static function loadOne(array $records, string $key, string $related, string $foreignKey, string $ownerKey = 'id'): array
    {
        $ids = array_values(array_unique(array_filter(array_column($records, $foreignKey))));

        if (!$ids) {
            return $records;
        }

        $indexed = array_column($related::query()->whereIn($ownerKey, $ids)->get(), null, $ownerKey);

        foreach ($records as &$record) {
            $record[$key] = $indexed[$record[$foreignKey]] ?? null;
        }

        return $records;
    }

    /** belongsToMany (via pivot) chargée pour toute une collection en une requête, plutôt qu'une par ligne. */
    protected static function loadManyToMany(
        array $records,
        string $key,
        string $related,
        string $pivotTable,
        string $foreignKey,
        string $relatedKey,
        string $localKey = 'id'
    ): array {
        $ids = array_values(array_unique(array_column($records, $localKey)));

        if (!$ids) {
            return $records;
        }

        $relatedTable = $related::table();
        $relatedPrimaryKey = $related::$primaryKey;
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $sql = "SELECT {$pivotTable}.{$foreignKey} as np_pivot_key, {$relatedTable}.* "
            . "FROM {$relatedTable} "
            . "INNER JOIN {$pivotTable} ON {$relatedTable}.{$relatedPrimaryKey} = {$pivotTable}.{$relatedKey} "
            . "WHERE {$pivotTable}.{$foreignKey} IN ($placeholders)";

        $grouped = [];
        foreach (DB::select($sql, $ids) as $row) {
            $parentId = $row['np_pivot_key'];
            unset($row['np_pivot_key']);
            $grouped[$parentId][] = $row;
        }

        foreach ($records as &$record) {
            $record[$key] = $grouped[$record[$localKey]] ?? [];
        }

        return $records;
    }

    public static function factory(\Closure $definition): Factory
    {
        return Factory::for(static::class, $definition);
    }

    /** Un enregistrement lié possédé par celui-ci (ex: Post::author($post['id'], User::class, 'author_id')). */
    protected static function hasOne(int|string $id, string $related, string $foreignKey): ?array
    {
        return $related::query()->where($foreignKey, $id)->first();
    }

    /** Plusieurs enregistrements liés (ex: Post::comments($post['id'], Comment::class, 'post_id')). */
    protected static function hasMany(int|string $id, string $related, string $foreignKey): array
    {
        return $related::where($foreignKey, $id);
    }

    /** L'enregistrement parent auquel appartient $record (ex: Comment::post($comment, Post::class, 'post_id')). */
    protected static function belongsTo(array $record, string $related, string $foreignKey): ?array
    {
        return isset($record[$foreignKey]) ? $related::find($record[$foreignKey]) : null;
    }

    /** Relation N-N via une table pivot (ex: Post::tags($post['id'], Tag::class, 'post_tag', 'post_id', 'tag_id')). */
    protected static function belongsToMany(
        int|string $id,
        string $related,
        string $pivotTable,
        string $foreignKey,
        string $relatedKey
    ): array {
        $relatedTable = $related::table();
        $relatedPrimaryKey = $related::$primaryKey;

        $sql = "SELECT {$relatedTable}.* FROM {$relatedTable} "
            . "INNER JOIN {$pivotTable} ON {$relatedTable}.{$relatedPrimaryKey} = {$pivotTable}.{$relatedKey} "
            . "WHERE {$pivotTable}.{$foreignKey} = ?";

        return DB::select($sql, [$id]);
    }
}
