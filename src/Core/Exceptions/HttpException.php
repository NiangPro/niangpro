<?php

namespace Niang\Core\Exceptions;

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
        return match ($status) {
            401 => 'Authentification requise.',
            403 => 'Action non autorisée.',
            404 => 'Page introuvable.',
            405 => 'Méthode non autorisée.',
            419 => 'Jeton CSRF invalide ou expiré.',
            429 => 'Trop de requêtes, réessayez plus tard.',
            default => $status >= 500 ? 'Erreur serveur.' : "Erreur HTTP $status.",
        };
    }
}
