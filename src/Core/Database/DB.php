<?php

namespace Niang\Core\Database;

use Niang\Core\Env;
use PDO;

class DB
{
    private static array $connections = [];

    public static function connection(string $name = 'write'): PDO
    {
        $name = self::resolveName($name);

        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        return self::$connections[$name] = self::createConnection($name);
    }

    /**
     * Sans DB_READ_* dédié dans .env, lecture et écriture partagent la même connexion.
     */
    private static function resolveName(string $name): string
    {
        if ($name === 'read' && !Env::get('DB_READ_CONNECTION') && !Env::get('DB_READ_DATABASE') && !Env::get('DB_READ_HOST')) {
            return 'write';
        }

        return $name;
    }

    private static function createConnection(string $name): PDO
    {
        $prefix = $name === 'read' ? 'DB_READ_' : 'DB_';
        $driver = Env::get($prefix . 'CONNECTION', Env::get('DB_CONNECTION', 'sqlite'));

        if ($driver === 'sqlite') {
            $path = Env::get($prefix . 'DATABASE', Env::get('DB_DATABASE', 'storage/database.sqlite'));

            if ($path !== ':memory:') {
                if (!str_starts_with($path, '/')) {
                    $path = base_path($path);
                }

                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            $pdo = new PDO("sqlite:$path");
        } else {
            $host = Env::get($prefix . 'HOST', Env::get('DB_HOST', '127.0.0.1'));
            $port = Env::get($prefix . 'PORT', Env::get('DB_PORT', '3306'));
            $database = Env::get($prefix . 'DATABASE', Env::get('DB_DATABASE', 'niangpro'));
            $charset = Env::get($prefix . 'CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));
            $dsn = "$driver:host=$host;port=$port;dbname=$database;charset=$charset";

            $pdo = new PDO(
                $dsn,
                Env::get($prefix . 'USERNAME', Env::get('DB_USERNAME', 'root')),
                Env::get($prefix . 'PASSWORD', Env::get('DB_PASSWORD', ''))
            );
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    public static function select(string $query, array $bindings = [], string $connection = 'read'): array
    {
        $statement = self::connection($connection)->prepare($query);
        $statement->execute($bindings);
        return $statement->fetchAll();
    }

    public static function selectOne(string $query, array $bindings = [], string $connection = 'read'): ?array
    {
        $statement = self::connection($connection)->prepare($query);
        $statement->execute($bindings);
        $result = $statement->fetch();
        return $result === false ? null : $result;
    }

    public static function statement(string $query, array $bindings = [], string $connection = 'write'): bool
    {
        $statement = self::connection($connection)->prepare($query);
        return $statement->execute($bindings);
    }

    public static function insert(string $query, array $bindings = [], string $connection = 'write'): string
    {
        self::statement($query, $bindings, $connection);
        return self::connection($connection)->lastInsertId();
    }

    public static function beginTransaction(): void
    {
        self::connection('write')->beginTransaction();
    }

    public static function commit(): void
    {
        self::connection('write')->commit();
    }

    public static function rollBack(): void
    {
        self::connection('write')->rollBack();
    }

    /** Exécute $callback dans une transaction ; rollback automatique si une exception est levée. */
    public static function transaction(\Closure $callback): mixed
    {
        self::beginTransaction();

        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }
}
