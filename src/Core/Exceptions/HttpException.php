<?php

namespace Niang\Core\Exceptions;

use Niang\Core\Lang;

/**
 * Exception "d'erreur HTTP" générique : lui associer un code suffit pour que le Handler la rende
 * correctement (page d'erreur si elle existe, sinon un message simple), en JSON si le client
 * attend du JSON. `abort(404)` en est le raccourci le plus courant.
 */
class HttpException extends \RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(private int $status, string $message = '', private array $headers = [])
    {
        parent::__construct($message !== '' ? $message : self::defaultMessage($status));
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    private static function defaultMessage(int $status): string
    {
        return match (true) {
            in_array($status, [401, 403, 404, 405, 419, 429], true) => Lang::get("http.$status"),
            $status >= 500 => Lang::get('http.server_error'),
            default => Lang::get('http.other', ['status' => $status]),
        };
    }
}
