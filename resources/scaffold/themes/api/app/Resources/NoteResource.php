<?php

namespace App\Resources;

use Niang\Core\Http\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => (int) $this->resource['id'],
            'title' => $this->resource['title'],
            'body' => $this->resource['body'],
            'done' => (bool) $this->resource['done'],
            'created_at' => $this->resource['created_at'],
            'updated_at' => $this->resource['updated_at'],
        ];
    }
}
