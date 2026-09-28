<?php

declare(strict_types=1);

namespace Niang\Core;

/**
 * SESSION_DRIVER=array : sessions en mémoire du process, perdues à la fin de la requête. Pour les
 * tests et les commandes CLI, jamais pour un site (un visiteur serait déconnecté à chaque page).
 */
final class ArraySessionHandler implements \SessionHandlerInterface
{
    /** @var array<string, string> */
    private static array $sessions = [];

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
        return self::$sessions[$id] ?? '';
    }

    public function write(string $id, string $data): bool
    {
        self::$sessions[$id] = $data;
        return true;
    }

    public function destroy(string $id): bool
    {
        unset(self::$sessions[$id]);
        return true;
    }

    public function gc(int $max_lifetime): int
    {
        return 0;
    }
}
