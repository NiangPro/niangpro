<?php

declare(strict_types=1);

namespace Niang\Core\Http;

use Niang\Core\Database\CursorPaginator;
use Niang\Core\Database\Paginator;
use Niang\Core\Database\SimplePaginator;

/** Retourné par JsonResource::collection() — ajoute meta/links quand la source est un Paginator. */
class JsonResourceCollection
{
    /** @param class-string<JsonResource> $resourceClass */
    public function __construct(private string $resourceClass, private array|Paginator|SimplePaginator|CursorPaginator $items)
    {
    }

    public function toArray(): array
    {
        $records = is_array($this->items) ? $this->items : $this->items->items;

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

        if ($this->items instanceof SimplePaginator) {
            $payload['meta'] = ['current_page' => $this->items->currentPage, 'per_page' => $this->items->perPage];
            $payload['links'] = [
                'prev' => $this->items->currentPage > 1 ? '?page=' . ($this->items->currentPage - 1) : null,
                'next' => $this->items->hasMorePages() ? '?page=' . ($this->items->currentPage + 1) : null,
            ];
        }

        if ($this->items instanceof CursorPaginator) {
            $payload['meta'] = ['per_page' => $this->items->perPage, 'next_cursor' => $this->items->nextCursor];
            $payload['links'] = ['next' => $this->items->nextCursor !== null ? '?cursor=' . rawurlencode($this->items->nextCursor) : null];
        }

        return Response::json($payload, $status);
    }
}
