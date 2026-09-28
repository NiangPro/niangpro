<?php

declare(strict_types=1);

namespace Niang\Core\Database\Grammar;

class SQLiteGrammar extends Grammar
{
    public function wrap(string $identifier): string
    {
        return '"' . $identifier . '"';
    }

    public function compileId(string $name): string
    {
        return $this->wrap($name) . ' INTEGER PRIMARY KEY AUTOINCREMENT';
    }

    /**
     * SQLite ne sait pas modifier une colonne : procédure officielle de reconstruction
     * (https://www.sqlite.org/lang_altertable.html#otheralter) — nouvelle table avec la colonne modifiée,
     * copie des données, remplacement, puis index recréés. Clés étrangères suspendues le temps de
     * l'opération (hors transaction : c'est pourquoi les migrations n'en ouvrent pas).
     */
    public function compileChange(string $table, \Niang\Core\Database\ColumnDefinition $column, ?string $createSql = null, array $indexSql = []): array
    {
        if ($createSql === null) {
            throw new \InvalidArgumentException("change() : table « $table » introuvable.");
        }

        $open = strpos($createSql, '(');
        $close = strrpos($createSql, ')');

        if ($open === false || $close === false) {
            throw new \RuntimeException("change() : définition de « $table » illisible.");
        }

        $parts = self::splitTopLevel(substr($createSql, $open + 1, $close - $open - 1));
        $found = false;

        foreach ($parts as $index => $part) {
            if (preg_match('/^\s*["`\[]?' . preg_quote($column->name(), '/') . '["`\]]?\s/i', $part . ' ') === 1) {
                $parts[$index] = ' ' . $this->compileChangedColumn($column);
                $found = true;
            }
        }

        if (!$found) {
            throw new \InvalidArgumentException("change() : colonne « {$column->name()} » absente de « $table ».");
        }

        $temporary = $this->wrap("__np_change_$table");

        return [
            'PRAGMA foreign_keys = OFF',
            'CREATE TABLE ' . $temporary . ' (' . implode(',', $parts) . ')',
            'INSERT INTO ' . $temporary . ' SELECT * FROM ' . $this->wrap($table),
            'DROP TABLE ' . $this->wrap($table),
            'ALTER TABLE ' . $temporary . ' RENAME TO ' . $this->wrap($table),
            ...$indexSql,
            'PRAGMA foreign_keys = ON',
        ];
    }

    /** @return list<string> éléments séparés par les virgules de premier niveau (hors parenthèses et guillemets) */
    private static function splitTopLevel(string $body): array
    {
        $parts = [];
        $depth = 0;
        $quote = null;
        $current = '';

        foreach (str_split($body) as $char) {
            if ($quote !== null) {
                $quote = $char === $quote ? null : $quote;
            } elseif (in_array($char, ['"', "'", '`'], true)) {
                $quote = $char;
            } elseif ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $parts[] = $current;

        return $parts;
    }

    public function typeKeyword(string $type, array $params): string
    {
        return match ($type) {
            'string' => 'VARCHAR(' . ($params['length'] ?? 255) . ')',
            'text' => 'TEXT',
            'integer', 'foreignId', 'bigInteger' => 'INTEGER', // SQLite : INTEGER est déjà 64 bits
            'uuid' => 'VARCHAR(36)',
            'enum' => 'VARCHAR(255)',
            'boolean' => 'BOOLEAN',
            'decimal' => 'NUMERIC',
            'float' => 'FLOAT',
            'date' => 'DATE',
            'dateTime', 'timestamp' => 'DATETIME',
            'json' => 'TEXT', // SQLite n'a pas de type JSON natif ; stocké comme texte.
            default => throw new \InvalidArgumentException("Type de colonne inconnu : $type"),
        };
    }
}
