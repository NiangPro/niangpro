<?php

namespace Niang\Core;

use Niang\Core\Exceptions\HttpException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/**
 * Mode maintenance (`niang down` / `niang up`) : tant que storage/framework/down existe, toute
 * requête reçoit une 503 avec Retry-After — sauf /up et /health, pour que la supervision ne
 * déclenche pas d'alerte pendant une maintenance prévue, et sauf les visiteurs qui ont ouvert
 * l'URL secrète (`niang down --secret`) : un cookie chiffré les laisse naviguer normalement.
 */
final class MaintenanceMode
{
    private const COOKIE = 'niang_maintenance';

    /** Chemins toujours servis, même en maintenance. */
    private const ALWAYS_UP = ['/up', '/health', '/health/live', '/health/ready'];

    public static function isDown(): bool
    {
        return is_file(self::path());
    }

    /** @return array{since: int, retry: ?int, secret: ?string}|null */
    public static function data(): ?array
    {
        if (!self::isDown()) {
            return null;
        }

        $data = json_decode((string) file_get_contents(self::path()), true);

        $defaults = ['since' => 0, 'retry' => null, 'secret' => null];

        return is_array($data) ? $data + $defaults : $defaults;
    }

    /** Seul le hachage du secret est écrit sur le disque. */
    public static function activate(?int $retrySeconds = null, ?string $secret = null): void
    {
        $dir = dirname(self::path());

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(self::path(), json_encode([
            'since' => time(),
            'retry' => $retrySeconds,
            'secret' => $secret !== null ? hash('sha256', $secret) : null,
        ]), LOCK_EX);
    }

    public static function deactivate(): void
    {
        if (self::isDown()) {
            unlink(self::path());
        }
    }

    /**
     * Appelée par Application::handle() avant le routeur : null pour laisser passer la requête,
     * une redirection pour l'URL secrète ; lève une HttpException 503 sinon.
     */
    public static function intercept(Request $request): ?Response
    {
        $data = self::data();

        if ($data === null) {
            return null;
        }

        $path = '/' . trim($request->uri, '/');

        if (in_array($path, self::ALWAYS_UP, true)) {
            return null;
        }

        if ($data['secret'] !== null) {
            if (hash_equals($data['secret'], hash('sha256', ltrim($path, '/')))) {
                return Response::redirect('/')->cookie(self::COOKIE, $data['secret'], 12 * 60);
            }

            $cookie = Cookie::get(self::COOKIE);

            if (is_string($cookie) && hash_equals($data['secret'], $cookie)) {
                return null;
            }
        }

        $headers = $data['retry'] !== null ? ['Retry-After' => (string) $data['retry']] : [];

        throw new HttpException(503, '', $headers);
    }

    private static function path(): string
    {
        return base_path('storage/framework/down');
    }
}
