<?php

namespace Niang\Core\Database;

class Migrator
{
    public function __construct(private string $path)
    {
    }

    /** @return string[] noms des migrations appliquées */
    public function run(): array
    {
        $this->ensureTable();

        $ran = $this->ran();
        $batch = $this->nextBatch();
        $applied = [];

        foreach ($this->files() as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $ran, true)) {
                continue;
            }

            /** @var Migration $migration */
            $migration = require $file;
            $migration->up();

            DB::statement('INSERT INTO migrations (migration, batch) VALUES (?, ?)', [$name, $batch]);
            $applied[] = $name;
        }

        return $applied;
    }

    /** @return string[] noms des migrations annulées (dernier lot uniquement) */
    public function rollback(): array
    {
        $this->ensureTable();

        $batch = (int) (DB::selectOne('SELECT MAX(batch) as max_batch FROM migrations')['max_batch'] ?? 0);

        if ($batch === 0) {
            return [];
        }

        return $this->rollbackBatch($batch);
    }

    /** Annule toutes les migrations puis les rejoue depuis zéro. */
    public function fresh(): array
    {
        $this->ensureTable();

        foreach (DB::select('SELECT DISTINCT batch FROM migrations ORDER BY batch DESC') as $row) {
            $this->rollbackBatch((int) $row['batch']);
        }

        return $this->run();
    }

    private function rollbackBatch(int $batch): array
    {
        $migrations = DB::select('SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC', [$batch]);
        $rolledBack = [];

        foreach ($migrations as $row) {
            $name = $row['migration'];
            $file = $this->path . "/$name.php";

            if (file_exists($file)) {
                /** @var Migration $migration */
                $migration = require $file;
                $migration->down();
            }

            DB::statement('DELETE FROM migrations WHERE migration = ?', [$name]);
            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    private function ensureTable(): void
    {
        DB::statement(
            'CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, migration VARCHAR(255) NOT NULL, batch INTEGER NOT NULL)'
        );
    }

    private function files(): array
    {
        $files = glob($this->path . '/*.php') ?: [];
        sort($files);
        return $files;
    }

    private function ran(): array
    {
        return array_column(DB::select('SELECT migration FROM migrations'), 'migration');
    }

    private function nextBatch(): int
    {
        $max = DB::selectOne('SELECT MAX(batch) as max_batch FROM migrations')['max_batch'] ?? 0;
        return (int) $max + 1;
    }
}
