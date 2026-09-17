<?php

namespace Niang\Core\Http;

use Niang\Core\Database\Paginator;

/** Retourné par JsonResource::collection() — ajoute meta/links quand la source est un Paginator. */
class JsonResourceCollection
{
    /** @param class-string<JsonResource> $resourceClass */
    public function __construct(private string $resourceClass, private array|Paginator $items)
    {
    }

    public function toArray(): array
    {
        $records = $this->items instanceof Paginator ? $this->items->items : $this->items;

        return array_map(
            fn (array $record) => (new ($this->resourceClass)($record))->toArray(),
            $records
        );
    }

    public function toResponse(int $status = 200): Response
    {
        $payload = ['data' => $this->toArray()];

        if ($this->items instanceof Paginator) {
            $payload['meta'] = [
                'current_page' => $this->items->currentPage,
                'last_page' => $this->items->lastPage(),
                'per_page' => $this->items->perPage,
                'total' => $this->items->total,
            ];
            $payload['links'] = [
                'prev' => $this->items->currentPage > 1 ? '?page=' . ($this->items->currentPage - 1) : null,
                'next' => $this->items->hasMorePages() ? '?page=' . ($this->items->currentPage + 1) : null,
            ];
        }

        return Response::json($payload, $status);
    }
}
