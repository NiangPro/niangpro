<?php

namespace Niang\Core\Database;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

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

    public static function where(string $column, mixed $value): array
    {
        return static::query()->where($column, $value)->get();
    }

    public static function create(array $data): string
    {
        return static::query()->insert($data);
    }

    public static function update(int|string $id, array $data): bool
    {
        return static::query()->where(static::$primaryKey, $id)->update($data);
    }

    public static function destroy(int|string $id): bool
    {
        return static::query()->where(static::$primaryKey, $id)->delete();
    }

    public static function paginate(int $perPage = 15, int $page = 1): Paginator
    {
        return static::query()->paginate($perPage, $page);
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
