<?php

namespace Niang\Core\Database;

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

    /** Curseur opaque (base64url) : une seule valeur, toujours liée comme paramètre SQL. */
    public static function encode(mixed $value): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($value)), '+/', '-_'), '=');
    }

    /** Valeur scalaire du curseur, ou null s'il est absent ou illisible (on repart du début). */
    public static function decode(?string $cursor): int|float|string|null
    {
        if ($cursor === null || $cursor === '') {
            return null;
        }

        $json = base64_decode(strtr($cursor, '-_', '+/'), true);
        $value = $json === false ? null : json_decode($json, true);

        return is_int($value) || is_float($value) || is_string($value) ? $value : null;
    }
}
