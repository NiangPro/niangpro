<?php

namespace App\Resources;

use Niang\Core\Http\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->resource['id'],
            'title' => $this->resource['title'],
            'body' => $this->resource['body'],
            'comments_count' => count($this->resource['comments'] ?? []),
            'tags' => array_column($this->resource['tags'] ?? [], 'name'),
        ];
    }
}
