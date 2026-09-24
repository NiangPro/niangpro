<?php

namespace Niang\Core;

use Niang\Core\Database\DB;

/**
 * Sessions en base (SESSION_DRIVER=database) : table `sessions`, partagée par tous les serveurs web
 * derrière un répartiteur de charge — avec le stockage natif de PHP, un visiteur envoyé sur un
 * autre serveur perdait sa session (déconnecté, panier vidé).
 *
 * Branché par Session::start() via session_set_save_handler() : session_regenerate_id(),
 * session_destroy() et le ramasse-miettes de PHP passent tous par ici. Le contenu est encodé en
 * base64 (la sérialisation de PHP peut contenir des octets nuls, refusés par une colonne texte
 * PostgreSQL).
 *
 * Contrairement au stockage natif, aucun verrou par session : deux requêtes simultanées du même
 * visiteur qui modifient toutes deux la session — la dernière écriture l'emporte.
 */
final class DatabaseSessionHandler implements \SessionHandlerInterface
{
    public function __construct(private int $lifetimeSeconds)
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $row = DB::selectOne(
            'SELECT payload FROM sessions WHERE id = ? AND last_activity >= ?',
            [$id, time() - $this->lifetimeSeconds],
            'write' // relire sa propre écriture, jamais un réplica en retard
        );

        if ($row === null) {
            return '';
        }

        $payload = base64_decode((string) $row['payload'], true);

        return $payload === false ? '' : $payload;
    }

    public function write(string $id, string $data): bool
    {
        // DELETE + INSERT dans une transaction : portable sur SQLite, MySQL et PostgreSQL, là où un
        // « upsert » a une syntaxe différente pour chacun.
        DB::transaction(function () use ($id, $data): void {
            DB::statement('DELETE FROM sessions WHERE id = ?', [$id]);
            DB::statement('INSERT INTO sessions (id, payload, last_activity) VALUES (?, ?, ?)', [$id, base64_encode($data), time()]);
        });

        return true;
    }

    public function destroy(string $id): bool
    {
        DB::statement('DELETE FROM sessions WHERE id = ?', [$id]);

        return true;
    }

    public function gc(int $max_lifetime): int
    {
        return DB::affected('DELETE FROM sessions WHERE last_activity < ?', [time() - max($max_lifetime, $this->lifetimeSeconds)]);
    }
}
