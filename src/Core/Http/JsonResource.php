<?php

namespace Niang\Core\Http;

use Niang\Core\Database\Paginator;

/**
 * Enveloppe un enregistrement dans {"data": ...} — surchargez toArray() pour choisir exactement
 * les champs exposés (masquer un mot de passe haché, renommer une clé, ajouter un champ calculé),
 * plutôt que de renvoyer l'enregistrement brut de la base. Pas de JSON:API complet : juste
 * data/meta/links, la partie utile sans la complexité de la spec entière.
 */
abstract class JsonResource
{
    public function __construct(protected array $resource)
    {
    }

    public function toArray(): array
    {
        return $this->resource;
    }

    public function toResponse(int $status = 200): Response
    {
        return Response::json(['data' => $this->toArray()], $status);
    }

    public static function collection(array|Paginator $items): JsonResourceCollection
    {
        return new JsonResourceCollection(static::class, $items);
    }
}
