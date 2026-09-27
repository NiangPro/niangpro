<?php

declare(strict_types=1);

namespace Niang\Core\Database;

use Niang\Core\AppKey;
use Niang\Core\Lang;

/**
 * Pagination par curseur : chaque page reprend après la dernière valeur vue de la colonne de tri
 * (WHERE id > ?), au lieu d'un OFFSET qui ralentit au fil des pages et saute ou répète des lignes
 * quand la table change entre deux pages. Pas de numéro de page ni de total : « Suivant » seulement.
 */
class CursorPaginator
{
    public function __construct(
        public readonly array $items,
        public readonly int $perPage,
        public readonly ?string $nextCursor,
    ) {
    }

    public function hasMorePages(): bool
    {
        return $this->nextCursor !== null;
    }

    public function links(string $baseUrl): string
    {
        if ($this->nextCursor === null) {
            return '';
        }

        return sprintf(
            '<nav class="pagination"><a href="%s?cursor=%s">%s</a></nav>',
            $baseUrl,
            rawurlencode($this->nextCursor),
            Lang::get('pagination.next')
        );
    }

    /**
     * Curseur opaque et signé (HMAC, clé dérivée d'APP_KEY) : « valeur.signature » en base64url. Un
     * curseur modifié ou fabriqué est ignoré par decode(), comme un curseur illisible. Sans signature,
     * un curseur forgé pouvait envoyer n'importe quelle valeur à la base (toujours liée, jamais
     * interprétée, mais PostgreSQL refuse par exemple un texte comparé à une colonne entière : erreur 500).
     */
    public static function encode(mixed $value): string
    {
        $payload = self::base64((string) json_encode($value));

        return $payload . '.' . self::signature($payload);
    }

    /** Valeur scalaire du curseur, ou null s'il est absent, illisible ou modifié (on repart du début). */
    public static function decode(?string $cursor): int|float|string|null
    {
        if ($cursor === null || !str_contains($cursor, '.')) {
            return null;
        }

        [$payload, $signature] = explode('.', $cursor, 2);

        if (!hash_equals(self::signature($payload), $signature)) {
            return null;
        }

        $json = base64_decode(strtr($payload, '-_', '+/'), true);
        $value = $json === false ? null : json_decode($json, true);

        return is_int($value) || is_float($value) || is_string($value) ? $value : null;
    }

    private static function signature(string $payload): string
    {
        return self::base64(substr(hash_hmac('sha256', $payload, AppKey::derive('cursor'), true), 0, 16));
    }

    private static function base64(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
