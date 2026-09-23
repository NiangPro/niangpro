<?php

namespace Niang\Core\Database;

use Niang\Core\Database\Grammar\Grammar;
use Niang\Core\Database\Grammar\MySqlGrammar;
use Niang\Core\Database\Grammar\PostgresGrammar;
use Niang\Core\Database\Grammar\SQLiteGrammar;
use Niang\Core\Env;
use PDO;

class DB
{
    private static array $connections = [];
    private static int $queryCount = 0;

    /** Profondeur d'imbrication de transaction courante (0 = aucune) — voir beginTransaction(). */
    private static int $transactionLevel = 0;

    /** Nombre de requêtes exécutées depuis le dernier resetQueryCount() — utilisé pour prouver
     *  qu'un correctif N+1 réduit vraiment le nombre de requêtes (voir tests/Database). */
    public static function queryCount(): int
    {
        return self::$queryCount;
    }

    public static function resetQueryCount(): void
    {
        self::$queryCount = 0;
    }

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
                if (!self::isAbsolutePath($path)) {
                    $path = base_path($path);
                }

                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            $pdo = new PDO("sqlite:$path");
            $pdo->exec('PRAGMA foreign_keys = ON'); // pas activé par défaut par SQLite, contrairement à MySQL/PostgreSQL
        } else {
            $host = Env::get($prefix . 'HOST', Env::get('DB_HOST', '127.0.0.1'));
            $port = Env::get($prefix . 'PORT', Env::get('DB_PORT', $driver === 'pgsql' ? '5432' : '3306'));
            $database = Env::get($prefix . 'DATABASE', Env::get('DB_DATABASE', 'niangpro'));

            // "charset" n'existe pas dans le DSN PDO_PGSQL (contrairement à PDO_MySQL) : le passer
            // fait échouer la connexion à PostgreSQL ("invalid connection option").
            $dsn = "$driver:host=$host;port=$port;dbname=$database";

            if ($driver === 'mysql') {
                $charset = Env::get($prefix . 'CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));
                $dsn .= ";charset=$charset";
            }

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

    /**
     * `/chemin` (Unix, macOS) ou `C:\chemin`/`C:/chemin` (Windows, lettre de lecteur) ou
     * `\\serveur\partage` (UNC Windows) — sans ce dernier cas, un DB_DATABASE Windows absolu
     * serait pris pour un chemin relatif et préfixé de base_path(), cassant la connexion SQLite.
     */
    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || (bool) preg_match('#^[A-Za-z]:[\\\\/]#', $path);
    }

    /** Le Grammar (traducteur SQL) correspondant au driver configuré pour $connection. */
    public static function grammar(string $connection = 'write'): Grammar
    {
        $prefix = self::resolveName($connection) === 'read' ? 'DB_READ_' : 'DB_';
        $driver = Env::get($prefix . 'CONNECTION', Env::get('DB_CONNECTION', 'sqlite'));

        return match ($driver) {
            'mysql' => new MySqlGrammar(),
            'pgsql' => new PostgresGrammar(),
            default => new SQLiteGrammar(),
        };
    }

    public static function select(string $query, array $bindings = [], string $connection = 'read'): array
    {
        self::$queryCount++;
        $statement = self::connection($connection)->prepare($query);
        self::bindValues($statement, $bindings);
        $statement->execute();
        return $statement->fetchAll();
    }

    public static function selectOne(string $query, array $bindings = [], string $connection = 'read'): ?array
    {
        self::$queryCount++;
        $statement = self::connection($connection)->prepare($query);
        self::bindValues($statement, $bindings);
        $statement->execute();
        $result = $statement->fetch();
        return $result === false ? null : $result;
    }

    public static function statement(string $query, array $bindings = [], string $connection = 'write'): bool
    {
        self::$queryCount++;
        $statement = self::connection($connection)->prepare($query);
        self::bindValues($statement, $bindings);
        return $statement->execute();
    }

    /**
     * PDOStatement::execute($bindings) lie systématiquement tous les paramètres comme des chaînes,
     * quel que soit leur type PHP. Sans affinité de colonne pour absorber la conversion (typiquement
     * une expression agrégée dans une clause HAVING), SQLite compare alors un INTEGER à un TEXTE et
     * le classe toujours avant — un ">" numérique pourtant correct peut alors ne renvoyer aucune ligne.
     * On lie donc explicitement chaque valeur avec le type PDO qui correspond à son type PHP.
     */
    private static function bindValues(\PDOStatement $statement, array $bindings): void
    {
        foreach (array_values($bindings) as $index => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $statement->bindValue($index + 1, $value, $type);
        }
    }

    public static function insert(string $query, array $bindings = [], string $connection = 'write'): string
    {
        self::statement($query, $bindings, $connection);
        return self::connection($connection)->lastInsertId();
    }

    /**
     * Imbrication réelle via SAVEPOINT (SQLite, MySQL/InnoDB et PostgreSQL la supportent tous) :
     * PDO ne permet qu'une seule transaction active à la fois — sans ça, un second
     * beginTransaction() (ex: DB::transaction() appelé depuis du code métier alors que
     * RefreshDatabase a déjà ouvert la transaction du test) lève PDOException("There is already
     * an active transaction") plutôt que de s'imbriquer proprement.
     */
    public static function beginTransaction(): void
    {
        $pdo = self::connection('write');

        if (self::$transactionLevel === 0) {
            $pdo->beginTransaction();
        } else {
            $pdo->exec('SAVEPOINT trans' . (self::$transactionLevel + 1));
        }

        self::$transactionLevel++;
    }

    public static function commit(): void
    {
        if (self::$transactionLevel === 1) {
            self::connection('write')->commit();
        }

        // Aux niveaux > 1 : rien à faire, le SAVEPOINT n'a de sens qu'en cas de rollback ; il
        // fusionne implicitement dans la transaction englobante à son commit.
        self::$transactionLevel = max(0, self::$transactionLevel - 1);
    }

    public static function rollBack(): void
    {
        if (self::$transactionLevel === 1) {
            self::connection('write')->rollBack();
        } elseif (self::$transactionLevel > 1) {
            self::connection('write')->exec('ROLLBACK TO SAVEPOINT trans' . self::$transactionLevel);
        }

        self::$transactionLevel = max(0, self::$transactionLevel - 1);
    }

    /** Une transaction (ou un niveau d'imbrication via SAVEPOINT) est-elle actuellement ouverte ? */
    public static function inTransaction(): bool
    {
        return self::$transactionLevel > 0;
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
